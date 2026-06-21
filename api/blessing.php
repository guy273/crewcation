<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();
demo_block_writes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];

    // מחיקת ברכה שלי - אין עריכה, מוחקים וכותבים מחדש
    if (($body['action'] ?? '') === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $db = get_db();
        $stmt = $db->prepare("SELECT user_id FROM blessings WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) json_error('ברכה לא קיימת');
        if ($row['user_id'] !== $user_id) json_error('אפשר למחוק רק ברכות שלך');
        $db->prepare("DELETE FROM blessings WHERE id = ?")->execute([$id]);
        json_response(['ok' => true]);
    }

    $day     = (int)($body['day']     ?? 0);
    $content = trim($body['content']  ?? '');

    if ($day < 1 || $day > 5) json_error('יום לא תקין');
    if (strlen($content) < 1) json_error('ברכה לא יכולה להיות ריקה');
    if (strlen($content) > 2000) json_error('ברכה ארוכה מדי');

    $db = get_db();
    try {
        $stmt = $db->prepare("
            INSERT INTO blessings (day, user_id, content)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$day, $user_id, $content]);
    } catch (Exception $e) {
        json_error('שגיאה בשמירת הברכה', 500);
    }

    json_response(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = get_db();

    // all=1 -> every blessing across all days (for the wall)
    if (!empty($_GET['all'])) {
        $rows = $db->query("SELECT * FROM blessings ORDER BY created_at DESC")->fetchAll();
        $blessings = array_map(function($b) {
            return [
                'id'      => (int)$b['id'],
                'user_id' => $b['user_id'],
                'day'     => (int)$b['day'],
                'content' => $b['content'],
                'name'    => USERS[$b['user_id']]['name'] ?? $b['user_id'],
                'first'   => explode(' ', USERS[$b['user_id']]['name'] ?? $b['user_id'])[0],
                'at'      => $b['created_at'],
            ];
        }, $rows);
        json_response(['blessings' => $blessings]);
    }

    $day = (int)($_GET['day'] ?? 0);
    if ($day < 1 || $day > 5) json_error('יום לא תקין');

    $stmt = $db->prepare("SELECT b.*, b.user_id FROM blessings b WHERE b.day = ? ORDER BY b.created_at ASC");
    $stmt->execute([$day]);
    $rows = $stmt->fetchAll();

    $blessings = array_map(function($b) {
        $b['name'] = USERS[$b['user_id']]['name'] ?? $b['user_id'];
        return $b;
    }, $rows);

    json_response(['blessings' => $blessings]);
}

json_error('Method not allowed', 405);
