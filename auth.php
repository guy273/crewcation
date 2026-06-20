<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'secure'   => true,  // HTTPS נכפה ב-.htaccess - העוגייה לא תעבור בלי TLS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function require_login(): string {
    session_start_safe();
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
    return $_SESSION['user_id'];
}

function get_current_user_id(): ?string {
    session_start_safe();
    return $_SESSION['user_id'] ?? null;
}

function is_dev_env(): bool {
    return strpos($_SERVER['REQUEST_URI'] ?? '', '/dev-') !== false;
}

// נעילה אחידה לכל הדפים. שוחרר ידנית (APP_RELEASED) - המוצר פתוח לאיסוף מקומות.
// ב-dev עדיין אפשר לראות את מצב הטיזר עם ?teaser=1.
function app_locked(): bool {
    if (is_dev_env()) return isset($_GET['teaser']);
    if (defined('APP_RELEASED') && APP_RELEASED) return false;
    return time() < UNLOCK_TS;
}

// מצב איסוף: לפני הטיול - ממלאים מקומות לימים, בלי הצבעה. נפתח להצבעה ביום הטיסה.
function collection_mode(int $tripDayNow): bool {
    return $tripDayNow <= 0;
}

function get_user(string $user_id): ?array {
    return USERS[$user_id] ?? null;
}

function member_photo(string $uid): string {
    foreach (['webp','png','jpg','jpeg'] as $ext) {
        if (file_exists(__DIR__ . "/assets/members/{$uid}.{$ext}")) {
            // cache-bust by file mtime so a replaced photo shows everywhere
            return "assets/members/{$uid}.{$ext}?v=" . filemtime(__DIR__ . "/assets/members/{$uid}.{$ext}");
        }
    }
    return '';
}

function get_db(): PDO {
    static $db = null;
    if ($db === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec("PRAGMA journal_mode=WAL;");
        $db->exec("PRAGMA foreign_keys=ON;");
        ensure_schema($db);
    }
    return $db;
}

// יוצר את טבלאות הבסיס אם חסרות - כך שכל התקנה חדשה עובדת בלי צעד ידני.
function ensure_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS places (
        id INTEGER PRIMARY KEY AUTOINCREMENT, day INTEGER NOT NULL,
        meal TEXT NOT NULL CHECK(meal IN ('lunch','dinner')),
        name TEXT NOT NULL, description TEXT, url TEXT, user_id TEXT
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS votes (
        id INTEGER PRIMARY KEY AUTOINCREMENT, day INTEGER NOT NULL,
        meal TEXT NOT NULL CHECK(meal IN ('lunch','dinner')),
        place_id INTEGER NOT NULL, user_id TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(day, meal, user_id)
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS uploads (
        id INTEGER PRIMARY KEY AUTOINCREMENT, day INTEGER NOT NULL, user_id TEXT NOT NULL,
        filename TEXT NOT NULL, type TEXT NOT NULL CHECK(type IN ('image','video')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS blessings (
        id INTEGER PRIMARY KEY AUTOINCREMENT, day INTEGER NOT NULL, user_id TEXT NOT NULL,
        content TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(day, user_id)
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS ratings (
        id INTEGER PRIMARY KEY AUTOINCREMENT, day INTEGER NOT NULL,
        rater_id TEXT NOT NULL, ratee_id TEXT NOT NULL,
        stars INTEGER NOT NULL CHECK(stars BETWEEN 1 AND 5),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(day, rater_id, ratee_id)
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS suggestions (
        id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL,
        name TEXT NOT NULL, url TEXT, note TEXT, og_image TEXT, og_site TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

function json_response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): never {
    json_response(['error' => $message], $status);
}
