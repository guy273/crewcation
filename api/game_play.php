<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();
$db = get_db();
$db->exec("CREATE TABLE IF NOT EXISTS game_plays (game TEXT NOT NULL, user_id TEXT NOT NULL, plays INTEGER DEFAULT 0, UNIQUE(game, user_id))");

$valid = ['wheel', 'tetris', 'candy', 'bowling'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $game = $body['game'] ?? '';
    if (!in_array($game, $valid)) json_error('משחק לא קיים');
    $db->prepare("INSERT INTO game_plays (game, user_id, plays) VALUES (?, ?, 1)
                  ON CONFLICT(game, user_id) DO UPDATE SET plays = plays + 1")
       ->execute([$game, $user_id]);
    json_response(['ok' => true]);
}

$game = $_GET['game'] ?? '';
if (!in_array($game, $valid)) json_error('משחק לא קיים');
$stmt = $db->prepare("SELECT user_id, plays FROM game_plays WHERE game = ? ORDER BY plays DESC LIMIT 1");
$stmt->execute([$game]);
$top = $stmt->fetch();
json_response([
    'top' => $top ? ['first' => explode(' ', USERS[$top['user_id']]['name'] ?? $top['user_id'])[0], 'plays' => (int)$top['plays'], 'photo' => member_photo($top['user_id'])] : null,
]);
