-- Run this on the NEW external MySQL database you will use for the league.
-- Minimal schema to get standings running (you can extend later).

CREATE TABLE seasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  year SMALLINT NOT NULL,
  total_match_days TINYINT NOT NULL
);

CREATE TABLE match_days (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_id INT NOT NULL,
  idx TINYINT NOT NULL,
  label VARCHAR(50),
  date DATE,
  type ENUM('regular','playoff','barrage') DEFAULT 'regular',
  FOREIGN KEY (season_id) REFERENCES seasons(id)
);

CREATE TABLE teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  logo VARCHAR(255)
);

CREATE TABLE players (
  id INT AUTO_INCREMENT PRIMARY KEY,
  team_id INT NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  gender ENUM('M','F') NULL,
  base_hc SMALLINT DEFAULT 0,
  FOREIGN KEY (team_id) REFERENCES teams(id)
);

CREATE TABLE fixtures (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_day_id INT NOT NULL,
  lane_id INT NULL,
  left_team_id INT NOT NULL,
  right_team_id INT NOT NULL,
  FOREIGN KEY (match_day_id) REFERENCES match_days(id),
  FOREIGN KEY (left_team_id) REFERENCES teams(id),
  FOREIGN KEY (right_team_id) REFERENCES teams(id)
);

CREATE TABLE games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fixture_id INT NOT NULL,
  game_number TINYINT NOT NULL, -- 1..3
  left_score SMALLINT DEFAULT 0,
  right_score SMALLINT DEFAULT 0,
  left_points DECIMAL(4,2) DEFAULT 0,
  right_points DECIMAL(4,2) DEFAULT 0,
  UNIQUE (fixture_id, game_number),
  FOREIGN KEY (fixture_id) REFERENCES fixtures(id)
);

-- Optional: per-player lines; keep it for later if needed
-- CREATE TABLE game_participants (...)

CREATE VIEW team_standings AS
SELECT
  t.id AS team_id,
  t.name AS team_name,
  SUM(g.left_points) AS points_as_left,
  SUM(g.right_points) AS points_as_right,
  SUM(CASE WHEN f.left_team_id = t.id THEN g.left_points ELSE 0 END
    + CASE WHEN f.right_team_id = t.id THEN g.right_points ELSE 0 END) AS total_points,
  SUM(CASE WHEN f.left_team_id = t.id THEN g.left_score ELSE 0 END
    + CASE WHEN f.right_team_id = t.id THEN g.right_score ELSE 0 END) AS total_pins
FROM teams t
LEFT JOIN fixtures f ON f.left_team_id = t.id OR f.right_team_id = t.id
LEFT JOIN games g ON g.fixture_id = f.id
GROUP BY t.id, t.name
ORDER BY total_points DESC, total_pins DESC;
