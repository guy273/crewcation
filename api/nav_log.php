<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();
demo_block_writes();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $reason = trim($body['reason'] ?? '');
    if (mb_strlen($reason) > 120) $reason = mb_substr($reason, 0, 120);
    $stmt = $db->prepare("INSERT INTO nav_log (user_id, reason) VALUES (?, ?)");
    $stmt->execute([$user_id, $reason]);
    json_response(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $db->query("SELECT * FROM nav_log ORDER BY created_at DESC LIMIT 30")->fetchAll();
    $log = array_map(function ($r) {
        return [
            'name'   => explode(' ', USERS[$r['user_id']]['name'] ?? $r['user_id'])[0],
            'photo'  => member_photo($r['user_id']),
            'reason' => $r['reason'],
            'at'     => $r['created_at'],
        ];
    }, $rows);
    json_response(['log' => $log]);
}

json_error('Method not allowed', 405);
