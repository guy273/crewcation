<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();
demo_block_writes();
$db = get_db();
$db->exec("CREATE TABLE IF NOT EXISTS member_tags (user_id TEXT NOT NULL, tag TEXT NOT NULL)");

// אתחול חד-פעמי: זריעת התגיות מהקונפיג ל-DB (פעם אחת, אם הטבלה ריקה לגמרי)
$seeded = (int)$db->query("SELECT COUNT(*) FROM member_tags")->fetchColumn() > 0;
if (!$seeded) {
    $ins = $db->prepare("INSERT INTO member_tags (user_id, tag) VALUES (?, ?)");
    foreach (USERS as $uid => $u) {
        foreach (($u['tags'] ?? []) as $t) { $ins->execute([$uid, $t]); }
    }
}

const MAX_TAGS = 6;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? '';
    if (!isset(USERS[$id])) json_error('משתמש לא תקין');
    $stmt = $db->prepare("SELECT tag FROM member_tags WHERE user_id = ? ORDER BY rowid");
    $stmt->execute([$id]);
    json_response([
        'tags'     => array_column($stmt->fetchAll(), 'tag'),
        'can_edit' => $id === $user_id,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';
$tag    = mb_substr(trim((string)($body['tag'] ?? '')), 0, 30);

// תמיד פועלים על המשתמש המחובר בלבד - אי אפשר לערוך תגיות של אחר
if ($action === 'add') {
    if ($tag === '') json_error('צריך טקסט');
    $cnt = $db->prepare("SELECT COUNT(*) FROM member_tags WHERE user_id = ?");
    $cnt->execute([$user_id]);
    if ((int)$cnt->fetchColumn() >= MAX_TAGS) json_error('עד ' . MAX_TAGS . ' תגיות');
    // לא לכפול
    $ex = $db->prepare("SELECT COUNT(*) FROM member_tags WHERE user_id = ? AND tag = ?");
    $ex->execute([$user_id, $tag]);
    if ((int)$ex->fetchColumn() === 0) {
        $db->prepare("INSERT INTO member_tags (user_id, tag) VALUES (?, ?)")->execute([$user_id, $tag]);
    }
    json_response(['ok' => true]);
}

if ($action === 'delete') {
    $db->prepare("DELETE FROM member_tags WHERE user_id = ? AND tag = ?")->execute([$user_id, $tag]);
    json_response(['ok' => true]);
}

json_error('פעולה לא מוכרת');
