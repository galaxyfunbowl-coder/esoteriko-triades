<?php
if (!defined('ABSPATH')) exit;

class GLR_Logic {
  const GAME_WIN_POINTS = 3.0;
  const SERIES_BONUS_POINTS = 3.0;
  const H2H_WIN_POINTS = 1.0;
  const INCLUDE_H2H_IN_TEAM_POINTS = false;

  private static array $H2H_PATTERN = [
    1 => [1=>1, 2=>2, 3=>3],
    2 => [1=>2, 2=>3, 3=>1],
    3 => [1=>3, 2=>1, 3=>2],
  ];

  private static function get_setting(PDO $pdo, string $key, $default) {
    $st=$pdo->prepare('SELECT sv FROM settings WHERE sk=?'); $st->execute([$key]);
    $v=$st->fetchColumn(); return $v!==false ? $v : $default;
  }

  public static function compute_handicap_by_avg(PDO $pdo, float $avg, ?string $gender): int {
    $target = (float) self::get_setting($pdo,'hc_target_avg',180);
    $factor = (float) self::get_setting($pdo,'hc_factor',0.8);
    $capM   = (int) self::get_setting($pdo,'hc_cap_male',30);
    $capF   = (int) self::get_setting($pdo,'hc_cap_female',40);
    $base = round($factor * max(0, $target - $avg));
    $cap  = ($gender === 'F') ? $capF : $capM;
    return (int) min($base, $cap);
  }

