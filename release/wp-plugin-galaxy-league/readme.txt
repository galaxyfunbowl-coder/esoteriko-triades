=== Galaxy League Results ===
Requires at least: 6.0
Stable tag: 0.3.0

Setup:
1. Run `sql/migrations.mysql.sql` on the external MySQL database.
2. In WordPress go to **Galaxy League → Settings** and fill DB host / name / user / pass.
3. Build assets:
   ```
   cd assets
   npm install
   npm run build
   ```
   (Generates `assets/dist/assets/index.js`, `admin.js`, `admin-manage.js`, `admin-setup.js`)
4. Upload only the plugin PHP files plus `assets/dist/` to the server and activate the plugin.
5. Add the shortcode `[league_standings]` to a page for the public table.

Admin → Setup Wizard:
- Step 1: δημιουργεί Season και βασικά Match Days.
- Step 2: εισαγωγή ομάδων και παικτών (gender + base HC).
- Step 3: ορισμός handicap για την 1η αγωνιστική.
- Step 4: δημιουργία fixtures 1ης και αποθήκευση σειράς παικτών (slots 1–3).

Admin → Manage:
- Seasons & Match Days: create seasons, add match days with labels/dates/types.
- Teams & Players: add teams, then roster players per team.
- Program (Fixtures): assign fixtures per match day, lanes, and manage player order (Save Order button).

Admin → Scores:
- Season → Match Day → Fixture selection
- Optional absence toggles per side
- Enter scratch scores per player (G1–G3), mark Blind when needed
- Save & Recompute to apply logic; pending roll-offs appear when ties exist
- Record roll-offs and recompute until the pending list clears
