-- player_order: μοναδικό ανά fixture/side/slot
ALTER TABLE player_order
  ADD UNIQUE KEY uq_player_order (fixture_id, team_side, slot);

-- game_participants: μοναδικό ανά fixture/game/side/slot
ALTER TABLE game_participants
  ADD UNIQUE KEY uq_gp (fixture_id, game_number, team_side, player_slot);

-- games: μοναδικό ανά fixture/game_number
ALTER TABLE games
  ADD UNIQUE KEY uq_games (fixture_id, game_number);

-- round_totals: μοναδικό ανά fixture
ALTER TABLE round_totals
  ADD UNIQUE KEY uq_rt (fixture_id);

-- roll_offs: unified unique για series/game/h2h
ALTER TABLE roll_offs
  ADD UNIQUE KEY uq_ro (fixture_id, scope, game_number, left_slot, right_slot);

