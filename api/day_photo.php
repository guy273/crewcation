<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

require_login();

$day = (int)($_GET['day'] ?? 0);
if ($day < 1 || $day > 5) json_error('יום לא תקין');

$db   = get_db();
$stmt = $db->prepare("SELECT filename, type FROM uploads WHERE day = ? AND type = 'image' ORDER BY RANDOM() LIMIT 1");
$stmt->execute([$day]);
$row  = $stmt->fetch();

if ($row) {
    json_response(['filename' => $row['filename'], 'type' => $row['type']]);
} else {
    // Try video if no image
    $stmt2 = $db->prepare("SELECT filename, type FROM uploads WHERE day = ? ORDER BY RANDOM() LIMIT 1");
    $stmt2->execute([$day]);
    $row2  = $stmt2->fetch();
    if ($row2) {
        json_response(['filename' => $row2['filename'], 'type' => $row2['type']]);
    } else {
        json_response(['filename' => null, 'type' => null]);
    }
}
