<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$day      = (int)($body['day']      ?? 0);
$meal     = $body['meal']     ?? '';
$place_id = (int)($body['place_id'] ?? 0);
$action   = $body['action']   ?? 'vote';

if ($day < 1 || $day > 5)                  json_error('יום לא תקין');
if (!in_array($meal, ['lunch', 'dinner']))  json_error('ארוחה לא תקינה');
if ($place_id < 1)                          json_error('מקום לא תקין');

$db = get_db();

// Cancel vote (משחרר את הבחירה במקום הספציפי)
if ($action === 'cancel') {
    // groom lock: nobody else can change while groom voted
    $groomUser = null;
    foreach (USERS as $uid => $u) { if ($u['is_groom']) { $groomUser = $uid; break; } }
    $gv = $db->prepare("SELECT 1 FROM votes WHERE day = ? AND meal = ? AND user_id = ?");
    $gv->execute([$day, $meal, $groomUser]);
    if ($gv->fetch() && $user_id !== $groomUser) {
        json_error('נועם כבר קבע - ההצבעה נעולה');
    }
    $del = $db->prepare("DELETE FROM votes WHERE day = ? AND meal = ? AND user_id = ? AND place_id = ?");
    $del->execute([$day, $meal, $user_id, $place_id]);
    json_response(['ok' => true]);
}

// Verify place exists for this day+meal
$place = $db->prepare("SELECT id FROM places WHERE id = ? AND day = ? AND meal = ?");
$place->execute([$place_id, $day, $meal]);
if (!$place->fetch()) {
    json_error('מקום לא קיים');
}

// Check if groom already voted for this day+meal (groom can always change their vote)
$groomUser = null;
foreach (USERS as $uid => $u) {
    if ($u['is_groom']) { $groomUser = $uid; break; }
}

$groomVote = $db->prepare("SELECT place_id FROM votes WHERE day = ? AND meal = ? AND user_id = ?");
$groomVote->execute([$day, $meal, $groomUser]);
$groomVoted = $groomVote->fetch();

if ($groomVoted && $user_id !== $groomUser) {
    json_error('נועם כבר קבע - ההצבעה נעולה');
}

// עד 2 בחירות לארוחה לכל משתתף
$cnt = $db->prepare("SELECT COUNT(*) FROM votes WHERE day = ? AND meal = ? AND user_id = ?");
$cnt->execute([$day, $meal, $user_id]);
if ((int)$cnt->fetchColumn() >= 2) {
    json_error('אפשר לבחור עד 2 מקומות. שחרר אחד קודם.');
}

try {
    $ins = $db->prepare("INSERT OR IGNORE INTO votes (day, meal, place_id, user_id) VALUES (?, ?, ?, ?)");
    $ins->execute([$day, $meal, $place_id, $user_id]);
} catch (Exception $e) {
    json_error('שגיאה בשמירת הצבעה', 500);
}

json_response(['ok' => true]);
