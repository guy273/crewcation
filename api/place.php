<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? 'add';
$db     = get_db();

// מחיקת מקום שלי בלבד
if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    $stmt = $db->prepare("SELECT added_by FROM places WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) json_error('המקום לא קיים');
    if (($row['added_by'] ?? '') !== $user_id) json_error('אפשר למחוק רק מקומות שהוספת');
    $db->prepare("DELETE FROM votes WHERE place_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM places WHERE id = ?")->execute([$id]);
    json_response(['ok' => true]);
}

// הוספת מקום
$day  = (int)($body['day'] ?? 0);
$meal = $body['meal'] ?? '';
$name = trim((string)($body['name'] ?? ''));
$url  = trim((string)($body['url'] ?? ''));

if ($day < 1 || $day > 5) json_error('יום לא תקין');
if (!in_array($meal, ['lunch', 'dinner'], true)) json_error('ארוחה לא תקינה');
if ($name === '' && $url === '') json_error('צריך שם או קישור');
if ($name === '') {
    $h = $url !== '' ? parse_url((str_starts_with($url, 'http') ? $url : 'https://' . $url), PHP_URL_HOST) : '';
    $name = $h ? preg_replace('/^www\\./', '', $h) : ('מקום של ' . (explode(' ', USERS[$user_id]['name'] ?? '')[0]));
}
$name = mb_substr($name, 0, 120);
$url  = mb_substr($url, 0, 500);

// תקרה: 2 מקומות לכל ארוחה ביום
$cnt = $db->prepare("SELECT COUNT(*) FROM places WHERE day = ? AND meal = ?");
$cnt->execute([$day, $meal]);
if ((int)$cnt->fetchColumn() >= 2) {
    json_error('כבר יש 2 מקומות לארוחה הזו. צריך לפנות מקום קודם.');
}

$ins = $db->prepare("INSERT INTO places (day, meal, name, description, url, added_by) VALUES (?, ?, ?, '', ?, ?)");
$ins->execute([$day, $meal, $name, $url, $user_id]);

json_response(['ok' => true, 'id' => (int)$db->lastInsertRowId()]);
