<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    json_error('לא הועלה קובץ');
}

$f = $_FILES['photo'];
if ($f['size'] > 8 * 1024 * 1024) json_error('התמונה גדולה מדי (עד 8MB)');

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $f['tmp_name']);
finfo_close($finfo);

$map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($map[$mime])) json_error('פורמט לא נתמך (רק JPG/PNG/WEBP)');
$ext = $map[$mime];

$dir = dirname(__DIR__) . '/assets/members';
if (!is_dir($dir)) { json_error('שגיאת שרת', 500); }

// remove any existing photo for this user
foreach (['webp','png','jpg','jpeg'] as $e) {
    $p = "$dir/{$user_id}.{$e}";
    if (file_exists($p)) @unlink($p);
}

$dest = "$dir/{$user_id}.{$ext}";
if (!move_uploaded_file($f['tmp_name'], $dest)) {
    json_error('שמירת התמונה נכשלה', 500);
}
@chmod($dest, 0644);

json_response(['ok' => true, 'photo' => "assets/members/{$user_id}.{$ext}?v=" . time()]);
