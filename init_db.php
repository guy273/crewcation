<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$dir = dirname(DB_PATH);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("PRAGMA journal_mode=WAL;");
    $db->exec("PRAGMA foreign_keys=ON;");

    $db->exec("
        CREATE TABLE IF NOT EXISTS places (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            day         INTEGER NOT NULL,
            meal        TEXT    NOT NULL CHECK(meal IN ('lunch','dinner')),
            name        TEXT    NOT NULL,
            description TEXT,
            url         TEXT
        );
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS votes (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            day        INTEGER NOT NULL,
            meal       TEXT    NOT NULL CHECK(meal IN ('lunch','dinner')),
            place_id   INTEGER NOT NULL,
            user_id    TEXT    NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(day, meal, user_id)
        );
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS uploads (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            day        INTEGER NOT NULL,
            user_id    TEXT    NOT NULL,
            filename   TEXT    NOT NULL,
            type       TEXT    NOT NULL CHECK(type IN ('image','video')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS blessings (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            day        INTEGER NOT NULL,
            user_id    TEXT    NOT NULL,
            content    TEXT    NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(day, user_id)
        );
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS ratings (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            day        INTEGER NOT NULL,
            rater_id   TEXT    NOT NULL,
            ratee_id   TEXT    NOT NULL,
            stars      INTEGER NOT NULL CHECK(stars BETWEEN 1 AND 5),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(day, rater_id, ratee_id)
        );
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS suggestions (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    TEXT    NOT NULL,
            name       TEXT    NOT NULL,
            url        TEXT,
            note       TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Demo places - ניתן לעדכן ידנית לאחר מכן
    $existingPlaces = $db->query("SELECT COUNT(*) FROM places")->fetchColumn();
    if ((int)$existingPlaces === 0) {
        $stmt = $db->prepare("
            INSERT INTO places (day, meal, name, description, url) VALUES (?, ?, ?, ?, ?)
        ");
        $demoPlaces = [
            [1, 'lunch',  'מסעדה ראשונה',  'ארוחת צהריים ביום הראשון',   ''],
            [1, 'dinner', 'מסעדה שנייה',   'ארוחת ערב ביום הראשון',     ''],
            [2, 'lunch',  'מסעדה שלישית',  'ארוחת צהריים ביום השני',    ''],
            [2, 'dinner', 'מסעדה רביעית',  'ארוחת ערב ביום השני',       ''],
            [3, 'lunch',  'מסעדה חמישית',  'ארוחת צהריים ביום השלישי',  ''],
            [3, 'dinner', 'מסעדה שישית',   'ארוחת ערב ביום השלישי',     ''],
            [4, 'lunch',  'מסעדה שביעית',  'ארוחת צהריים ביום הרביעי',  ''],
            [4, 'dinner', 'מסעדה שמינית',  'ארוחת ערב ביום הרביעי',     ''],
            [5, 'lunch',  'מסעדה תשיעית',  'ארוחת צהריים ביום החמישי',  ''],
            [5, 'dinner', 'מסעדה עשירית',  'ארוחת ערב ביום החמישי',     ''],
        ];
        foreach ($demoPlaces as $p) {
            $stmt->execute($p);
        }
    }

    echo "<pre>DB initialized successfully at: " . DB_PATH . "\nTables: places, votes, uploads, blessings\n</pre>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
