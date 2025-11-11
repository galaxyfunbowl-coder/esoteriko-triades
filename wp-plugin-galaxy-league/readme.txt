=== Galaxy League Results ===
Requires at least: 6.0
Stable tag: 0.2.0

Shortcodes:
  [league_standings] – Public standings table.

Setup:
1) Run sql/migrations.mysql.sql on your EXTERNAL MySQL.
2) In WP Admin → Galaxy League → fill DB credentials.
3) Build assets:
   cd assets
   npm install
   npm run build
4) Activate plugin. Create a page and add [league_standings].

Admin Scores:
- WP Admin → Galaxy League → Scores
- Select Season → Match Day → Fixture
- Optional: set Left/Right Absent
- Enter per-player scratch for G1–G3, toggle Blind if needed (Blind: avg−15, no HC, cannot claim H2H point)
- Save & Recompute → If any tie exists, Pending Roll-offs appear (H2H, Game, Series)
- Fill roll-offs; recompute clears pending list

Rules implemented:
- 3 games per fixture, H2H rotation per game (1-1,2-2,3-3 → 1-2,2-3,3-1 → 1-3,2-1,3-2)
- Points: H2H=1, Team per game win=3, Series bonus=3 (total 21)
- Roll-off on ALL ties (H2H, game, series)
- Blind (avg−15, HC=0, cannot claim H2H)
- Whole side absence → opponent gets all team game wins + series bonus
- HC after each match day: 80% to target 180, caps 30 (M) / 40 (F); first MD uses manual hc_history
- Points division every 3 MDs via points_adjustments (config in seasons)
- Finals: Top-4 direct + 2 from barrage (5–8 seeds)

Extra REST:
- GET /glr/v1/final-standings
- GET /glr/v1/playoff-qualifiers
- POST /glr/v1/create-barrage (season_id, date, label)
- POST /glr/v1/recalc-hc?match_day_id=...
- POST /glr/v1/apply-division?season_id=...&idx=3

Τελικές οδηγίες (εσύ):

Τρέξε το migrations.mysql.sql στη νέα MySQL.

Άνοιξε WP → Galaxy League → βάλε host/db/user/pass.

Στον φάκελο assets: npm install && npm run build.

Ενεργοποίησε το plugin. Βάλε [league_standings] σε σελίδα.

Admin → Scores: πέρασε πρόγραμμα/participants (player_order), βάζε σκορ, blind/absence.

Μετά από κάθε αγωνιστική: POST /glr/v1/recalc-hc?match_day_id=... (ή φτιάχνουμε κουμπί).

Κάθε 3 αγωνιστικές: POST /glr/v1/apply-division?season_id=...&idx=3|6.

Τέλος regular: GET /glr/v1/playoff-qualifiers → POST /glr/v1/create-barrage.

Αν θες, στο επόμενο μήνυμα σου δίνω έτοιμο shortcode [league_match id=".."] για public προβολή ενός αγώνα (grid όπως στο Excel: H2H ανά game, team HC, points, roll-offs).
