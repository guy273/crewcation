<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();

$day = (int)($_GET['day'] ?? 0);
if ($day < 1 || $day > 5) json_error('יום לא תקין');

$db = get_db();

// Get places for day
$placesStmt = $db->prepare("SELECT * FROM places WHERE day = ? ORDER BY meal, id");
$placesStmt->execute([$day]);
$places = $placesStmt->fetchAll();

// Get all votes for day
$votesStmt = $db->prepare("SELECT v.place_id, v.user_id FROM votes v WHERE v.day = ?");
$votesStmt->execute([$day]);
$rawVotes = $votesStmt->fetchAll();

// Tally votes + track my votes
$voteMap  = [];
$myVotes  = ['lunch' => [], 'dinner' => []];
$groomVoted = ['lunch' => false, 'dinner' => false];

$groomId = null;
foreach (USERS as $uid => $u) {
    if ($u['is_groom']) { $groomId = $uid; break; }
}

// Need meal info per place
$placeMap = [];
foreach ($places as $p) {
    $placeMap[$p['id']] = $p;
}

foreach ($rawVotes as $v) {
    $pid  = $v['place_id'];
    $uid  = $v['user_id'];
    $meal = $placeMap[$pid]['meal'] ?? null;

    if (!isset($voteMap[$pid])) {
        $voteMap[$pid] = ['count' => 0, 'voters' => []];
    }
    $voteMap[$pid]['count']++;
    $voteMap[$pid]['voters'][] = [
        'user_id' => $uid,
        'name'    => USERS[$uid]['name'] ?? $uid,
        'photo'   => member_photo($uid),
    ];

    if ($uid === $user_id && $meal) {
        $myVotes[$meal][] = $pid;
    }
    if ($uid === $groomId && $meal) {
        $groomVoted[$meal] = true;
    }
}

json_response([
    'places'      => $places,
    'votes'       => $voteMap,
    'my_votes'    => $myVotes,
    'groom_voted' => $groomVoted,
]);
