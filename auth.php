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
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec("PRAGMA journal_mode=WAL;");
        $db->exec("PRAGMA foreign_keys=ON;");
    }
    return $db;
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
