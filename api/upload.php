<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();

// GET - list uploads for a day
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $day = (int)($_GET['day'] ?? 0);
    if ($day < 1 || $day > 5) json_error('יום לא תקין');

    $db   = get_db();
    $stmt = $db->prepare("SELECT * FROM uploads WHERE day = ? ORDER BY created_at DESC");
    $stmt->execute([$day]);
    $rows = $stmt->fetchAll();

    $myCount = 0;
    $uploads = array_map(function($row) use ($user_id, &$myCount) {
        $uid = $row['user_id'];
        $name = USERS[$uid]['name'] ?? $uid;
        $row['short_name'] = explode(' ', $name)[0]; // שם פרטי מלא על התמונה
        if ($uid === $user_id) $myCount++;
        return $row;
    }, $rows);

    json_response([
        'uploads'  => $uploads,
        'my_count' => $myCount,
        'max'      => MAX_UPLOADS_PER_USER_PER_DAY,
        'first'    => explode(' ', USERS[$user_id]['name'] ?? $user_id)[0],
    ]);
}

// POST - upload file
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

// מחיקת תמונה שלי (JSON body: {action:'delete', id}) - מחזירה את ההקצאה
if (empty($_FILES) && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    if (($body['action'] ?? '') === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM uploads WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) json_error('תמונה לא קיימת');
        if ($row['user_id'] !== $user_id) json_error('אפשר למחוק רק תמונות שלך');
        if (!is_dev_env()) {
            $tripStart = mktime(0, 0, 0, 7, 1, 2026);
            $todayTrip = (int)floor((time() - $tripStart) / 86400) + 1;
            if ((int)$row['day'] !== $todayTrip) json_error('אפשר למחוק רק תמונות מהיום');
        }
        $db->prepare("DELETE FROM uploads WHERE id = ?")->execute([$id]);
        @unlink(UPLOAD_PATH . $row['filename']);
        json_response(['ok' => true]);
    }
    json_error('פעולה לא מוכרת');
}

$day = (int)($_POST['day'] ?? 0);
if ($day < 1 || $day > 5) json_error('יום לא תקין');

// העלאה מותרת רק ליום הנוכחי של הטיול (ב-dev אין אכיפה כדי לאפשר בדיקות)
if (!is_dev_env()) {
    $tripStart = mktime(0, 0, 0, 7, 1, 2026);
    $todayTrip = (int)floor((time() - $tripStart) / 86400) + 1;
    if ($day !== $todayTrip) {
        json_error('אפשר להעלות תמונות רק להיום. אין לחפור בעבר ואין להקדים את המאוחר.');
    }
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['file']['error'] ?? -1;
    $msgs = [
        UPLOAD_ERR_INI_SIZE   => 'הקובץ גדול מדי (הגדרת שרת)',
        UPLOAD_ERR_FORM_SIZE  => 'הקובץ גדול מדי',
        UPLOAD_ERR_PARTIAL    => 'ההעלאה לא הושלמה',
        UPLOAD_ERR_NO_FILE    => 'לא נשלח קובץ',
        UPLOAD_ERR_NO_TMP_DIR => 'שגיאת שרת (tmp dir)',
        UPLOAD_ERR_CANT_WRITE => 'שגיאת כתיבה בשרת',
    ];
    json_error($msgs[$err] ?? 'שגיאת העלאה', 400);
}

$file    = $_FILES['file'];
$tmpPath = $file['tmp_name'];
$size    = $file['size'];

if ($size > MAX_FILE_SIZE) {
    json_error('הקובץ גדול מדי. עד ' . (int)(MAX_FILE_SIZE / 1048576) . 'MB.');
}

// בדיקת MIME בצד שרת - תמונות בלבד
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);

if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
    json_error('אפשר להעלות רק תמונות (כולל HEIC של אייפון).');
}

$db = get_db();

// Check daily limit
$countStmt = $db->prepare("SELECT COUNT(*) FROM uploads WHERE day = ? AND user_id = ?");
$countStmt->execute([$day, $user_id]);
$count = (int)$countStmt->fetchColumn();

if ($count >= MAX_UPLOADS_PER_USER_PER_DAY) {
    json_error("הגעת לגבול של " . MAX_UPLOADS_PER_USER_PER_DAY . " תמונות ליום");
}

$type = 'image';

if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// GIF נשמר כמו שהוא (לשמר אנימציה). כל השאר עובר עיבוד ל-JPEG.
$isGif    = ($mimeType === 'image/gif');
$ext      = $isGif ? 'gif' : 'jpg';
$filename = sprintf('%s_day%d_%s.%s', $user_id, $day, bin2hex(random_bytes(8)), $ext);
$destPath = UPLOAD_PATH . $filename;

if ($isGif) {
    if (!move_uploaded_file($tmpPath, $destPath)) {
        json_error('שגיאה בשמירת הקובץ', 500);
    }
} else {
    // עיבוד עם Imagick: המרת HEIC ל-JPEG, תיקון סיבוב EXIF, הסרת מטא-דאטה (פרטיות), והקטנה.
    try {
        $img = new Imagick();
        $img->readImage($tmpPath);
        if ($img->getNumberImages() > 1) {           // Live Photo / HEIC רב-פריים - פריים ראשון
            $img = $img->coalesceImages();
            $img->setIteratorIndex(0);
        }
        $img->setImageBackgroundColor('white');       // שקיפות -> רקע לבן ב-JPEG
        $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        // סיבוב לפי EXIF ידני (autoOrientImage לא קיים בגרסת Imagick של השרת)
        switch ($img->getImageOrientation()) {
            case Imagick::ORIENTATION_BOTTOMRIGHT: $img->rotateImage('#000', 180); break;
            case Imagick::ORIENTATION_RIGHTTOP:    $img->rotateImage('#000', 90);  break;
            case Imagick::ORIENTATION_LEFTBOTTOM:  $img->rotateImage('#000', -90); break;
        }
        $img->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
        $img->stripImage();                           // הסרת EXIF/GPS - פרטיות וגודל
        $w = $img->getImageWidth();
        $h = $img->getImageHeight();
        $max = 2200;                                  // הקטנה לחיסכון דיסק ורוחב פס
        if ($w > $max || $h > $max) {
            $img->resizeImage($max, $max, Imagick::FILTER_LANCZOS, 1, true);
        }
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality(82);
        $img->writeImage($destPath);
        $img->clear();
        $img->destroy();
    } catch (Throwable $e) {
        @unlink($destPath);
        json_error('לא הצלחנו לעבד את התמונה. נסה תמונה אחרת.', 500);
    }
}
@chmod($destPath, 0644);

try {
    $ins = $db->prepare("INSERT INTO uploads (day, user_id, filename, type) VALUES (?, ?, ?, ?)");
    $ins->execute([$day, $user_id, $filename, $type]);
} catch (Exception $e) {
    @unlink($destPath);
    json_error('שגיאה בשמירת מידע הקובץ', 500);
}

json_response(['ok' => true, 'filename' => $filename, 'type' => $type]);
