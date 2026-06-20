<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();
if (uploads_blocked() && $_SERVER['REQUEST_METHOD'] === 'POST') json_error('זה דמו - העלאת תמונות מושבתת. תהנו משאר המוצר 🙂');
$db = get_db();
$db->exec("CREATE TABLE IF NOT EXISTS hype (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, filename TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

// GET - all hype photos, newest first
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $db->query("SELECT * FROM hype ORDER BY created_at DESC")->fetchAll();
    $out = array_map(function ($r) use ($user_id) {
        $name = USERS[$r['user_id']]['name'] ?? $r['user_id'];
        return [
            'id'    => (int)$r['id'],
            'file'  => 'uploads/' . $r['filename'],
            'first' => explode(' ', $name)[0],
            'at'    => $r['created_at'],
            'mine'  => $r['user_id'] === $user_id,
        ];
    }, $rows);
    // האם כבר העליתי היום
    $stmt = $db->prepare("SELECT COUNT(*) FROM hype WHERE user_id = ? AND date(created_at) = date('now','localtime')");
    $stmt->execute([$user_id]);
    json_response(['photos' => $out, 'posted_today' => (int)$stmt->fetchColumn() > 0]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

// מחיקת התמונה שלי
if (empty($_FILES) && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    if (($body['action'] ?? '') === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM hype WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) json_error('לא קיים');
        if ($row['user_id'] !== $user_id) json_error('אפשר למחוק רק תמונה שלך');
        $db->prepare("DELETE FROM hype WHERE id = ?")->execute([$id]);
        @unlink(UPLOAD_PATH . $row['filename']);
        json_response(['ok' => true]);
    }
    json_error('פעולה לא מוכרת');
}

// תמונה אחת ליום למשתמש
$stmt = $db->prepare("SELECT COUNT(*) FROM hype WHERE user_id = ? AND date(created_at) = date('now','localtime')");
$stmt->execute([$user_id]);
if ((int)$stmt->fetchColumn() > 0) {
    json_error('כבר העלית תמונה היום. מחר עוד אחת.');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    json_error('לא נשלח קובץ', 400);
}
$file = $_FILES['file'];
if ($file['size'] > MAX_FILE_SIZE) json_error('הקובץ גדול מדי. עד ' . (int)(MAX_FILE_SIZE / 1048576) . 'MB.');

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if (!in_array($mime, ALLOWED_MIME_TYPES, true)) json_error('אפשר להעלות רק תמונות.');

if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
$isGif    = ($mime === 'image/gif');
$filename = sprintf('hype_%s_%s.%s', $user_id, bin2hex(random_bytes(8)), $isGif ? 'gif' : 'jpg');
$dest     = UPLOAD_PATH . $filename;

if ($isGif) {
    if (!move_uploaded_file($file['tmp_name'], $dest)) json_error('שגיאה בשמירה', 500);
} else {
    try {
        $img = new Imagick();
        $img->readImage($file['tmp_name']);
        if ($img->getNumberImages() > 1) { $img = $img->coalesceImages(); $img->setIteratorIndex(0); }
        $img->setImageBackgroundColor('white');
        $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        // תיקון סיבוב EXIF ידני (autoOrientImage לא קיים בגרסת Imagick של השרת)
        switch ($img->getImageOrientation()) {
            case Imagick::ORIENTATION_BOTTOMRIGHT: $img->rotateImage('#000', 180); break;
            case Imagick::ORIENTATION_RIGHTTOP:    $img->rotateImage('#000', 90);  break;
            case Imagick::ORIENTATION_LEFTBOTTOM:  $img->rotateImage('#000', -90); break;
        }
        $img->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
        $img->stripImage();
        $w = $img->getImageWidth(); $h = $img->getImageHeight();
        if ($w > 2200 || $h > 2200) $img->resizeImage(2200, 2200, Imagick::FILTER_LANCZOS, 1, true);
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality(82);
        $img->writeImage($dest);
        $img->clear(); $img->destroy();
    } catch (Throwable $e) {
        @unlink($dest);
        json_error('לא הצלחנו לעבד את התמונה. נסה אחרת.', 500);
    }
}
@chmod($dest, 0644);

$ins = $db->prepare("INSERT INTO hype (user_id, filename) VALUES (?, ?)");
$ins->execute([$user_id, $filename]);
json_response(['ok' => true]);