  public static function get_handicap(PDO $pdo, int $player_id, int $match_day_id): int {
    $q = $pdo->prepare('SELECT handicap FROM hc_history WHERE player_id=? AND match_day_id=?');
    $q->execute([$player_id,$match_day_id]);
    $row = $q->fetch();
    if ($row) return (int)$row['handicap'];

    $avgQ = $pdo->prepare("
      SELECT SUM(gp.scratch) / NULLIF(COUNT(*),0)
      FROM game_participants gp
      JOIN fixtures f ON f.id=gp.fixture_id
      WHERE gp.player_id=? AND gp.is_blind=0
        AND f.match_day_id IN (
          SELECT id FROM match_days WHERE season_id = (
            SELECT season_id FROM match_days WHERE id=?
          ) AND idx < (SELECT idx FROM match_days WHERE id=?)
        )
    ");
    $avgQ->execute([$player_id,$match_day_id,$match_day_id]);
    $avg = (float)($avgQ->fetchColumn() ?: 0);

    $gq=$pdo->prepare('SELECT gender FROM players WHERE id=?'); $gq->execute([$player_id]);
    $gender = $gq->fetchColumn() ?: null;
    return self::compute_handicap_by_avg($pdo,$avg,$gender);
  }

  private static function ro_fetch(PDO $pdo, int $fixture_id, string $scope, ?int $game_number=null, ?int $left_slot=null, ?int $right_slot=null): ?array {
    $sql = 'SELECT * FROM roll_offs WHERE fixture_id=? AND scope=? AND ';
    $args = [$fixture_id, $scope];
    if ($scope === 'series') { $sql .= 'game_number IS NULL AND left_slot IS NULL AND right_slot IS NULL'; }
    elseif ($scope === 'game') { $sql .= 'game_number=? AND left_slot IS NULL AND right_slot IS NULL'; $args[]=$game_number; }
    else { $sql .= 'game_number=? AND left_slot=? AND right_slot=?'; $args[]=$game_number; $args[]=$left_slot; $args[]=$right_slot; }
    $st=$pdo->prepare($sql); $st->execute($args);
    $row=$st->fetch(); return $row?:null;
  }

  public static function recompute_fixture(PDO $pdo, int $fixture_id, int $match_day_id): array {
    $fxSt = $pdo->prepare('SELECT left_side_absent, right_side_absent FROM fixtures WHERE id=?');
    $fxSt->execute([$fixture_id]);
    $fx = $fxSt->fetch() ?: ['left_side_absent'=>0,'right_side_absent'=>0];
    $leftAbsent  = (int)$fx['left_side_absent'] === 1;
    $rightAbsent = (int)$fx['right_side_absent'] === 1;

    $stmt = $pdo->prepare("
      SELECT gp.game_number, gp.team_side, gp.player_slot, gp.player_id,
             gp.scratch, gp.handicap, gp.is_blind
      FROM game_participants gp
      WHERE gp.fixture_id=?
      ORDER BY gp.game_number, gp.team_side, gp.player_slot
    ");
    $stmt->execute([$fixture_id]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
      $scratch = (int)$r['scratch'];
      $handicap = (int)$r['handicap'];
      $isBlind = ((int)$r['is_blind'] === 1);
      $r['total'] = $isBlind ? $scratch : ($scratch + $handicap);
    }
    unset($r);

    $games = [1=>['left'=>[], 'right'=>[]], 2=>['left'=>[], 'right'=>[]], 3=>['left'=>[], 'right'=>[]]];
    foreach ($rows as $r) {
      $g=(int)$r['game_number']; $side=$r['team_side']; $slot=(int)$r['player_slot'];
      $games[$g][$side][$slot]=$r;
    }

    $series = ['left'=>['pins'=>0,'team_points'=>0,'h2h'=>0],'right'=>['pins'=>0,'team_points'=>0,'h2h'=>0]];
    $perGame = [];
    $pending = [];

    $upParticipantPts = $pdo->prepare('UPDATE game_participants SET points=? WHERE fixture_id=? AND game_number=? AND team_side=? AND player_slot=?');

    foreach ([1,2,3] as $g) {
      $left_total  = array_sum(array_map(fn($x)=>(int)$x['total'],  $games[$g]['left']  ?? []));
      $right_total = array_sum(array_map(fn($x)=>(int)$x['total'],  $games[$g]['right'] ?? []));
      $left_hc  = array_sum(array_map(fn($x)=>(int)$x['handicap'],  $games[$g]['left']  ?? []));
      $right_hc = array_sum(array_map(fn($x)=>(int)$x['handicap'],  $games[$g]['right'] ?? []));

      $left_game_pts=0.0; $right_game_pts=0.0;
      if ($leftAbsent && !$rightAbsent) {
        $right_game_pts = self::GAME_WIN_POINTS;
      } elseif ($rightAbsent && !$leftAbsent) {
        $left_game_pts  = self::GAME_WIN_POINTS;
      } else {
        if ($left_total > $right_total)      $left_game_pts  = self::GAME_WIN_POINTS;
        elseif ($left_total < $right_total)  $right_game_pts = self::GAME_WIN_POINTS;
        else {
          $ro = self::ro_fetch($pdo,$fixture_id,'game',$g);
          if (!$ro) $pending[] = ['scope'=>'game','game_number'=>$g];
          else { ($ro['winner_side']==='left') ? $left_game_pts=self::GAME_WIN_POINTS : $right_game_pts=self::GAME_WIN_POINTS; }
        }
      }

      $left_h2h_sum=0.0; $right_h2h_sum=0.0;
      foreach ([1,2,3] as $slotL) {
        $slotR = self::$H2H_PATTERN[$g][$slotL];
        $L = $games[$g]['left'][$slotL]  ?? null;
        $R = $games[$g]['right'][$slotR] ?? null;
        $Lpts=0.0; $Rpt=0.0;

        if ($L && $R) {
          $Lblind = (int)($L['is_blind']??0)===1;
          $Rblind = (int)($R['is_blind']??0)===1;

          if ($Lblind && !$Rblind) { $Rpt = self::H2H_WIN_POINTS; }
          elseif ($Rblind && !$Lblind) { $Lpts = self::H2H_WIN_POINTS; }
          else {
            $Lt=(int)$L['total']; $Rt=(int)$R['total'];
            if ($Lt > $Rt) $Lpts=self::H2H_WIN_POINTS;
            elseif ($Lt < $Rt) $Rpt=self::H2H_WIN_POINTS;
            else {
              $ro = self::ro_fetch($pdo,$fixture_id,'h2h',$g,$slotL,$slotR);
              if (!$ro) $pending[]=['scope'=>'h2h','game_number'=>$g,'left_slot'=>$slotL,'right_slot'=>$slotR];
              else { ($ro['winner_side']==='left') ? $Lpts=self::H2H_WIN_POINTS : $Rpt=self::H2H_WIN_POINTS; }
            }
          }
        }

        if ($L) $upParticipantPts->execute([$Lpts,$fixture_id,$g,'left',$slotL]);
        if ($R) $upParticipantPts->execute([$Rpt,$fixture_id,$g,'right',$slotR]);
        $left_h2h_sum += $Lpts; $right_h2h_sum += $Rpt;
      }

      $series['left']['pins']  += $left_total;
      $series['right']['pins'] += $right_total;
      $series['left']['team_points']  += $left_game_pts;
      $series['right']['team_points'] += $right_game_pts;
      $series['left']['h2h']  += $left_h2h_sum;
      $series['right']['h2h'] += $right_h2h_sum;

      $perGame[$g] = [
        'left'=> ['score'=>$left_total,'team_hc'=>$left_hc,'team_points'=>$left_game_pts,'h2h_points'=>$left_h2h_sum],
        'right'=>['score'=>$right_total,'team_hc'=>$right_hc,'team_points'=>$right_game_pts,'h2h_points'=>$right_h2h_sum],
      ];

      $up = $pdo->prepare('INSERT INTO games (fixture_id,game_number,left_score,right_score,left_points,right_points)
        VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE left_score=VALUES(left_score), right_score=VALUES(right_score),
        left_points=VALUES(left_points), right_points=VALUES(right_points)');
      $up->execute([$fixture_id,$g,$perGame[$g]['left']['score'],$perGame[$g]['right']['score'],$left_game_pts,$right_game_pts]);
    }

    $left_bonus=0.0; $right_bonus=0.0;
    if ($leftAbsent && !$rightAbsent) $right_bonus=self::SERIES_BONUS_POINTS;
    elseif ($rightAbsent && !$leftAbsent) $left_bonus=self::SERIES_BONUS_POINTS;
    else {
      if ($series['left']['pins'] > $series['right']['pins']) $left_bonus=self::SERIES_BONUS_POINTS;
      elseif ($series['left']['pins'] < $series['right']['pins']) $right_bonus=self::SERIES_BONUS_POINTS;
      else {
        $ro = self::ro_fetch($pdo,$fixture_id,'series',null,null,null);
        if (!$ro) $pending[]=['scope'=>'series'];
        else { ($ro['winner_side']==='left') ? $left_bonus=self::SERIES_BONUS_POINTS : $right_bonus=self::SERIES_BONUS_POINTS; }
      }
    }

    $left_team_points  = $series['left']['team_points']  + $left_bonus;
    $right_team_points = $series['right']['team_points'] + $right_bonus;

    if (self::INCLUDE_H2H_IN_TEAM_POINTS) {
      $left_team_points  += $series['left']['h2h'];
      $right_team_points += $series['right']['h2h'];
    }

    $rt = $pdo->prepare("
      INSERT INTO round_totals (
        fixture_id,
        left_total_pins,right_total_pins,
        left_total_points,right_total_points,
        left_bonus,right_bonus,
        left_h2h_points,right_h2h_points
      ) VALUES (?,?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
        left_total_pins=VALUES(left_total_pins),
        right_total_pins=VALUES(right_total_pins),
        left_total_points=VALUES(left_total_points),
        right_total_points=VALUES(right_total_points),
        left_bonus=VALUES(left_bonus),
        right_bonus=VALUES(right_bonus),
        left_h2h_points=VALUES(left_h2h_points),
        right_h2h_points=VALUES(right_h2h_points)
    ");
    $rt->execute([
      $fixture_id,
      $series['left']['pins'],$series['right']['pins'],
      $left_team_points,$right_team_points,
      $left_bonus,$right_bonus,
      $series['left']['h2h'],$series['right']['h2h']
    ]);

    return [
      'games'=>$perGame,
      'series'=>$series,
      'bonus'=>['left'=>$left_bonus,'right'=>$right_bonus],
      'totals'=>['left_points'=>$left_team_points,'right_points'=>$right_team_points],
      'rolloffs_pending'=>$pending
    ];
  }
}

