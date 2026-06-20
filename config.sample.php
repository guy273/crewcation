<?php
// ===== config.sample.php =====
// העתק לקובץ config.php ומלא את הפרטים שלך. config.php ב-.gitignore ולא עולה לגיט.
//   cp config.sample.php config.php
declare(strict_types=1);

date_default_timezone_set('Asia/Jerusalem');

define('DB_PATH',      __DIR__ . '/data/db.sqlite');
define('UPLOAD_PATH',  __DIR__ . '/uploads/');

// ===== מיתוג: שם האפליקציה - שנה לשם הקבוצה שלך (למשל הפרלמנט / החבר'ה / הנבחרת) =====
define('APP_NAME', 'Crewcation');
// שם המשתמש המשותף בכניסה (כולם נכנסים עם השם הזה + הסיסמה האישית)
define('LOGIN_USERNAME', 'crew');
// קישור ניווט ליעד (Google Maps)
define('HOTEL_URL', 'https://www.google.com/maps');
// true = המוצר פתוח לכולם מיד. false = מסך "טיזר" נעול עד UNLOCK_TS (להפתעה).
define('APP_RELEASED', true);

// ===== מצב דמו ציבורי =====
// true = כניסה אוטומטית בלי סיסמה + חסימת העלאות תמונות. לתצוגה פומבית בלבד.
// לטיול אמיתי: השאר false (כניסה עם סיסמה, העלאות פעילות).
define('DEMO_MODE', true);
define('DEMO_USER', 'alon'); // הדמות שאליה נכנסים אוטומטית בדמו

// ===== תאריכי הטיול. השעון לפני הטיסה והמעבר האוטומטי למצב "במהלך" מבוססים על זה =====
define('TRIP_START', mktime(0, 0, 0, 7, 1, 2026));  // יום 1, 00:00
define('FLIGHT_TS',  mktime(10, 0, 0, 7, 1, 2026)); // שעת ההמראה (ספירה לאחור)
define('UNLOCK_TS',  mktime(6, 0, 0, 7, 1, 2026));  // רגע שחרור המוצר המלא

define('MAX_UPLOADS_PER_USER_PER_DAY', 6);
define('MAX_FILE_SIZE', 25 * 1024 * 1024); // 25MB - תמונות בלבד
define('ALLOWED_MIME_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif',
]);
define('TRIP_DAYS', 5);
define('MAX_SUGGESTIONS_PER_USER', 5);

define('RATING_PARAMS', [
    'mood'       => 'מצב רוח',
    'alcohol'    => 'אלכוהול',
    'initiative' => 'יוזמה',
    'vibe'       => 'תרומה לאווירה',
]);

// רשימת "לא לשכוח" - כל אחד מסמן לעצמו; אפשר להוסיף פריטים מהאפליקציה
define('PACKING_ITEMS', [
    'תחתונים', 'מגבת', 'כובע', 'כפכפים', 'נעליים', 'משקפי שמש',
    'דאודורנט', 'בושם', 'בגד ים', 'מסרק', 'משחה + מברשת שיניים',
    'שעון', 'מטענים', 'אוזניות', 'מים', 'מגבונים', 'מזומן', 'תיק קטן',
]);

// ===== הצוות. כל אחד: שם, סיסמה (bcrypt), האם החתן. הסיסמה בדמו = "demo" =====
// ליצירת סיסמה: php -r 'echo password_hash("PASS", PASSWORD_BCRYPT, ["cost"=>12]);'
define('USERS', [
    'noam'  => ['name' => 'נועם',  'is_groom' => true,  'password' => '$2y$12$IvnjIXbTtZuAcdnkVvsNAuAISB.h06D/fafNzbnkl2nbHD4bxxasO', 'phrase' => 'החתן. הסיבה שכולנו פה.', 'tel' => '0500000001', 'since' => null, 'tags' => [], 'bio' => 'איש נעים, לב רחב. כולם שמחים לראות אותו נכנס לחדר.'],
    'alon'  => ['name' => 'אלון',  'is_groom' => false, 'password' => '$2y$12$IvnjIXbTtZuAcdnkVvsNAuAISB.h06D/fafNzbnkl2nbHD4bxxasO', 'phrase' => 'מי שארגן את כל הבלאגן.', 'tel' => '0500000002', 'since' => 2008, 'tags' => ['אנרגיות', 'ראשון לבריכה'], 'bio' => 'הנשמה של החבורה. מגיע ראשון לכל מקום וצוחק הכי חזק.'],
    'yoav'  => ['name' => 'יואב',  'is_groom' => false, 'password' => '$2y$12$IvnjIXbTtZuAcdnkVvsNAuAISB.h06D/fafNzbnkl2nbHD4bxxasO', 'phrase' => 'האחראי הלא רשמי.', 'tel' => '0500000003', 'since' => 2010, 'tags' => ['שומר הדרכונים', 'הלו"ז אצלי'], 'bio' => 'יודע איפה הדרכון של כולם ומתי היציאה מחר. בלעדיו היינו אבודים.'],
    'itai'  => ['name' => 'איתי',  'is_groom' => false, 'password' => '$2y$12$IvnjIXbTtZuAcdnkVvsNAuAISB.h06D/fafNzbnkl2nbHD4bxxasO', 'phrase' => 'שקט בחוץ, בלגן מבפנים.', 'tel' => '0500000004', 'since' => 2012, 'tags' => ['ההפתעה מהשקטים'], 'bio' => 'מדבר מעט, אבל המשפט האחד שלו בערב שווה שעה של כולם ביחד.'],
    'rotem' => ['name' => 'רותם', 'is_groom' => false, 'password' => '$2y$12$IvnjIXbTtZuAcdnkVvsNAuAISB.h06D/fafNzbnkl2nbHD4bxxasO', 'phrase' => 'תמיד מביא את הווייב.', 'tel' => '0500000005', 'since' => 2009, 'tags' => ['הדיג\'יי של הנסיעה'], 'bio' => 'אחראי על הפלייליסט, על מצב הרוח, ועל זה שאף אחד לא ישתעמם.'],
]);
