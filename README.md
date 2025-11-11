# 🎳 Galaxy League Results

**WordPress + React + MySQL** σύστημα διαχείρισης και προβολής αποτελεσμάτων για το εσωτερικό πρωτάθλημα τριάδων bowling του **Galaxy Fun 'n Bowl**.

---

## 🧩 Overview

Το plugin διαχειρίζεται:

- 7 **κανονικές αγωνιστικές** + **Μπαράζ** + **Τελικούς**
- **Live υπολογισμούς** H2H, ομαδικών και bonus πόντων
- **Roll-off** για *όλες* τις ισοπαλίες (ατομικά, ομαδικά, σύνολο)
- **Blind / Absent** παίκτες (AVG−15 χωρίς HC, δεν παίρνουν βαθμό)
- **Αυτόματο Handicap** με βάση μέσο όρο 180 (80%, capped 30/40)
- **Διαίρεση βαθμών** κάθε 3 αγωνιστικές
- **Προκριματικά & Μπαράζ** (Top-4 + 2 από seeds 5–8)
- **Frontend πίνακα κατάταξης** μέσω React

---

## ⚙️ Features

| Πεδίο | Περιγραφή |
|-------|------------|
| **Αγώνες** | 3 παιχνίδια ανά συνάντηση (rotation 1–1, 1–2, 1–3 κ.λπ.) |
| **Σύνολο βαθμών** | 21 ανά αγωνιστική (9 H2H + 9 ομαδικά + 3 bonus) |
| **Roll-offs** | Ατομικά / Ομαδικά / Σύνολο, αποθηκεύονται στη DB |
| **Blind Player** | AVG−15, HC = 0, δεν διεκδικεί βαθμούς |
| **Απουσία ομάδας** | Αυτόματη νίκη αντιπάλου |
| **HC Υπολογισμός** | 80% διαφοράς από 180 (cap 30♂ / 40♀) |
| **Division** | Κάθε 3 match days: διαίρεση βαθμών |
| **Playoffs** | Top-4 direct, seeds 5–8 ➜ 2 μπαράζ για τελικά |
| **Frontend** | React standings table μέσω shortcode [league_standings] |
| **Admin** | Σελίδα "Scores" με εισαγωγή σκορ, roll-offs, absence/blind toggles |

---

## 🧱 Database Schema

> Το plugin συνδέεται σε **εξωτερική MySQL**. Εκτέλεσε το αρχείο sql/migrations.mysql.sql.

Περιλαμβάνει:

- seasons, match_days, ixtures, 	eams, players
- game_participants, games, ound_totals
- oll_offs, points_adjustments, hc_history
- 	eam_standings (view)

---

## 🧮 Βαθμολογία

| Κατηγορία | Πόντοι |
|-----------|--------|
| Ατομική κόντρα (H2H) | 1 |
| Νίκη ομάδας σε κάθε game | 3 |
| Bonus για σειρά (series win) | 3 |
| **Σύνολο ανά fixture** | **21** |

Όλες οι ισοπαλίες λύνονται με **roll-off**. Blind δεν διεκδικεί βαθμό — σε ισοπαλία κερδίζει ο παρών παίκτης.

---

## 🖥️ Installation

1. **Εξωτερική MySQL**  
   Τρέξε sql/migrations.mysql.sql.

2. **WordPress Plugin**  
   Αντιγραφή φακέλου wp-plugin-galaxy-league στο /wp-content/plugins/ και ενεργοποίηση από το WP Admin.

3. **Ρυθμίσεις βάσης**  
   WP Admin → **Galaxy League → Settings** → συμπλήρωσε host, database, user, pass.

4. **Build React assets**

   `ash
   cd wp-plugin-galaxy-league/assets
   npm install
   npm run build
   `

5. **Προβολή κατάταξης**  
   Δημιούργησε σελίδα στο WP και πρόσθεσε το shortcode:

   `
   [league_standings]
   `

---

## 🧰 Admin λειτουργίες

WP Admin → **Galaxy League → Scores**

1. Επιλέγεις Season → Match Day → Fixture  
2. Ορίζεις Absence / Blind ανά παίκτη  
3. Εισάγεις scratch σκορ (G1–G3) ανά slot  
4. Αποθήκευση ➜ αυτόματος υπολογισμός (21 πόντοι)  
5. Αν υπάρχουν ισοπαλίες ➜ εμφανίζεται “Pending Roll-offs”  
6. Συμπλήρωσε roll-off σκορ ➜ Recompute

---

## 🧮 Handicap

- Πρώτη αγωνιστική: χειροκίνητα (hc_history)
- Έπειτα: **HC = round(0.8 * max(0, 180 - AVG))**
  - cap ♂ 30
  - cap ♀ 40

---

## 🔁 Division & Playoffs

- Κάθε 3 αγωνιστικές:

  `
  POST /glr/v1/apply-division?season_id=1&idx=3
  `

- Μετά την κανονική περίοδο:

  `
  GET /glr/v1/playoff-qualifiers
  `

  ➜ δείχνει Top-4 + seeds 5–8

- Δημιουργία μπαράζ:

  `json
  POST /glr/v1/create-barrage
  {
    "season_id": 1,
    "date": "2025-03-12",
    "label": "ΜΠΑΡΑΖ Τετάρτη 19:00"
  }
  `

---

## 📡 REST API Endpoints

| Route | Περιγραφή |
|-------|-----------|
| GET /glr/v1/seasons | Λίστα σεζόν |
| GET /glr/v1/match-days?season_id=... | Αγωνιστικές |
| GET /glr/v1/fixtures?match_day_id=... | Fixtures ανά αγωνιστική |
| POST /glr/v1/submit-scores | Αποθήκευση σκορ |
| POST /glr/v1/recompute | Επανυπολογισμός fixture |
| GET /glr/v1/rolloffs / POST /glr/v1/rolloffs | Λίστα / αποθήκευση roll-offs |
| POST /glr/v1/recalc-hc | Επαναυπολογισμός HC επόμενης αγωνιστικής |
| POST /glr/v1/apply-division | Εφαρμογή division |
| GET /glr/v1/final-standings | Τελική κατάταξη |
| GET /glr/v1/playoff-qualifiers | Top-4 + 5–8 seeds |
| POST /glr/v1/create-barrage | Δημιουργία 2 μπαράζ fixtures |

---

## 📊 Τεχνικά

- Backend: PHP 8.1+ (συμβατό και με 7.4+)
- WordPress 6.x+, REST API ενεργό
- Frontend: React + Vite
- Database: MySQL 5.7+ ή MariaDB 10.4+

---

## 💡 Developer Notes

- Δυνατότητα μελλοντικής σύνδεσης με Galaxy Live Scoring JSON feed.
- Ο πίνακας ound_totals αποθηκεύει τα συνολικά 21 πόντα για τα standings.
- Η σελίδα “Scores” υλοποιείται με React admin interface (ssets/admin.jsx).
- Ο πυρήνας κανόνων βρίσκεται στο inc/Logic.php.

---

## 🏁 Credits

Developed for Galaxy Fun 'n Bowl  
by the in-house dev team, based on competition rules 2025–2026.

## 🧠 License

MIT License — use freely, modify responsibly.

---

Θες να σου προσθέσω και **παράδειγμα screenshots/εικόνες** για το README (standings table και admin panel mockup) ώστε να φαίνεται ωραίο στο GitHub;
