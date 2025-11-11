<?php
if (!defined('ABSPATH')) exit;

class GLR_Api {
  public static function register() {
    register_rest_route('glr/v1','/seasons',['methods'=>'GET','callback'=>['GLR_Api','seasons'],'permission_callback'=>'__return_true']);
    register_rest_route('glr/v1','/match-days',['methods'=>'GET','callback'=>['GLR_Api','match_days'],'permission_callback'=>'__return_true']);
    register_rest_route('glr/v1','/fixtures',['methods'=>'GET','callback'=>['GLR_Api','fixtures'],'permission_callback'=>'__return_true']);

    register_rest_route('glr/v1','/participants',['methods'=>'GET','callback'=>['GLR_Api','participants'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/submit-scores',['methods'=>'POST','callback'=>['GLR_Api','submit_scores'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/recompute',['methods'=>'POST','callback'=>['GLR_Api','recompute'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/rolloffs',['methods'=>'GET','callback'=>['GLR_Api','rolloffs_list'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/rolloffs',['methods'=>'POST','callback'=>['GLR_Api','rolloffs_save'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/fixture-absence',['methods'=>'POST','callback'=>['GLR_Api','fixture_absence'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/suggest-order',['methods'=>'GET','callback'=>['GLR_Api','suggest_order'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/recalc-hc',['methods'=>'POST','callback'=>['GLR_Api','recalc_hc'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/apply-division',['methods'=>'POST','callback'=>['GLR_Api','apply_division'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/final-standings',['methods'=>'GET','callback'=>['GLR_Api','final_standings'],'permission_callback'=>'__return_true']);
    register_rest_route('glr/v1','/playoff-qualifiers',['methods'=>'GET','callback'=>['GLR_Api','playoff_qualifiers'],'permission_callback'=>'__return_true']);
    register_rest_route('glr/v1','/create-barrage',['methods'=>'POST','callback'=>['GLR_Api','create_barrage'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/teams',['methods'=>'GET','callback'=>['GLR_Api','teams_list'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/teams',['methods'=>'POST','callback'=>['GLR_Api','teams_upsert'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/teams/(?P<id>\d+)',['methods'=>'DELETE','callback'=>['GLR_Api','teams_delete'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/players',['methods'=>'GET','callback'=>['GLR_Api','players_list'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/players',['methods'=>'POST','callback'=>['GLR_Api','players_upsert'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/players/(?P<id>\d+)',['methods'=>'DELETE','callback'=>['GLR_Api','players_delete'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/seasons',['methods'=>'POST','callback'=>['GLR_Api','seasons_upsert'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/match-days',['methods'=>'POST','callback'=>['GLR_Api','match_days_upsert'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/match-days/(?P<id>\d+)',['methods'=>'DELETE','callback'=>['GLR_Api','match_days_delete'],'permission_callback'=>function(){return current_user_can('manage_options');}]);

    register_rest_route('glr/v1','/fixtures-manage',['methods'=>'GET','callback'=>['GLR_Api','fixtures_manage'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/fixtures',['methods'=>'POST','callback'=>['GLR_Api','fixtures_upsert'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/player-order',['methods'=>'GET','callback'=>['GLR_Api','order_get'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/player-order',['methods'=>'POST','callback'=>['GLR_Api','order_set'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/hc/list',['methods'=>'GET','callback'=>['GLR_Api','hc_list'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('glr/v1','/hc/save',['methods'=>'POST','callback'=>['GLR_Api','hc_save'],'permission_callback'=>function(){return current_user_can('manage_options');}]);
  }

  public static function seasons() {
    $pdo=GLR_DB::pdo();
    $rows=$pdo->query('SELECT id,name,year,total_match_days FROM seasons ORDER BY year DESC, id DESC')->fetchAll();
    return ['ok'=>true,'rows'=>$rows];
  }

  public static function match_days(\WP_REST_Request $r) {
    $pdo=GLR_DB::pdo(); $season_id=(int)$r->get_param('season_id');
    $st=$pdo->prepare('SELECT id,season_id,idx,label,date,type FROM match_days WHERE season_id=? ORDER BY idx ASC'); $st->execute([$season_id]);
    return ['ok'=>true,'rows'=>$st->fetchAll()];
  }

  public static function fixtures(\WP_REST_Request $r) {
    $pdo=GLR_DB::pdo(); $match_day_id=(int)$r->get_param('match_day_id');
    $sql='SELECT f.id,f.match_day_id,f.lane_id,f.left_team_id,f.right_team_id,tl.name AS left_team,tr.name AS right_team,
                 f.left_side_absent,f.right_side_absent
          FROM fixtures f
          JOIN teams tl ON tl.id=f.left_team_id
          JOIN teams tr ON tr.id=f.right_team_id
          WHERE f.match_day_id=? ORDER BY f.id ASC';
    $st=$pdo->prepare($sql); $st->execute([$match_day_id]);
    return ['ok'=>true,'rows'=>$st->fetchAll()];
  }

  public static function participants(\WP_REST_Request $r) {
    $pdo=GLR_DB::pdo(); $fixture_id=(int)$r->get_param('fixture_id');
    $md = $pdo->prepare('SELECT match_day_id FROM fixtures WHERE id=?'); $md->execute([$fixture_id]);
    $match_day_id=(int)$md->fetchColumn();

    $sql='SELECT team_side, slot, po.player_id, p.full_name
          FROM player_order po JOIN players p ON p.id=po.player_id
          WHERE po.fixture_id=? ORDER BY team_side, slot';
    $st=$pdo->prepare($sql); $st->execute([$fixture_id]); $rows=$st->fetchAll();

    foreach($rows as &$x){
      $x['handicap']=GLR_Logic::get_handicap($pdo,(int)$x['player_id'],$match_day_id);
    }
    return ['ok'=>true,'rows'=>$rows,'match_day_id'=>$match_day_id];
  }

  public static function submit_scores(\WP_REST_Request $req) {
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params();
    $fixture_id=(int)($b['fixture_id']??0); $match_day_id=(int)($b['match_day_id']??0);
    $lines=$b['lines']??[];
    if(!$fixture_id||!$match_day_id||!is_array($lines)||!count($lines)) throw new Exception('Missing payload');

    $pdo->beginTransaction();
    $up=$pdo->prepare('INSERT INTO game_participants (fixture_id,game_number,team_side,player_slot,player_id,scratch,handicap,is_blind)
      VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE scratch=VALUES(scratch),handicap=VALUES(handicap),is_blind=VALUES(is_blind)');

    foreach($lines as $L){
      $is_blind = !empty($L['is_blind']) ? 1 : 0;
      $hc = $is_blind ? 0 : GLR_Logic::get_handicap($pdo,(int)$L['player_id'],$match_day_id);
      $up->execute([
        (int)$L['fixture_id'] ?: $fixture_id,
        (int)$L['game_number'],
        $L['team_side'],
        (int)$L['player_slot'],
        (int)$L['player_id'],
        (int)($L['scratch']??0),
        (int)$hc,
        $is_blind
      ]);
    }

    $res = GLR_Logic::recompute_fixture($pdo,$fixture_id,$match_day_id);
    $pdo->commit();
    return ['ok'=>true,'result'=>$res];
  }

  public static function recompute(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params();
    $fixture_id=(int)($b['fixture_id']??0);
    $md=$pdo->prepare('SELECT match_day_id FROM fixtures WHERE id=?'); $md->execute([$fixture_id]);
    $match_day_id=(int)$md->fetchColumn();
    $res=GLR_Logic::recompute_fixture($pdo,$fixture_id,$match_day_id);
    return ['ok'=>true,'result'=>$res];
  }

  public static function rolloffs_list(\WP_REST_Request $r){
    $pdo=GLR_DB::pdo(); $fixture_id=(int)$r->get_param('fixture_id');
    $st=$pdo->prepare('SELECT * FROM roll_offs WHERE fixture_id=? ORDER BY scope, game_number, id'); $st->execute([$fixture_id]);
    return ['ok'=>true,'rows'=>$st->fetchAll()];
  }

  public static function rolloffs_save(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params();
    $fixture_id=(int)($b['fixture_id']??0);
    $scope=$b['scope']??''; $game=isset($b['game_number'])?(int)$b['game_number']:null;
    $left_slot=$b['left_slot']??null; $right_slot=$b['right_slot']??null;
    $left_score=(int)($b['left_score']??0); $right_score=(int)($b['right_score']??0);
    $winner_side=$b['winner_side']??'';
    if(!$fixture_id || !in_array($scope,['game','series','h2h'],true)) throw new Exception('Invalid payload');
    if(!in_array($winner_side,['left','right'],true)) throw new Exception('Invalid winner_side');

    $sql='INSERT INTO roll_offs (fixture_id,scope,game_number,left_score,right_score,winner_side,left_slot,right_slot)
          VALUES (?,?,?,?,?,?,?,?)
          ON DUPLICATE KEY UPDATE left_score=VALUES(left_score), right_score=VALUES(right_score), winner_side=VALUES(winner_side)';
    $st=$pdo->prepare($sql); $st->execute([$fixture_id,$scope,$game,$left_score,$right_score,$winner_side,$left_slot,$right_slot]);

    $md=$pdo->prepare('SELECT match_day_id FROM fixtures WHERE id=?'); $md->execute([$fixture_id]);
    $match_day_id=(int)$md->fetchColumn();
    $res=GLR_Logic::recompute_fixture($pdo,$fixture_id,$match_day_id);
    return ['ok'=>true,'result'=>$res];
  }

  public static function fixture_absence(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params();
    $fixture_id=(int)$b['fixture_id'];
    $left  = !empty($b['left_side_absent'])?1:0;
    $right = !empty($b['right_side_absent'])?1:0;
    $st=$pdo->prepare('UPDATE fixtures SET left_side_absent=?, right_side_absent=? WHERE id=?');
    $st->execute([$left,$right,$fixture_id]);
    return ['ok'=>true];
  }

  public static function suggest_order(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $fixture_id=(int)$req->get_param('fixture_id');
    $md=$pdo->prepare('SELECT match_day_id,left_team_id,right_team_id FROM fixtures WHERE id=?'); $md->execute([$fixture_id]);
    $fx=$md->fetch(); if(!$fx) throw new Exception('Fixture not found');
    $match_day_id=(int)$fx['match_day_id'];

    $helper=function($team_id) use($pdo,$match_day_id){
      $q=$pdo->prepare("
        SELECT p.id, p.full_name,
               (SELECT SUM(gp.scratch)/NULLIF(COUNT(*),0)
                FROM game_participants gp JOIN fixtures f2 ON f2.id=gp.fixture_id
                WHERE gp.player_id=p.id AND gp.is_blind=0
                  AND f2.match_day_id IN (
                    SELECT id FROM match_days WHERE season_id=(SELECT season_id FROM match_days WHERE id=?)
                    AND idx < (SELECT idx FROM match_days WHERE id=?)
                  )
               ) AS avg
        FROM players p WHERE p.team_id=? ORDER BY avg DESC NULLS LAST LIMIT 3
      ");
      $q->execute([$match_day_id,$match_day_id,$team_id]);
      return $q->fetchAll();
    };

    $left=$helper((int)$fx['left_team_id']);
    $right=$helper((int)$fx['right_team_id']);

    $map=function($arr){ return [
      ['slot'=>3,'player'=>$arr[0]??null],
      ['slot'=>2,'player'=>$arr[1]??null],
      ['slot'=>1,'player'=>$arr[2]??null],
    ];};

    return ['ok'=>true,'left'=>$map($left),'right'=>$map($right)];
  }

  public static function recalc_hc(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $md_id=(int)$req->get_param('match_day_id');
    if(!$md_id) throw new Exception('match_day_id required');

    $q=$pdo->prepare('SELECT season_id, idx FROM match_days WHERE id=?'); $q->execute([$md_id]);
    $cur=$q->fetch(); $season_id=(int)$cur['season_id']; $idx=(int)$cur['idx'];

    $q2=$pdo->prepare('SELECT id FROM match_days WHERE season_id=? AND idx=?+1');
    $q2->execute([$season_id,$idx]); $next_id=$q2->fetchColumn();
    if(!$next_id) return ['ok'=>true,'note'=>'No next match day'];

    $avgSql='
      SELECT gp.player_id, SUM(gp.scratch) AS pins, COUNT(*) AS games_played
      FROM game_participants gp JOIN fixtures f ON f.id=gp.fixture_id
      WHERE gp.is_blind=0 AND f.match_day_id IN (
        SELECT id FROM match_days WHERE season_id=? AND idx <= ?
      )
      GROUP BY gp.player_id';
    $st=$pdo->prepare($avgSql); $st->execute([$season_id,$idx]); $rows=$st->fetchAll();

    $ins=$pdo->prepare('INSERT INTO hc_history (player_id, match_day_id, handicap)
      VALUES (?,?,?) ON DUPLICATE KEY UPDATE handicap=VALUES(handicap)');

    foreach($rows as $r){
      $player_id=(int)$r['player_id'];
      $avg = ($r['games_played']>0) ? ($r['pins']/$r['games_played']) : 0.0;
      $gq=$pdo->prepare('SELECT gender FROM players WHERE id=?'); $gq->execute([$player_id]);
      $gender=$gq->fetchColumn()?:null;
      $hc=GLR_Logic::compute_handicap_by_avg($pdo,(float)$avg,$gender);
      $ins->execute([$player_id,(int)$next_id,$hc]);
    }
    return ['ok'=>true,'next_match_day_id'=>(int)$next_id,'players'=>count($rows)];
  }

  public static function apply_division(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo();
    $season_id=(int)$req->get_param('season_id'); $idx=(int)$req->get_param('idx');
    if(!$season_id||!$idx) throw new Exception('season_id & idx required');

    $st=$pdo->prepare('SELECT points_division_factor, points_division_round FROM seasons WHERE id=?');
    $st->execute([$season_id]); $s=$st->fetch();
    $factor=(float)$s['points_division_factor']; $round=$s['points_division_round'];

    $mds=$pdo->prepare('SELECT id FROM match_days WHERE season_id=? AND idx <= ? ORDER BY idx');
    $mds->execute([$season_id,$idx]); $md_ids=array_column($mds->fetchAll(),'id');
    if(!$md_ids) return ['ok'=>true,'note'=>'no match days yet'];
    $in = implode(',', array_fill(0,count($md_ids),'?'));

    $sql = "
      SELECT t.id AS team_id,
        SUM(CASE WHEN f.left_team_id=t.id THEN (rt.left_total_points+rt.left_h2h_points) ELSE 0 END +
            CASE WHEN f.right_team_id=t.id THEN (rt.right_total_points+rt.right_h2h_points) ELSE 0 END) AS pts
      FROM teams t
      JOIN fixtures f ON f.left_team_id=t.id OR f.right_team_id=t.id
      JOIN round_totals rt ON rt.fixture_id=f.id
      WHERE f.match_day_id IN ($in)
      GROUP BY t.id";
    $st=$pdo->prepare($sql); $st->execute($md_ids); $rows=$st->fetchAll();

    $cur_md_id = end($md_ids);
    $ins=$pdo->prepare('INSERT INTO points_adjustments (team_id, match_day_id, delta_points, note)
                        VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE delta_points=VALUES(delta_points), note=VALUES(note)');

    foreach($rows as $r){
      $old=(float)$r['pts']; $new=$old/$factor;
      if($round==='down') $new=floor($new+1e-9);
      elseif($round==='up') $new=ceil($new-1e-9);
      else $new=round($new,0);
      $delta=$new-$old;
      $ins->execute([(int)$r['team_id'], $cur_md_id, $delta, "division idx=$idx factor=$factor"]);
    }
    return ['ok'=>true,'adjusted'=>count($rows),'match_day_id'=>$cur_md_id];
  }

  public static function final_standings() {
    $pdo=GLR_DB::pdo();
    $rows=$pdo->query("
      SELECT t.id, t.name,
             SUM(CASE WHEN f.left_team_id=t.id THEN rt.left_total_pins ELSE 0 END +
                 CASE WHEN f.right_team_id=t.id THEN rt.right_total_pins ELSE 0 END) AS total_pins,
             SUM(CASE WHEN f.left_team_id=t.id THEN (rt.left_total_points+rt.left_h2h_points) ELSE 0 END +
                 CASE WHEN f.right_team_id=t.id THEN (rt.right_total_points+rt.right_h2h_points) ELSE 0 END) +
             COALESCE((SELECT SUM(pa.delta_points) FROM points_adjustments pa WHERE pa.team_id=t.id),0) AS total_points
      FROM teams t
      LEFT JOIN fixtures f ON f.left_team_id=t.id OR f.right_team_id=t.id
      LEFT JOIN round_totals rt ON rt.fixture_id=f.id
      GROUP BY t.id, t.name
    ")->fetchAll();

    foreach($rows as &$r){
      $q=$pdo->prepare('SELECT MAX(ps.average) FROM player_stats ps JOIN players p ON p.id=ps.player_id WHERE p.team_id=?');
      @$q->execute([$r['id']]); $r['best_player_avg']=(float)($q->fetchColumn() ?: 0);
    }

    usort($rows,function($a,$b){
      if ($a['total_points'] != $b['total_points']) return $b['total_points'] <=> $a['total_points'];
      if ($a['total_pins']   != $b['total_pins'])   return $b['total_pins']   <=> $a['total_pins'];
      return $b['best_player_avg'] <=> $a['best_player_avg'];
    });

    return ['ok'=>true,'rows'=>$rows];
  }

  public static function playoff_qualifiers($r=null){
    $stand = self::final_standings(); if ($stand instanceof \WP_REST_Response) return $stand;
    $rows = $stand['rows'];
    $direct = array_slice($rows,0,4);
    $barrage = array_slice($rows,4,4);
    return ['ok'=>true,'direct'=>$direct,'barrage'=>$barrage];
  }

  public static function create_barrage(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params();
    $season_id=(int)($b['season_id']??0); $date_str=$b['date']??null; $label=$b['label']??'ΜΠΑΡΑΖ Τετάρτη 19:00';
    if(!$season_id||!$date_str) throw new Exception('season_id/date required');

    $q=$pdo->prepare('SELECT COALESCE(MAX(idx),0)+1 FROM match_days WHERE season_id=?'); $q->execute([$season_id]); $next_idx=(int)$q->fetchColumn();
    $insMD=$pdo->prepare('INSERT INTO match_days(season_id,idx,label,date,type) VALUES (?,?,?,?, "barrage")');
    $insMD->execute([$season_id,$next_idx,$label,$date_str]); $match_day_id=(int)$pdo->lastInsertId();

    $qs=self::playoff_qualifiers(); if($qs instanceof \WP_REST_Response) return $qs; $barr=$qs['barrage'];
    if(count($barr)<4) throw new Exception('Not enough barrage teams');
    $seed5=$barr[0]['id']; $seed6=$barr[1]['id']; $seed7=$barr[2]['id']; $seed8=$barr[3]['id'];

    $insFx=$pdo->prepare('INSERT INTO fixtures(match_day_id,lane_id,left_team_id,right_team_id) VALUES (?,?,?,?)');
    $insFx->execute([$match_day_id,1,$seed5,$seed8]); $fx1=(int)$pdo->lastInsertId();
    $insFx->execute([$match_day_id,2,$seed6,$seed7]); $fx2=(int)$pdo->lastInsertId();

    return ['ok'=>true,'match_day_id'=>$match_day_id,'fixtures'=>[$fx1,$fx2]];
  }

  public static function teams_list(){
    $pdo=GLR_DB::pdo();
    $rows=$pdo->query('SELECT id,name,logo FROM teams ORDER BY name')->fetchAll();
    return ['ok'=>true,'rows'=>$rows];
  }

  public static function teams_upsert(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params(); $id=(int)($b['id']??0);
    $name = isset($b['name']) ? trim($b['name']) : '';
    $logo = $b['logo'] ?? null;
    if ($name === '') throw new Exception('Team name required');
    if ($id) {
      $st=$pdo->prepare('UPDATE teams SET name=?, logo=? WHERE id=?');
      $st->execute([$name,$logo,$id]);
    } else {
      $st=$pdo->prepare('INSERT INTO teams(name,logo) VALUES(?,?)');
      $st->execute([$name,$logo]);
      $id=(int)$pdo->lastInsertId();
    }
    return ['ok'=>true,'id'=>$id];
  }

  public static function teams_delete(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $id=(int)$req->get_param('id');
    if(!$id) throw new Exception('id required');
    $pdo->prepare('DELETE FROM teams WHERE id=?')->execute([$id]);
    return ['ok'=>true];
  }

  public static function players_list(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $team_id=(int)$req->get_param('team_id');
    if ($team_id) {
      $st=$pdo->prepare('SELECT id,team_id,full_name,gender,base_hc FROM players WHERE team_id=? ORDER BY full_name');
      $st->execute([$team_id]);
      $rows=$st->fetchAll();
    } else {
      $rows=$pdo->query('SELECT id,team_id,full_name,gender,base_hc FROM players ORDER BY full_name')->fetchAll();
    }
    return ['ok'=>true,'rows'=>$rows];
  }

  public static function players_upsert(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params(); $id=(int)($b['id']??0);
    $team_id = (int)($b['team_id']??0);
    $full_name = isset($b['full_name']) ? trim($b['full_name']) : '';
    $gender = $b['gender'] ?? null;
    $base_hc = (int)($b['base_hc']??0);
    if(!$team_id || $full_name==='') throw new Exception('team_id and full_name required');
    if ($id) {
      $st=$pdo->prepare('UPDATE players SET team_id=?, full_name=?, gender=?, base_hc=? WHERE id=?');
      $st->execute([$team_id,$full_name,$gender?:null,$base_hc,$id]);
    } else {
      $st=$pdo->prepare('INSERT INTO players(team_id,full_name,gender,base_hc) VALUES (?,?,?,?)');
      $st->execute([$team_id,$full_name,$gender?:null,$base_hc]);
      $id=(int)$pdo->lastInsertId();
    }
    return ['ok'=>true,'id'=>$id];
  }

  public static function players_delete(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $id=(int)$req->get_param('id');
    if(!$id) throw new Exception('id required');
    $pdo->prepare('DELETE FROM players WHERE id=?')->execute([$id]);
    return ['ok'=>true];
  }

  public static function seasons_upsert(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params(); $id=(int)($b['id']??0);
    $name = isset($b['name']) ? trim($b['name']) : '';
    $year = (int)($b['year']??0);
    $total = (int)($b['total_match_days']??0);
    if($name===''||!$year||!$total) throw new Exception('name/year/total required');
    if ($id) {
      $st=$pdo->prepare('UPDATE seasons SET name=?, year=?, total_match_days=? WHERE id=?');
      $st->execute([$name,$year,$total,$id]);
    } else {
      $st=$pdo->prepare('INSERT INTO seasons(name,year,total_match_days) VALUES (?,?,?)');
      $st->execute([$name,$year,$total]);
      $id=(int)$pdo->lastInsertId();
    }
    return ['ok'=>true,'id'=>$id];
  }

  public static function match_days_upsert(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params(); $id=(int)($b['id']??0);
    $season_id=(int)($b['season_id']??0);
    $idx=(int)($b['idx']??0);
    $label=$b['label']??null;
    $date=$b['date']??null;
    $type=$b['type']??'regular';
    if(!$season_id||!$idx) throw new Exception('season_id/idx required');
    if ($id) {
      $st=$pdo->prepare('UPDATE match_days SET season_id=?, idx=?, label=?, date=?, type=? WHERE id=?');
      $st->execute([$season_id,$idx,$label,$date,$type?:'regular',$id]);
    } else {
      $st=$pdo->prepare('INSERT INTO match_days(season_id,idx,label,date,type) VALUES (?,?,?,?,?)');
      $st->execute([$season_id,$idx,$label,$date,$type?:'regular']);
      $id=(int)$pdo->lastInsertId();
    }
    return ['ok'=>true,'id'=>$id];
  }

  public static function match_days_delete(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $id=(int)$req->get_param('id');
    if(!$id) throw new Exception('id required');
    $pdo->prepare('DELETE FROM match_days WHERE id=?')->execute([$id]);
    return ['ok'=>true];
  }

  public static function fixtures_manage(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $season_id=(int)$req->get_param('season_id');
    if(!$season_id) throw new Exception('season_id required');
    $sql='SELECT f.id, f.match_day_id, md.idx AS md_idx, tl.name AS left_team, tr.name AS right_team, f.lane_id
          FROM fixtures f JOIN match_days md ON md.id=f.match_day_id
          JOIN teams tl ON tl.id=f.left_team_id JOIN teams tr ON tr.id=f.right_team_id
          WHERE md.season_id=? ORDER BY md.idx, f.id';
    $st=$pdo->prepare($sql); $st->execute([$season_id]);
    return ['ok'=>true,'rows'=>$st->fetchAll()];
  }

  public static function fixtures_upsert(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params(); $id=(int)($b['id']??0);
    $match_day_id = (int)($b['match_day_id']??0);
    $lane_id = isset($b['lane_id']) && $b['lane_id'] !== '' ? (int)$b['lane_id'] : null;
    $left_team_id = (int)($b['left_team_id']??0);
    $right_team_id = (int)($b['right_team_id']??0);
    if(!$match_day_id||!$left_team_id||!$right_team_id) throw new Exception('fixture fields missing');
    $check=$pdo->prepare('SELECT COUNT(*) FROM match_days WHERE id=?');
    $check->execute([$match_day_id]);
    if(!$check->fetchColumn()) throw new Exception('Το match day δεν υπάρχει. Βεβαιώσου ότι έχεις ολοκληρώσει το Setup (Step 1).');
    $teamsCheck=$pdo->prepare('SELECT id FROM teams WHERE id IN (?,?)');
    $teamsCheck->execute([$left_team_id,$right_team_id]);
    $found=$teamsCheck->fetchAll(\PDO::FETCH_COLUMN);
    if(!in_array($left_team_id,$found,true) || !in_array($right_team_id,$found,true)) throw new Exception('Μία από τις ομάδες δεν υπάρχει πλέον.');
    if ($id) {
      $st=$pdo->prepare('UPDATE fixtures SET match_day_id=?, lane_id=?, left_team_id=?, right_team_id=? WHERE id=?');
      try {
        $st->execute([$match_day_id,$lane_id,$left_team_id,$right_team_id,$id]);
      } catch(\PDOException $e) {
        throw new Exception('Αδυναμία ενημέρωσης fixture: '.$e->getMessage());
      }
    } else {
      $st=$pdo->prepare('INSERT INTO fixtures(match_day_id,lane_id,left_team_id,right_team_id) VALUES (?,?,?,?)');
      try {
        $st->execute([$match_day_id,$lane_id,$left_team_id,$right_team_id]);
        $id=(int)$pdo->lastInsertId();
      } catch(\PDOException $e) {
        throw new Exception('Αδυναμία δημιουργίας fixture: '.$e->getMessage());
      }
    }
    return ['ok'=>true,'id'=>$id];
  }

  public static function order_get(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $fixture_id=(int)$req->get_param('fixture_id');
    if(!$fixture_id) throw new Exception('fixture_id required');
    $st=$pdo->prepare('SELECT team_side,slot,player_id FROM player_order WHERE fixture_id=? ORDER BY team_side,slot');
    $st->execute([$fixture_id]);
    return ['ok'=>true,'rows'=>$st->fetchAll()];
  }

  public static function order_set(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params();
    $fixture_id=(int)($b['fixture_id']??0);
    $rows=is_array($b['rows']??null)?$b['rows']:[];
    if(!$fixture_id) throw new Exception('fixture_id required');
    $up=$pdo->prepare('INSERT INTO player_order(fixture_id,team_side,slot,player_id) VALUES (?,?,?,?)
                       ON DUPLICATE KEY UPDATE player_id=VALUES(player_id)');
    foreach($rows as $row){
      $team_side = $row['team_side'] ?? null;
      $slot = isset($row['slot']) ? (int)$row['slot'] : 0;
      $player_id = isset($row['player_id']) ? (int)$row['player_id'] : 0;
      if(!$team_side || !$slot || !$player_id) continue;
      $up->execute([$fixture_id,$team_side,$slot,$player_id]);
    }
    return ['ok'=>true];
  }

  public static function hc_list(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $match_day_id=(int)$req->get_param('match_day_id');
    if(!$match_day_id) throw new Exception('match_day_id required');
    $sql="SELECT p.id AS player_id, p.full_name, p.team_id, t.name AS team_name,
                 COALESCE(h.handicap,0) AS handicap, p.gender
          FROM players p
          JOIN teams t ON t.id=p.team_id
          LEFT JOIN hc_history h ON h.player_id=p.id AND h.match_day_id=?
          ORDER BY t.name, p.full_name";
    $st=$pdo->prepare($sql); $st->execute([$match_day_id]);
    return ['ok'=>true,'rows'=>$st->fetchAll()];
  }

  public static function hc_save(\WP_REST_Request $req){
    $pdo=GLR_DB::pdo(); $b=$req->get_json_params();
    $match_day_id=(int)($b['match_day_id']??0);
    $rows=is_array($b['rows']??null)?$b['rows']:[];
    if(!$match_day_id) throw new Exception('match_day_id required');
    $ins=$pdo->prepare('INSERT INTO hc_history (player_id, match_day_id, handicap)
                        VALUES (?,?,?) ON DUPLICATE KEY UPDATE handicap=VALUES(handicap)');
    foreach($rows as $row){
      $player_id=(int)($row['player_id']??0);
      $handicap=(int)($row['handicap']??0);
      if(!$player_id) continue;
      $ins->execute([$player_id,$match_day_id,$handicap]);
    }
    return ['ok'=>true,'saved'=>count($rows)];
  }

  private static function err($e){ return new \WP_REST_Response(['ok'=>false,'error'=>$e->getMessage()],500); }
}
add_action('rest_api_init',['GLR_Api','register']);

