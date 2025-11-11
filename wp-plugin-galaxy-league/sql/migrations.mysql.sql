-- Galaxy League Results — external MySQL schema

CREATE TABLE IF NOT EXISTS seasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  year SMALLINT NOT NULL,
  total_match_days TINYINT NOT NULL DEFAULT 7,
  points_division_every_n TINYINT DEFAULT 3,
  points_division_factor DECIMAL(4,2) DEFAULT 2.0,
  points_division_round ENUM(''down'',''nearest'',''up'') DEFAULT ''nearest''
);

CREATE TABLE IF NOT EXISTS match_days (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_id INT NOT NULL,
  idx TINYINT NOT NULL,
  label VARCHAR(50),
  date DATE,
  type ENUM(''regular'',''playoff'',''barrage'',''finals'') DEFAULT ''regular'',
  FOREIGN KEY (season_id) REFERENCES seasons(id)
);

CREATE TABLE IF NOT EXISTS lanes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  number TINYINT UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  logo VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS players (
  id INT AUTO_INCREMENT PRIMARY KEY,
  team_id INT NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  gender ENUM(''M'',''F'') NULL,
  base_hc SMALLINT DEFAULT 0,
  FOREIGN KEY (team_id) REFERENCES teams(id)
);

CREATE TABLE IF NOT EXISTS fixtures (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_day_id INT NOT NULL,
  lane_id INT NULL,
  left_team_id INT NOT NULL,
  right_team_id INT NOT NULL,
  left_side_absent TINYINT(1) DEFAULT 0,
  right_side_absent TINYINT(1) DEFAULT 0,
  FOREIGN KEY (match_day_id) REFERENCES match_days(id),
  FOREIGN KEY (left_team_id) REFERENCES teams(id),
  FOREIGN KEY (right_team_id) REFERENCES teams(id)
);

CREATE TABLE IF NOT EXISTS player_order (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fixture_id INT NOT NULL,
  team_side ENUM(''left'',''right'') NOT NULL,
  slot TINYINT NOT NULL,          -- 1..3 (1 = μικρότερος Μ.Ο., 3 = μεγαλύτερος Μ.Ο.)
  player_id INT NOT NULL,
  UNIQUE (fixture_id, team_side, slot),
  FOREIGN KEY (fixture_id) REFERENCES fixtures(id),
  FOREIGN KEY (player_id) REFERENCES players(id)
);

CREATE TABLE IF NOT EXISTS games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fixture_id INT NOT NULL,
  game_number TINYINT NOT NULL,   -- 1..3
  left_score SMALLINT DEFAULT 0,
  right_score SMALLINT DEFAULT 0,
  left_points DECIMAL(4,2) DEFAULT 0,
  right_points DECIMAL(4,2) DEFAULT 0,
  UNIQUE (fixture_id, game_number),
  FOREIGN KEY (fixture_id) REFERENCES fixtures(id)
);

CREATE TABLE IF NOT EXISTS game_participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fixture_id INT NOT NULL,
  game_number TINYINT NOT NULL,   -- 1..3
  team_side ENUM(''left'',''right'') NOT NULL,
  player_slot TINYINT NOT NULL,   -- 1..3
  player_id INT NOT NULL,
  scratch SMALLINT DEFAULT 0,
  handicap SMALLINT DEFAULT 0,
  total SMALLINT GENERATED ALWAYS AS (scratch + handicap) STORED,
  points DECIMAL(4,2) DEFAULT 0,  -- H2H points earned by this player for this game
  is_blind TINYINT(1) DEFAULT 0,
  UNIQUE (fixture_id, game_number, team_side, player_slot),
  FOREIGN KEY (fixture_id) REFERENCES fixtures(id),
  FOREIGN KEY (player_id) REFERENCES players(id)
);

CREATE TABLE IF NOT EXISTS hc_table (
  id INT AUTO_INCREMENT PRIMARY KEY,
  avg_min DECIMAL(5,2),
  avg_max DECIMAL(5,2),
  handicap SMALLINT NOT NULL
);

CREATE TABLE IF NOT EXISTS hc_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  player_id INT NOT NULL,
  match_day_id INT NOT NULL,
  handicap SMALLINT NOT NULL,
  UNIQUE (player_id, match_day_id),
  FOREIGN KEY (player_id) REFERENCES players(id),
  FOREIGN KEY (match_day_id) REFERENCES match_days(id)
);

CREATE TABLE IF NOT EXISTS round_totals (
  fixture_id INT PRIMARY KEY,
  left_total_pins INT DEFAULT 0,
  right_total_pins INT DEFAULT 0,
  left_total_points DECIMAL(5,2) DEFAULT 0,   -- team per-game + series bonus (χωρίς H2H)
  right_total_points DECIMAL(5,2) DEFAULT 0,
  left_bonus DECIMAL(4,2) DEFAULT 0,
  right_bonus DECIMAL(4,2) DEFAULT 0,
  left_h2h_points DECIMAL(5,2) DEFAULT 0,
  right_h2h_points DECIMAL(5,2) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS settings (
  sk VARCHAR(64) PRIMARY KEY,
  sv VARCHAR(255) NOT NULL
);

INSERT IGNORE INTO settings (sk, sv) VALUES
 ('hc_target_avg','180'),
 ('hc_factor','0.8'),
 ('hc_cap_male','30'),
 ('hc_cap_female','40');

CREATE TABLE IF NOT EXISTS points_adjustments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  team_id INT NOT NULL,
  match_day_id INT NOT NULL,
  delta_points DECIMAL(6,2) NOT NULL,
  note VARCHAR(255),
  UNIQUE KEY uq_team_md (team_id, match_day_id),
  FOREIGN KEY (team_id) REFERENCES teams(id),
  FOREIGN KEY (match_day_id) REFERENCES match_days(id)
);

CREATE TABLE IF NOT EXISTS roll_offs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fixture_id INT NOT NULL,
  scope ENUM(''game'',''series'',''h2h'') NOT NULL,
  game_number TINYINT NULL,         -- for game/h2h
  left_score SMALLINT NOT NULL,
  right_score SMALLINT NOT NULL,
  winner_side ENUM(''left'',''right'') NOT NULL,
  left_slot TINYINT NULL,           -- for h2h
  right_slot TINYINT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_fixture_scope (fixture_id, scope, game_number, left_slot, right_slot),
  FOREIGN KEY (fixture_id) REFERENCES fixtures(id)
);

DROP VIEW IF EXISTS team_standings;
CREATE VIEW team_standings AS
SELECT
  t.id AS team_id,
  t.name AS team_name,
  SUM(
    CASE WHEN f.left_team_id = t.id THEN (rt.left_total_points + rt.left_h2h_points) ELSE 0 END +
    CASE WHEN f.right_team_id = t.id THEN (rt.right_total_points + rt.right_h2h_points) ELSE 0 END
  )
  + COALESCE((
      SELECT SUM(pa.delta_points)
      FROM points_adjustments pa
      WHERE pa.team_id = t.id
    ),0) AS total_points,
  SUM(
    CASE WHEN f.left_team_id = t.id THEN rt.left_total_pins ELSE 0 END +
    CASE WHEN f.right_team_id = t.id THEN rt.right_total_pins ELSE 0 END
  ) AS total_pins
FROM teams t
LEFT JOIN fixtures f ON f.left_team_id = t.id OR f.right_team_id = t.id
LEFT JOIN round_totals rt ON rt.fixture_id = f.id
GROUP BY t.id, t.name
ORDER BY total_points DESC, total_pins DESC;
