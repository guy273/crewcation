<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();
demo_block_writes();
$db = get_db();
$db->exec("CREATE TABLE IF NOT EXISTS packing (user_id TEXT NOT NULL, item TEXT NOT NULL, PRIMARY KEY (user_id, item))");
$db->exec("CREATE TABLE IF NOT EXISTS packing_custom (item TEXT PRIMARY KEY, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

// רשימת כל הפריטים: בסיס מהקונפיג + מה שהוסיפו (משותף, בלי תיעוד מי)
function all_items(PDO $db): array {
    $custom = array_column($db->query("SELECT item FROM packing_custom ORDER BY created_at")->fetchAll(), 'item');
    $items = PACKING_ITEMS;
    foreach ($custom as $c) {
        if (!in_array($c, $items, true)) $items[] = $c;
    }
    return $items;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT item FROM packing WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $checked = array_column($stmt->fetchAll(), 'item');
    json_response(['items' => all_items($db), 'checked' => $checked]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? 'toggle';

// הוספת פריט לרשימה המשותפת
if ($action === 'add') {
    $item = trim((string)($body['item'] ?? ''));
    $item = mb_substr($item, 0, 40);
    if ($item === '') json_error('צריך טקסט');
    if (in_array($item, all_items($db), true)) {
        json_error('עוד לא שתינו ואתה כבר שיכור. יש את זה.');
    }
    $db->prepare("INSERT OR IGNORE INTO packing_custom (item) VALUES (?)")->execute([$item]);
    json_response(['ok' => true, 'items' => all_items($db)]);
}

// סימון/ביטול פריט (אישי)
$item = (string)($body['item'] ?? '');
$on   = !empty($body['checked']);
if (!in_array($item, all_items($db), true)) json_error('פריט לא תקין');

if ($on) {
    $db->prepare("INSERT OR IGNORE INTO packing (user_id, item) VALUES (?, ?)")->execute([$user_id, $item]);
} else {
    $db->prepare("DELETE FROM packing WHERE user_id = ? AND item = ?")->execute([$user_id, $item]);
}
json_response(['ok' => true]);
