=== Galaxy League Results ===
Contributors: galaxy
Requires at least: 6.0
Tested up to: 6.x
Stable tag: 0.1.0

Shortcodes:
  [league_standings]  -- renders React app that fetches standings via WP AJAX.

Setup:
1) Run sql/migrations.mysql.sql on your EXTERNAL MySQL.
2) In WP Admin -> Galaxy League, fill DB credentials.
3) In /assets: npm install && npm run build
4) Upload/activate plugin. Use [league_standings] on a page.

Notes:
- Standings uses view team_standings. Extend schema & handlers for schedule, fixtures, players.
Build steps:

cd assets
npm install
npm run build

This creates /assets/dist with main.js and main.css.
WordPress will enqueue these automatically.

In WordPress:
- Activate plugin
- Menu: Galaxy League -> fill external DB credentials
- Create page "Standings" and add shortcode [league_standings]
