<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();
demo_block_writes();
$db = get_db();
$db->exec("CREATE TABLE IF NOT EXISTS game_scores (game TEXT NOT NULL, user_id TEXT NOT NULL, score INTEGER DEFAULT 0, PRIMARY KEY (game, user_id))");

$GAMES = ['candy', 'tetris', 'bowling', 'wheel'];
// משחקים שבהם נמוך יותר = טוב יותר (זיכרון: פחות ניסיונות)
$LOWER = ['tetris'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $game = $_GET['game'] ?? '';
    if (!in_array($game, $GAMES, true)) json_error('משחק לא תקין');
    $order = in_array($game, $LOWER, true) ? 'ASC' : 'DESC';
    $stmt = $db->prepare("SELECT user_id, score FROM game_scores WHERE game = ? ORDER BY score $order, rowid ASC LIMIT 1");
    $stmt->execute([$game]);
    $r = $stmt->fetch();
    $top = null;
    if ($r) {
        $top = [
            'first' => explode(' ', USERS[$r['user_id']]['name'] ?? $r['user_id'])[0],
            'photo' => member_photo($r['user_id']),
            'score' => (int)$r['score'],
        ];
    }
    json_response(['top' => $top]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$game  = (string)($body['game'] ?? '');
$score = (int)($body['score'] ?? 0);
if (!in_array($game, $GAMES, true)) json_error('משחק לא תקין');
if ($score < 0 || $score > 1000000) json_error('ניקוד לא תקין');

// שומר את השיא האישי. רוב המשחקים = מקסימום; זיכרון = מינימום (פחות ניסיונות)
$agg = in_array($game, $LOWER, true) ? 'MIN' : 'MAX';
$db->prepare("INSERT INTO game_scores (game, user_id, score) VALUES (?, ?, ?)
              ON CONFLICT(game, user_id) DO UPDATE SET score = $agg(score, excluded.score)")
   ->execute([$game, $user_id, $score]);

json_response(['ok' => true]);
