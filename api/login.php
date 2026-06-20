<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');

if ($username !== LOGIN_USERNAME) {
    json_error('שם משתמש שגוי', 401);
}

foreach (USERS as $uid => $user) {
    if (password_verify($password, $user['password'])) {
        session_start_safe();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $uid;
        json_response(['ok' => true, 'user_id' => $uid, 'name' => $user['name']]);
    }
}

json_error('סיסמה שגויה', 401);
