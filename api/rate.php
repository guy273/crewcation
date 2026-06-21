<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();
demo_block_writes();

// היום הנוכחי של הטיול (1..5), 0 אם עוד לא התחיל
function trip_day_now(): int {
    $tripStart = mktime(0, 0, 0, 7, 1, 2026);
    return (int)floor((time() - $tripStart) / 86400) + 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $day   = (int)($body['day']   ?? 0);
    $ratee = trim($body['ratee']  ?? '');
    $param = trim($body['param']  ?? '');
    $stars = (int)($body['stars'] ?? 0);

    if ($day < 1 || $day > 5)          json_error('יום לא תקין');
    if (!isset(USERS[$ratee]))         json_error('משתתף לא קיים');
    if ($ratee === $user_id)           json_error('אי אפשר לדרג את עצמך');
    if (!isset(RATING_PARAMS[$param])) json_error('פרמטר דירוג לא קיים');
    if ($stars < 1 || $stars > 5)      json_error('דירוג חייב להיות בין 1 ל-5');

    // מותר לדרג רטרו (שכחנו? קורה). אסור לדרג ימים שעוד לא קרו.
    if (!is_dev_env() && $day > trip_day_now()) {
        json_error('אי אפשר לדרג יום שעוד לא קרה. סבלנות.');
    }

    $db = get_db();
    try {
        $stmt = $db->prepare("
            INSERT INTO ratings (day, rater_id, ratee_id, param, stars)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT(day, rater_id, ratee_id, param) DO UPDATE SET stars = excluded.stars
        ");
        $stmt->execute([$day, $user_id, $ratee, $param, $stars]);
    } catch (Exception $e) {
        json_error('שגיאה בשמירת הדירוג', 500);
    }

    json_response(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = get_db();

    // טבלת ההתנהגות המצטברת: סך נקודות מכל הימים והפרמטרים
    $agg = [];
    foreach ($db->query("SELECT ratee_id, SUM(stars) AS pts, COUNT(*) AS cnt, AVG(stars) AS avg_stars FROM ratings GROUP BY ratee_id")->fetchAll() as $r) {
        $agg[$r['ratee_id']] = [
            'points' => (int)$r['pts'],
            'cnt'    => (int)$r['cnt'],
            'avg'    => round((float)$r['avg_stars'], 1),
        ];
    }
    $leaderboard = [];
    foreach (USERS as $uid => $u) {
        $leaderboard[] = [
            'id'       => $uid,
            'name'     => $u['name'],
            'is_groom' => $u['is_groom'] ?? false,
            'points'   => $agg[$uid]['points'] ?? 0,
            'cnt'      => $agg[$uid]['cnt'] ?? 0,
            'avg'      => $agg[$uid]['avg'] ?? 0,
        ];
    }
    usort($leaderboard, function ($a, $b) {
        // נועם תמיד ראשון. ככה זה וזהו.
        if ($a['is_groom'] !== $b['is_groom']) return $a['is_groom'] ? -1 : 1;
        if ($b['points'] === $a['points']) return $b['avg'] <=> $a['avg'];
        return $b['points'] <=> $a['points'];
    });

    // מצב טבלה בלבד (לשונית צוות)
    if (isset($_GET['board'])) {
        json_response(['leaderboard' => $leaderboard]);
    }

    $day = (int)($_GET['day'] ?? 0);
    if ($day < 1 || $day > 5) json_error('יום לא תקין');

    // הדירוגים שלי ליום: ratee_id => [param => stars]
    $stmt = $db->prepare("SELECT ratee_id, param, stars FROM ratings WHERE day = ? AND rater_id = ?");
    $stmt->execute([$day, $user_id]);
    $myRatings = [];
    foreach ($stmt->fetchAll() as $r) {
        $myRatings[$r['ratee_id']][$r['param']] = (int)$r['stars'];
    }

    $members = [];
    foreach (USERS as $uid => $u) {
        $members[] = [
            'id'       => $uid,
            'name'     => $u['name'],
            'is_groom' => $u['is_groom'] ?? false,
            'is_me'    => $uid === $user_id,
        ];
    }

    json_response([
        'me'          => $user_id,
        'params'      => RATING_PARAMS,
        'members'     => $members,
        'my_ratings'  => $myRatings,
        'leaderboard' => $leaderboard,
    ]);
}

json_error('Method not allowed', 405);
