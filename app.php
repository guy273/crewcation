<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

$user_id  = require_login();
$user     = get_user($user_id);
$userName = $user['name'];
$myFirst  = explode(' ', $userName)[0];
$myPhoto  = member_photo($user_id);

$locked = app_locked();

// ברצלונה 1-5.7.2026: רביעי עד ראשון
$dayLabels = [
    1 => ['title' => 'יום רביעי',  'date' => '1.7'],
    2 => ['title' => 'יום חמישי',  'date' => '2.7'],
    3 => ['title' => 'יום שישי',   'date' => '3.7'],
    4 => ['title' => 'שבת',        'date' => '4.7'],
    5 => ['title' => 'יום ראשון',  'date' => '5.7'],
];

// which trip day is "now" (1.7-5.7.2026). 0 = trip not started yet.
$tripStart  = mktime(0, 0, 0, 7, 1, 2026);
$tripDayNow = (int)floor((time() - $tripStart) / 86400) + 1;
if ($tripDayNow < 0) $tripDayNow = 0;
// dev preview: middle of the trip so we can see past/today/future states.
// ?simday=N מדמה יום אחר (למשל simday=1 = בוקר השחרור)
if (is_dev_env()) {
    $tripDayNow = isset($_GET['simday']) ? max(0, min(5, (int)$_GET['simday'])) : 3;
} elseif (($dsd = demo_sim_day()) !== null) {
    $tripDayNow = $dsd; // דמו: 0 = לפני הטיסה, 3 = במהלך הטיול
}
// מצב איסוף: לפני הטיול - ממלאים מקומות, בלי הצבעה ובלי נעילות עבר/עתיד.
$collectionMode = collection_mode($tripDayNow);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<script>try{var t=localStorage.getItem("cw-theme");if(t&&t!=="gold")document.documentElement.dataset.theme=t;}catch(e){}</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#080810">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars(APP_NAME) ?>">
    <link rel="apple-touch-icon" href="assets/icon-180.png">
    <link rel="icon" type="image/svg+xml" href="assets/crown.svg">
    <link rel="icon" type="image/png" href="assets/icon-192.png">
    <link rel="manifest" href="manifest.json">
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body<?= demo_mode() ? ' class="is-demo"' : '' ?>>
<div class="app-bg-depth" aria-hidden="true"></div><div class="app-bg-sheen" aria-hidden="true"></div>

<!-- Header -->
<header class="app-header">
    <?php include __DIR__ . '/header_profile.php'; ?>
    <div class="logo-area">
        <svg class="logo-crown" viewBox="0 0 100 100" aria-hidden="true"><defs><linearGradient id="lcg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFD966"/><stop offset="0.5" stop-color="#D4A017"/><stop offset="1" stop-color="#9A7414"/></linearGradient></defs><path fill="url(#lcg)" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="url(#lcg)"/><circle cx="14" cy="32" r="5" fill="#FFD966"/><circle cx="50" cy="20" r="5.5" fill="#FFD966"/><circle cx="86" cy="32" r="5" fill="#FFD966"/></svg>
        <h1><?= htmlspecialchars(APP_NAME) ?></h1>
    </div>
    <?php include __DIR__ . '/header_photo_btn.php'; ?>
</header>

<?php if ($locked): ?>
<?php
// פרצופים לגלגל
$wheelFaces = [];
foreach (USERS as $uid => $u) {
    $photo = '';
    foreach (['webp','png','jpg','jpeg'] as $ext) {
        if (file_exists(__DIR__ . "/assets/members/{$uid}.{$ext}")) { $photo = "assets/members/{$uid}.{$ext}"; break; }
    }
    $wheelFaces[] = ['name' => explode(' ', $u['name'])[0], 'photo' => $photo, 'groom' => !empty($u['is_groom'])];
}
// תמונות מלון (שים קבצים ב-assets/hotel/)
$hotelPhotos = [];
foreach (glob(__DIR__ . '/assets/hotel/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [] as $p) {
    $hotelPhotos[] = 'assets/hotel/' . basename($p);
}
?>
<!-- ===== TEASER MODE (before flight) ===== -->
<main class="app-wrap teaser-wrap">
    <section class="teaser-hero reveal">
        <div class="teaser-soon">עוד מעט טסים</div>
        <h2>הספירה לברצלונה התחילה</h2>
        <p class="teaser-sub">עד אז - שתפו מקומות שבא לכם לבקר בהם. הכל ייפתח ביום הטיסה.</p>
        <div class="countdown" id="countdown"></div>
    </section>

    <!-- Suggestions - העיקר עכשיו, למעלה -->
    <section class="suggest-section reveal">
        <div class="sug-head2">
            <div class="navlog-title sug-title2">שיתוף מקומות
                <span class="suggest-count" id="sugCount"></span>
                <span class="info-wrap" id="sugInfo">
                    <button class="info-i" onclick="this.parentNode.classList.toggle('open'); event.stopPropagation()" aria-label="מה זה">i</button>
                    <span class="info-tip" id="sugTitle">יש מקום שבא לך לבקר בו בברצלונה? שתף אותנו כאן. עד <?= MAX_SUGGESTIONS_PER_USER ?> מקומות לכל אחד.</span>
                </span>
            </div>
            <button class="gal-add sug-toggle" id="sugToggle" onclick="toggleSugForm()" aria-label="הוספת מקום">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
        </div>
        <div class="suggest-form sug-collapse" id="suggestForm">
            <div class="suggest-field">
                <input type="text" id="sugUrl" placeholder="הדביקו קישור (Instagram / Maps / אתר)" maxlength="500" oninput="toggleSugSend()">
                <button class="sug-send" id="sugSubmit" onclick="submitSuggestion()" aria-label="שלח"><?= icon('navigation', 'i') ?></button>
            </div>
            <div class="sug-error" id="sugError">אל תתחכם, תביא לינק.</div>
        </div>
        <div class="suggest-list" id="suggestList">
            <div class="spinner"></div>
        </div>
    </section>

    <!-- Games -->
    <section class="games-open" id="games" style="margin-top:2.75rem">
        <div class="navlog-title">משחקים</div>
        <div class="games-grid">
            <a class="game-tile" href="games.php?g=wheel"><span class="gt-emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/><path d="M12 3v6.5M12 14.5V21M3 12h6.5M14.5 12H21"/></svg></span><span>הגלגל האהוב</span></a>
            <a class="game-tile" href="games.php?g=tetris"><span class="gt-emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="8" height="10" rx="2"/><rect x="13" y="10" width="8" height="10" rx="2"/><path d="M7 8.5h.01M17 14.5h.01"/></svg></span><span>משחק הזיכרון</span></a>
            <a class="game-tile" href="games.php?g=candy"><span class="gt-emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="7" cy="7" r="3"/><circle cx="17" cy="7" r="3"/><circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/></svg></span><span>קנדי ראס</span></a>
            <a class="game-tile" href="games.php?g=bowling"><span class="gt-emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="18" r="3"/><path d="M12 15V9M9 6l3 3 3-3"/><circle cx="6" cy="4" r="1.6"/><circle cx="12" cy="3" r="1.6"/><circle cx="18" cy="4" r="1.6"/></svg></span><span>הגנת החתן</span></a>
        </div>
    </section>

    <!-- Trip info accordions -->
    <section class="info-section reveal" style="margin-top:2.75rem">
        <div class="navlog-title info-title">צריך שיהיה פה</div>
        <details class="acc">
            <summary>
                <span class="acc-title">פרטי הטיסה</span>
                <span class="acc-chevron"><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </summary>
            <div class="acc-body">
                <div class="flight-leg">
                    <div class="leg-head">הלוך · תל אביב ← ברצלונה</div>
                    <div class="leg-rows"><span>תאריך ושעות - לעדכון</span></div>
                    <div class="leg-sub">טיסה לדוגמה · טרמינל 1</div>
                </div>
                <div class="flight-leg">
                    <div class="leg-head">חזור · ברצלונה ← תל אביב</div>
                    <div class="leg-rows"><span>תאריך ושעות - לעדכון</span></div>
                    <div class="leg-sub">טיסה לדוגמה</div>
                </div>
            </div>
        </details>

        <details class="acc">
            <summary>
                <span class="acc-title">המלון</span>
                <span class="acc-chevron"><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </summary>
            <div class="acc-body">
                <div class="hotel-name">Zeus Wyndham Grand Athens</div>
                <div class="hotel-addr">Megalou Alexandrou 2, Athens 10437</div>
                <div class="hotel-tags">דירוג 8.3 · בריכת גג · 3 מסעדות</div>
                <?php if ($hotelPhotos): ?>
                <div class="hotel-gallery">
                    <?php foreach ($hotelPhotos as $hp): ?>
                        <a href="<?= htmlspecialchars($hp) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($hp) ?>" alt="מלון" loading="lazy"></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </details>
    </section>

    <!-- Locked features - ייפתחו ביום הטיסה -->
    <section class="locked-previews reveal" style="margin-top:2.75rem">
        <div class="locked-card">
            <span class="lc-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10.5" width="14" height="9.5" rx="3"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/></svg></span>
            <div class="lc-text"><span class="lc-title">דירוגים</span><span class="lc-sub">יפתח ביום הטיסה</span></div>
        </div>
        <div class="locked-card">
            <span class="lc-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10.5" width="14" height="9.5" rx="3"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/></svg></span>
            <div class="lc-text"><span class="lc-title">גלריה</span><span class="lc-sub">יפתח ביום הטיסה</span></div>
        </div>
    </section>
</main>

<script>
const WHEEL_FACES = <?= json_encode($wheelFaces, JSON_UNESCAPED_UNICODE) ?>;
</script>

<?php else: ?>
<?php if ($collectionMode): ?>
<section class="collect-hero">
    <h2 class="collect-title">זמן לבלאגן</h2>
    <div class="countdown" id="countdown"></div>
    <p class="collect-intro">בחרו יום והוסיפו מקומות שבא לכם לבקר בהם. הצבעות, תמונות ודירוגים - נפתחים ביום הטיסה.</p>
</section>
<?php endif; ?>

<!-- Day stepper -->
<nav class="day-stepper">
    <button class="step-arrow" id="stepPrev" aria-label="יום קודם">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
    <div class="step-center">
        <div class="step-day" id="stepDay"></div>
        <div class="step-date" id="stepDate"></div>
    </div>
    <button class="step-arrow" id="stepNext" aria-label="יום הבא">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
</nav>

<!-- Meal tabs: lunch / dinner -->
<div class="meal-tabs" id="mealTabs">
    <button class="meal-tab active" data-meal="lunch" onclick="showMeal('lunch')"><?= icon('sun', 'mt-icon') ?> צהריים</button>
    <button class="meal-tab" data-meal="dinner" onclick="showMeal('dinner')"><?= icon('moon', 'mt-icon') ?> ערב</button>
</div>

<!-- Main content -->
<main class="app-wrap" id="appMain">

<!-- Future-day funny message -->
<div class="day-future" id="dayFuture" style="display:none">
    <div class="df-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10.5" width="14" height="9.5" rx="3"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/><circle cx="12" cy="15" r="1.1" fill="currentColor" stroke="none"/></svg></div>
    <div class="df-msg" id="dayFutureMsg"></div>
</div>


<?php $todayClamp = max(1, min(5, $tripDayNow ?: 1)); ?>
<?php foreach ($dayLabels as $num => $info): ?>
<section class="day-content <?= $num === $todayClamp ? 'active' : '' ?>" id="day-<?= $num ?>" data-day="<?= $num ?>">
<?php if ($num === 5): ?>
    <!-- היום האחרון: רק ענייני הטיסה הביתה -->
    <div class="flight-home">
        <div class="navlog-title">טסים הביתה</div>
        <div class="fh-count" id="fhCount"><div class="spinner"></div></div>
        <div class="flight-leg fh-leg">
            <div class="leg-head">חזור · ברצלונה ← תל אביב</div>
            <div class="leg-rows"><span>תאריך ושעות - לעדכון</span></div>
            <div class="leg-sub">טיסה לדוגמה</div>
        </div>
        <p class="fh-note">לא נספיק כלום היום. תארזו, תשתו קפה, ואל תאחרו.</p>
    </div>
</section>
<?php continue; endif; ?>
    <!-- Votes -->
    <div class="votes-wrap" id="votes-day-<?= $num ?>">
        <div class="spinner"></div>
    </div>

    <?php if ($collectionMode): ?>
    <!-- מצב איסוף: דירוגים וגלריה נעולים עד יום הטיסה -->
    <section class="locked-previews" style="margin-top:2.25rem">
        <div class="locked-card">
            <span class="lc-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10.5" width="14" height="9.5" rx="3"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/></svg></span>
            <div class="lc-text"><span class="lc-title">דירוגים</span><span class="lc-sub">יפתח ביום הטיסה</span></div>
        </div>
        <div class="locked-card">
            <span class="lc-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10.5" width="14" height="9.5" rx="3"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/></svg></span>
            <div class="lc-text"><span class="lc-title">גלריה</span><span class="lc-sub">יפתח ביום הטיסה</span></div>
        </div>
    </section>
    <?php else: ?>
    <!-- Ratings (open section) -->
    <section class="ratings-open">
        <div class="ro-head">
            <div class="ro-title">דירוג סוף יום</div>
            <a class="lb-link lb-link-top" href="members.php?tab=board">לטבלה המלאה &rsaquo;</a>
        </div>
        <div class="rating-cards" id="rating-cards-<?= $num ?>"><div class="spinner"></div></div>
        <button class="btn-primary rating-submit" id="rating-submit-<?= $num ?>" style="display:none" onclick="submitRatings(<?= $num ?>)">שלח הצבעה</button>
        <div class="rating-done" id="rating-done-<?= $num ?>" style="display:none">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            דירגת את כולם להיום
        </div>
    </section>

    <!-- Gallery (open section) -->
    <section class="gallery-open">
        <div class="gal-head">
            <div class="navlog-title gal-title">גלריה</div>
            <button class="gal-add" id="gal-add-<?= $num ?>" style="display:none" onclick="triggerDailyUpload()" aria-label="הוספת תמונה">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
        </div>
        <div class="gallery-grid" id="gallery-grid-<?= $num ?>"></div>
    </section>
    <?php endif; ?>

    <?php if ($num === 4): ?>
    <section class="ratings-open">
        <div class="navlog-title">רק נניח את זה פה</div>
        <div class="flight-leg fh-leg">
            <div class="leg-head">חזור · ברצלונה ← תל אביב</div>
            <div class="leg-rows"><span>תאריך ושעות - לעדכון</span></div>
            <div class="leg-sub">טיסה לדוגמה</div>
        </div>
    </section>
    <?php endif; ?>
</section>
<?php endforeach; ?>

<?php if ($collectionMode): ?>
<!-- תמונה ביום - תמונת התרגשות אחת ליום עד הטיסה -->
<section class="hype-section">
    <div class="hype-head">
        <div class="navlog-title">תמונה ביום</div>
        <button class="gal-add" id="hypeAdd" onclick="triggerHype()" aria-label="העלאת תמונה" style="display:none">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </button>
    </div>
    <p class="hype-sub">תמונה אחת ביום עד הטיסה - מההתרגשות. כולם רואים.</p>
    <input type="file" id="hype-file" style="display:none" accept="image/*,.heic,.heif">
    <div class="hype-gallery" id="hypeGallery"><div class="spinner"></div></div>
</section>
<?php endif; ?>

<?php
// פרצופים למשחקים (כמו בגלגל הטיזר)
$gameFaces = [];
foreach (USERS as $uid => $u) {
    foreach (['webp','png','jpg','jpeg'] as $ext) {
        if (file_exists(__DIR__ . "/assets/members/{$uid}.{$ext}")) {
            $gameFaces[] = ['name' => explode(' ', $u['name'])[0], 'photo' => "assets/members/{$uid}.{$ext}", 'groom' => !empty($u['is_groom'])];
            break;
        }
    }
}
// תמונות מלון לסקציית המידע
$hotelPhotos = [];
foreach (glob(__DIR__ . '/assets/hotel/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [] as $p) {
    $hotelPhotos[] = 'assets/hotel/' . basename($p);
}
?>
<!-- Games -->
<section class="games-open" id="games">
    <div class="navlog-title">משחקים</div>
    <div class="games-grid">
        <a class="game-tile" href="games.php?g=wheel"><span class="gt-emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/><path d="M12 3v6.5M12 14.5V21M3 12h6.5M14.5 12H21"/></svg></span><span>הגלגל האהוב</span></a>
        <a class="game-tile" href="games.php?g=tetris"><span class="gt-emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="8" height="10" rx="2"/><rect x="13" y="10" width="8" height="10" rx="2"/><path d="M7 8.5h.01M17 14.5h.01"/></svg></span><span>משחק הזיכרון</span></a>
        <a class="game-tile" href="games.php?g=candy"><span class="gt-emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="7" cy="7" r="3"/><circle cx="17" cy="7" r="3"/><circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/></svg></span><span>קנדי ראס</span></a>
        <a class="game-tile" href="games.php?g=bowling"><span class="gt-emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="18" r="3"/><path d="M12 15V9M9 6l3 3 3-3"/><circle cx="6" cy="4" r="1.6"/><circle cx="12" cy="3" r="1.6"/><circle cx="18" cy="4" r="1.6"/></svg></span><span>הגנת החתן</span></a>
    </div>
</section>

<?php if ($collectionMode): ?>
<!-- Trip info + packing (collection mode) -->
<section class="info-section" style="margin-top:2.75rem">
    <div class="navlog-title info-title">צריך שיהיה פה</div>
    <details class="acc">
        <summary>
            <span class="acc-title">פרטי הטיסה</span>
            <span class="acc-chevron"><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
        </summary>
        <div class="acc-body">
            <div class="flight-leg">
                <div class="leg-head">הלוך · תל אביב ← ברצלונה</div>
                <div class="leg-rows"><span>תאריך ושעות - לעדכון</span></div>
                <div class="leg-sub">טיסה לדוגמה · טרמינל 1</div>
            </div>
            <div class="flight-leg">
                <div class="leg-head">חזור · ברצלונה ← תל אביב</div>
                <div class="leg-rows"><span>תאריך ושעות - לעדכון</span></div>
                <div class="leg-sub">טיסה לדוגמה</div>
            </div>
        </div>
    </details>
    <details class="acc">
        <summary>
            <span class="acc-title">המלון</span>
            <span class="acc-chevron"><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
        </summary>
        <div class="acc-body">
            <div class="hotel-name">Zeus Wyndham Grand Athens</div>
            <div class="hotel-addr">Megalou Alexandrou 2, Athens 10437</div>
            <div class="hotel-tags">דירוג 8.3 · בריכת גג · 3 מסעדות</div>
            <?php if ($hotelPhotos): ?>
            <div class="hotel-gallery">
                <?php foreach ($hotelPhotos as $hp): ?>
                    <a href="<?= htmlspecialchars($hp) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($hp) ?>" alt="מלון" loading="lazy"></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </details>
    <details class="acc" id="packingAcc">
        <summary>
            <span class="acc-title">לא לשכוח <span class="pack-count" id="packCount"></span></span>
            <span class="acc-chevron"><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
        </summary>
        <div class="acc-body">
            <p class="pack-hint">סמנו ביום האריזה מה שכבר נכנס לתיק. אישי - רק אתם רואים. אפשר להוסיף פריטים לכולם.</p>
            <div class="pack-list" id="packList"></div>
            <div class="pack-add">
                <div class="suggest-field">
                    <input type="text" id="packNew" placeholder="להוסיף פריט לרשימה..." maxlength="40" oninput="packAddToggle()" onkeydown="if(event.key==='Enter'){event.preventDefault();addPackItem();}">
                    <button class="sug-send" id="packAddBtn" onclick="addPackItem()" aria-label="הוסף פריט">
                        <svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </details>
</section>
<?php endif; ?>

</main>

<?php endif; ?>

<!-- Past-day floating banner (above navbar) -->
<?php if (!$locked): ?>
<div class="day-past-banner" id="dayPastBanner">יום שעבר לא יחזור. תתקדם</div>
<?php endif; ?>

<!-- Bottom Navbar -->
<?php $NAV_ACTIVE = 'home'; include __DIR__ . '/navbar.php'; ?>

<!-- Upload progress overlay: הכתר מתמלא בזהב -->
<div class="upload-crown" id="uploadCrown">
    <div class="uc-stage">
        <svg class="uc-crown uc-base" viewBox="0 0 100 100" aria-hidden="true"><path fill="rgba(255,255,255,0.10)" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="rgba(255,255,255,0.10)"/></svg>
        <div class="uc-fill-wrap">
            <div class="uc-water" id="ucWater">
                <svg class="uc-wave" viewBox="0 0 120 10" preserveAspectRatio="none" aria-hidden="true"><path fill="#E9C355" d="M0 10 V5 Q7.5 0 15 5 T30 5 T45 5 T60 5 T75 5 T90 5 T105 5 T120 5 V10 Z"/></svg>
                <div class="uc-water-body"></div>
            </div>
        </div>
    </div>
    <div class="uc-text" id="ucText">מעלה...</div>
</div>
<input type="file" id="daily-file-input" style="display:none" accept="image/*,.heic,.heif" multiple>

<!-- Lightbox: full-screen photo viewer -->
<div class="lightbox" id="lightbox">
    <button class="modal-close lb-close" onclick="closeLightbox()" aria-label="סגור">&times;</button>
    <button class="lbx-arrow lbx-prev" onclick="lightboxStep(-1)" aria-label="הקודם"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
    <button class="lbx-arrow lbx-next" onclick="lightboxStep(1)" aria-label="הבא"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
    <div class="lbx-media" id="lbxMedia"></div>
    <div class="lbx-caption" id="lbxCaption"></div>
</div>

<?php include __DIR__ . '/soon_modal.php'; ?>

<!-- Modal: Blessing -->
<div class="modal-overlay" id="blessModal" onclick="closeModal('blessModal')">
    <button class="modal-close" onclick="closeModal('blessModal')" aria-label="סגור">&times;</button>
    <div class="modal-sheet" role="dialog" aria-modal="true" tabindex="-1" onclick="event.stopPropagation()">
        <div class="modal-handle"></div>
        <div class="modal-title"><?= icon('star', 'sect-icon') ?> ברכה לנועם</div>
        <textarea class="bless-textarea" id="blessText" aria-label="טקסט הברכה" placeholder="כתוב כאן את הברכה שלך לנועם..."></textarea>
        <button class="btn-primary" id="blessSubmitBtn" onclick="submitBlessing()">שלח ברכה</button>
    </div>
</div>

<!-- Modal: Add place (collection mode) -->
<div class="modal-overlay" id="addPlaceModal" onclick="closeModal('addPlaceModal')">
    <button class="modal-close" onclick="closeModal('addPlaceModal')" aria-label="סגור">&times;</button>
    <div class="modal-sheet" role="dialog" aria-modal="true" tabindex="-1" onclick="event.stopPropagation()">
        <div class="modal-handle"></div>
        <div class="modal-title nav-modal-title" id="addPlaceTitle">הוספת מקום</div>
        <div class="ap-field-wrap">
            <div class="suggest-field">
                <input type="text" id="apUrl" placeholder="הדביקו קישור - Maps / Instagram / אתר" maxlength="500" oninput="apToggle()">
                <button class="sug-send" id="apSubmit" onclick="submitAddPlace()" aria-label="שלח"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg></button>
            </div>
        </div>
    </div>
</div>

<script>
const USER_ID   = <?= json_encode($user_id) ?>;
const USER_NAME = <?= json_encode($userName) ?>;
const IS_GROOM  = <?= json_encode($user['is_groom']) ?>;
const LOCKED    = <?= json_encode($locked) ?>;
const UNLOCK_MS = <?= UNLOCK_TS ?> * 1000;
const FLIGHT_MS = new Date(2026, 6, 1, 10, 0, 0).getTime(); // המראה לברצלונה
const GROOM_ID = <?php foreach (USERS as $uid => $u) if (!empty($u['is_groom'])) { echo json_encode($uid); break; } ?>;
const TRIP_DAY_NOW = <?= (int)$tripDayNow ?>;
const COLLECTION_MODE = <?= json_encode($collectionMode) ?>;
const MEMBER_NAMES = <?= json_encode(array_map(fn($u) => explode(' ', $u['name'])[0], (function(){ $m=[]; foreach (USERS as $k=>$v) $m[$k]=$v; return $m; })()), JSON_UNESCAPED_UNICODE) ?>;
const DAY_META =<?= json_encode(array_map(fn($d)=>['title'=>$d['title'],'date'=>$d['date'].'.2026'], $dayLabels), JSON_UNESCAPED_UNICODE) ?>;

let currentDay  = Math.max(1, Math.min(5, TRIP_DAY_NOW || 1));
let blessModal_day = null;

/* ===== Icon system (no emojis) ===== */
const SVG = (p, vb='0 0 24 24') => `<svg class="i" viewBox="${vb}" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${p}</svg>`;
const ICONS = {
    upload: SVG('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12.5"/>'),
    star:   SVG('<polygon points="12 2.5 14.85 8.27 21.2 9.2 16.6 13.68 17.69 20 12 17.02 6.31 20 7.4 13.68 2.8 9.2 9.15 8.27 12 2.5"/>'),
    trophy: SVG('<path d="M7 4h10v5a5 5 0 0 1-10 0V4z"/><path d="M7 5H4.5a2 2 0 0 0 0 4H7M17 5h2.5a2 2 0 0 1 0 4H17M9 19h6M8 22h8M12 14v5"/>'),
    sun:    SVG('<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.5 4.5l1.4 1.4M18.1 18.1l1.4 1.4M2 12h2M20 12h2M4.5 19.5l1.4-1.4M18.1 5.9l1.4-1.4"/>'),
    moon:   SVG('<path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/>'),
    camera: SVG('<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3l2-3h8l2 3h3a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="3.6"/>'),
    pin:    SVG('<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>'),
    check:  SVG('<path d="M20 6L9 17l-5-5"/>'),
};
const CROWN = `<svg class="i-crown" viewBox="0 0 100 100" aria-hidden="true"><path fill="currentColor" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="currentColor"/><circle cx="14" cy="32" r="5" fill="currentColor"/><circle cx="50" cy="20" r="5.5" fill="currentColor"/><circle cx="86" cy="32" r="5" fill="currentColor"/></svg>`;

/* ===== Day stepper ===== */
const FUTURE_MSG_1 = 'טרם נפתח. חכה בסבלנות, אל תגמור לנו את הטיול.';
const FUTURE_MSG_2 = 'אתה מגזים. חלאס, לך אחורה תהנה.';

// במצב איסוף כל הימים פתוחים להזנה - בלי נעילות עבר/עתיד
function dayIsFuture(day) { if (COLLECTION_MODE) return false; return TRIP_DAY_NOW > 0 ? day > TRIP_DAY_NOW : day > 1; }
function dayIsPast(day)   { if (COLLECTION_MODE) return false; return TRIP_DAY_NOW > 0 && day < TRIP_DAY_NOW; }

/* החלפת יום: התוכן הנכנס מחליק פנימה בכיוון הדפדוף */
function animateDayIn(el, dir) {
    if (!el || !dir) return;
    el.classList.remove('day-in-next', 'day-in-prev');
    void el.offsetWidth;
    el.classList.add(dir > 0 ? 'day-in-next' : 'day-in-prev');
}

function switchDay(day) {
    if (day < 1 || day > 5) return;
    const dir = day > currentDay ? 1 : (day < currentDay ? -1 : 0);
    currentDay = day;

    // stepper labels
    const meta = DAY_META[day] || {};
    document.getElementById('stepDay').textContent  = meta.title || '';
    document.getElementById('stepDate').textContent = meta.date || '';
    document.getElementById('stepPrev').disabled = (day <= 1); // right = earlier day
    document.getElementById('stepNext').disabled = (day >= 5); // left = next day

    const future = document.getElementById('dayFuture');
    const contents = document.querySelectorAll('.day-content');

    animateDayIn(document.querySelector('.step-center'), dir);
    const mealTabs = document.getElementById('mealTabs');
    if (dayIsFuture(day)) {
        animateDayIn(document.getElementById('dayFuture'), dir);
        contents.forEach(s => s.classList.remove('active'));
        const nowBase = TRIP_DAY_NOW > 0 ? TRIP_DAY_NOW : 1;
        document.getElementById('dayFutureMsg').textContent = (day === nowBase + 1) ? FUTURE_MSG_1 : FUTURE_MSG_2;
        future.style.display = '';
        if (mealTabs) mealTabs.style.display = 'none';
        const pb = document.getElementById('dayPastBanner');
        if (pb) pb.classList.remove('show');
        return;
    }
    future.style.display = 'none';
    if (mealTabs) mealTabs.style.display = day === 5 ? 'none' : '';
    const curStack = document.getElementById(`stack-${day}`);
    const visible = curStack ? [...curStack.querySelectorAll('.meal-panel')].find(p => p.style.display !== 'none') : null;
    syncMealTabs(visible?.dataset.meal || 'lunch');
    contents.forEach(s => s.classList.toggle('active', +s.dataset.day === day));
    animateDayIn(document.getElementById(`day-${day}`), dir);
    // past day = floating red banner above the navbar
    const pastBanner = document.getElementById('dayPastBanner');
    if (pastBanner) pastBanner.classList.toggle('show', dayIsPast(day));
    loadDay(day);
}

/* RTL: right arrow (stepPrev) = יום קודם (-1), left arrow (stepNext) = יום הבא (+1) */
const _stepPrev = document.getElementById('stepPrev');
const _stepNext = document.getElementById('stepNext');
if (_stepPrev) _stepPrev.addEventListener('click', () => switchDay(currentDay - 1));
if (_stepNext) _stepNext.addEventListener('click', () => switchDay(currentDay + 1));

/* ===== Load day data ===== */
const loadedDays = new Set();

function loadDay(day) {
    if (day === 5) { startFlightCountdown(); return; }
    if (loadedDays.has(day)) return;
    loadedDays.add(day);
    loadVotes(day);
    // במצב איסוף דירוגים+גלריה נעולים (כרטיס נעול ב-PHP) - אין מה לטעון
    if (!COLLECTION_MODE) { loadRatings(day); loadGallery(day); }
}

/* ===== הוספת מקום (מצב איסוף) ===== */
let addPlaceCtx = { day: 0, meal: 'lunch' };
function openAddPlace(day, meal) {
    addPlaceCtx = { day, meal };
    const t = document.getElementById('addPlaceTitle');
    if (t) t.textContent = `הוספת מקום · ${DAY_META[day]?.title || ''} · ${meal === 'lunch' ? 'צהריים' : 'ערב'}`;
    document.getElementById('apUrl').value = '';
    document.getElementById('apSubmit').classList.remove('visible');
    openModal('addPlaceModal');
}
function apToggle() {
    const v = document.getElementById('apUrl').value.trim();
    document.getElementById('apSubmit').classList.toggle('visible', v.length > 0);
}
async function submitAddPlace() {
    const url  = document.getElementById('apUrl').value.trim();
    if (!url) return;
    const name = '';
    const btn = document.getElementById('apSubmit');
    btn.disabled = true;
    try {
        const res = await fetch('api/place.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', day: addPlaceCtx.day, meal: addPlaceCtx.meal, name, url }),
        });
        const data = await res.json();
        if (data.error) { alert(data.error); btn.disabled = false; return; }
        closeModal('addPlaceModal');
        loadedDays.delete(addPlaceCtx.day);
        loadVotes(addPlaceCtx.day);
    } catch { alert('שגיאה בהוספה'); }
    btn.disabled = false;
}
async function deletePlace(id, day) {
    if (!confirm('למחוק את המקום?')) return;
    try {
        const res = await fetch('api/place.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id }),
        });
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        loadedDays.delete(day);
        loadVotes(day);
    } catch { alert('שגיאה במחיקה'); }
}

/* ===== Ratings ===== */
async function loadRatings(day) {
    const cards = document.getElementById(`rating-cards-${day}`);
    try {
        const res  = await fetch(`api/rate.php?day=${day}`);
        const data = await res.json();
        renderRatings(day, data);
    } catch {
        cards.innerHTML = '<div class="empty-state">שגיאה בטעינת דירוגים</div>';
    }
}

/* pending local selections per day: ratingState[day][rateeId][param] = stars */
const RATING_PARAMS = <?= json_encode(RATING_PARAMS, JSON_UNESCAPED_UNICODE) ?>;
const RP_KEYS = Object.keys(RATING_PARAMS);
const CHEV_SVG = '<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
const ratingState = {};
const ratingSaved = {};

function ratingLocked(day) { return dayIsFuture(day); } // מדרגים גם רטרו - רק קדימה אסור

function starRow(day, rateeId, param, current, locked) {
    let s = '';
    for (let i = 1; i <= 5; i++) {
        s += `<button class="star ${i <= current ? 'on' : ''}" ${locked ? 'disabled' : ''}
            onclick="pickStar(${day}, '${rateeId}', '${param}', ${i})"
            aria-label="${i} כוכבים">★</button>`;
    }
    return s;
}

function rateeSum(day, rateeId) {
    return RP_KEYS.reduce((a, p) => a + (ratingState[day]?.[rateeId]?.[p] || 0), 0);
}

function renderRatings(day, data) {
    const cards  = document.getElementById(`rating-cards-${day}`);
    const others = (data.members || []).filter(m => !m.is_me);
    const locked = ratingLocked(day);

    ratingSaved[day] = {};
    others.forEach(m => {
        ratingSaved[day][m.id] = {};
        RP_KEYS.forEach(p => ratingSaved[day][m.id][p] = data.my_ratings?.[m.id]?.[p] || 0);
    });
    ratingState[day] = JSON.parse(JSON.stringify(ratingSaved[day]));

    const hintTime = new Date().getHours() >= 21 && day === TRIP_DAY_NOW;
    cards.innerHTML = others.map(m => {
        const sum = rateeSum(day, m.id);
        const done = RP_KEYS.every(p => ratingState[day][m.id][p] > 0);
        const hint = hintTime && !done ? `<span class="rate-hint" title="הגיע הזמן לדרג">i</span>` : '';
        const paramRows = RP_KEYS.map(p => `
            <div class="rating-row" data-ratee="${m.id}" data-param="${p}">
                <div class="rating-name rating-param">${RATING_PARAMS[p]}</div>
                <div class="star-row">${starRow(day, m.id, p, ratingState[day][m.id][p], locked)}</div>
            </div>`).join('');
        return `<details class="acc rate-acc" data-ratee="${m.id}">
            <summary>
                <span class="acc-title rate-name">${esc(m.name)}${m.is_groom ? '<span class="crown-inline rate-crown">'+CROWN+'</span>' : ''}</span>${hint}
                <span class="rate-score" id="rscore-${day}-${m.id}" style="${sum > 0 ? '' : 'display:none'}">${sum > 0 ? sum + ' נק׳' : ''}</span>
                <span class="acc-chevron">${CHEV_SVG}</span>
            </summary>
            <div class="acc-body rate-body">${paramRows}</div>
        </details>`;
    }).join('');

    updateRatingSubmit(day);
}

function pickStar(day, rateeId, param, stars) {
    if (ratingLocked(day)) return;
    ratingState[day][rateeId][param] = stars;
    const row = document.querySelector(`#rating-cards-${day} .rating-row[data-ratee="${rateeId}"][data-param="${param}"] .star-row`);
    if (row) row.innerHTML = starRow(day, rateeId, param, stars, false);
    const score = document.getElementById(`rscore-${day}-${rateeId}`);
    if (score) {
        const sum = rateeSum(day, rateeId);
        score.textContent = sum > 0 ? sum + ' נק׳' : '';
        score.style.display = sum > 0 ? '' : 'none';
    }
    updateRatingSubmit(day);
}

function ratingDiff(day) {
    const st = ratingState[day] || {}, sv = ratingSaved[day] || {};
    const out = [];
    for (const ratee in st) for (const p of RP_KEYS) {
        if (st[ratee][p] > 0 && st[ratee][p] !== (sv[ratee]?.[p] || 0)) out.push({ ratee, param: p, stars: st[ratee][p] });
    }
    return out;
}

function ratingLeft(day) {
    const st = ratingState[day] || {};
    return Object.keys(st).filter(r => !RP_KEYS.every(p => st[r][p] > 0)).length;
}

function updateRatingSubmit(day) {
    const btn = document.getElementById(`rating-submit-${day}`);
    const done = document.getElementById(`rating-done-${day}`);
    if (!btn) return;
    if (done) done.style.display = 'none';
    if (ratingLocked(day)) { btn.style.display = 'none'; return; }
    const st = ratingState[day] || {};
    const people = Object.keys(st);
    const left = ratingLeft(day);
    const anyRated = people.some(r => RP_KEYS.some(p => st[r][p] > 0));
    const changed = ratingDiff(day).length;

    if (!anyRated) { btn.style.display = 'none'; return; }
    btn.style.display = '';
    btn.disabled = false;
    if (left > 0) {
        // לחיצה ממשיכה לדרג - פותחת את הבא בתור
        btn.classList.add('ghost');
        btn.textContent = left === 1 ? 'יאללה, נשאר לדרג עוד 1' : 'המשך לדרג';
    } else if (changed > 0) {
        btn.classList.remove('ghost');
        btn.textContent = 'שלח הצבעה';
    } else {
        // הכל מדורג ושמור: אין פעולה - אין כפתור. שורת אישור שקטה.
        btn.style.display = 'none';
        if (done) done.style.display = '';
    }
}

/* פותח את האקורדיון של הבא שעוד לא סיימת לדרג (הגבוה ברשימה) */
function openNextUnrated(day) {
    const st = ratingState[day] || {};
    const accs = [...document.querySelectorAll(`#rating-cards-${day} .rate-acc`)];
    const next = accs.find(a => { const r = a.dataset.ratee; return st[r] && !RP_KEYS.every(p => st[r][p] > 0); });
    if (!next) return;
    accs.forEach(a => { if (a !== next) a.open = false; });
    next.open = true;
    next.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function submitRatings(day) {
    // עוד לא סיימת? הכפתור ממשיך אותך לבא בתור במקום לשלוח
    if (ratingLeft(day) > 0) { openNextUnrated(day); return; }
    const changed = ratingDiff(day);
    if (!changed.length) return;
    const btn = document.getElementById(`rating-submit-${day}`);
    btn.disabled = true; btn.textContent = 'שולח...';
    try {
        for (const c of changed) {
            await fetch('api/rate.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ day, ratee: c.ratee, param: c.param, stars: c.stars }),
            });
        }
        loadRatings(day);
    } catch {
        alert('שגיאה בשליחת הדירוג');
        btn.disabled = false; btn.textContent = 'שלח הצבעה';
    }
}

/* טוסט קטן מעל הנאבבר */
const BLOCKED_LINES = [
    'מקום נחמד, אבל לא רלוונטי. כבר בחרת 2.',
    'סקרן? שחרר בחירה אחת ותגלה.',
    'יפה. אבל יש לך כבר 2 אהבות להיום.',
];
let toastTimer = null;
function showToast(msg) {
    let t = document.getElementById('appToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'appToast'; t.className = 'app-toast';
        t.setAttribute('role', 'status'); t.setAttribute('aria-live', 'polite');
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2600);
}
function voteBlockedToast(e) {
    if (e.target.closest('button')) return;
    showToast(BLOCKED_LINES[Math.floor(Math.random() * BLOCKED_LINES.length)]);
}

/* לחיצה על כרטיס מקום פותחת את הקישור - אלא אם לחצו על כפתור */
function openPlaceUrl(e, url) {
    if (e.target.closest('button')) return;
    window.open(url, '_blank', 'noopener');
}

/* ===== Votes ===== */
async function loadVotes(day) {
    const wrap = document.getElementById(`votes-day-${day}`);
    try {
        const res  = await fetch(`api/get_votes.php?day=${day}`);
        const data = await res.json();
        renderVotes(day, data);
    } catch {
        wrap.innerHTML = '<div class="empty-state">שגיאה בטעינת הצבעות</div>';
    }
}

function renderVotes(day, data) {
    const wrap = document.getElementById(`votes-day-${day}`);
    const groomVoted = data.groom_voted || { lunch: false, dinner: false };
    const myVotes    = data.my_votes   || { lunch: [], dinner: [] };
    const meals      = { lunch: [ICONS.sun, 'צהריים'], dinner: [ICONS.moon, 'ערב'] };

    let slides = '';
    for (const [meal, [ic, label]] of Object.entries(meals)) {
        const places    = (data.places || []).filter(p => p.meal === meal).slice(0, 2); // 2 מקומות לארוחה

        // מצב איסוף (לפני הטיול): 2 slots - מקומות מלאים + קו מקווקו "הוספת מקום"
        if (COLLECTION_MODE) {
            let cinner = '';
            for (let i = 0; i < 2; i++) {
                const place = places[i];
                if (place) {
                    const mine = place.added_by === USER_ID;
                    let host = '';
                    if (place.url) { try { host = new URL(place.url.startsWith('http') ? place.url : 'https://' + place.url).hostname.replace(/^www\./, ''); } catch {} }
                    const del = mine ? `<button class="place-del" onclick="event.stopPropagation();deletePlace(${place.id},${day})" aria-label="מחק">×</button>` : '';
                    const open = place.url ? `onclick="openPlaceUrl(event,'${esc(place.url)}')"` : '';
                    cinner += `<div class="place-card collect ${place.url ? 'has-url' : ''}" ${open}>
                        <div class="place-card-top"><div class="place-card-info">
                            <h3>${esc(place.name)}</h3>
                            ${host ? `<p class="pc-host">${esc(host)}</p>` : ''}
                        </div>${del}</div>
                        <div class="pc-by">${esc(MEMBER_NAMES[place.added_by] || '')}</div>
                    </div>`;
                } else {
                    cinner += `<button class="place-add-slot" onclick="openAddPlace(${day},'${meal}')">
                        <span class="pas-plus">+</span><span>הוספת מקום</span>
                    </button>`;
                }
            }
            slides += `<div class="meal-panel" data-meal="${meal}" ${meal === 'dinner' ? 'style="display:none"' : ''}>${cinner}</div>`;
            continue;
        }

        const votes     = data.votes   || {};
        const totalVote = places.reduce((acc, p) => acc + (votes[p.id]?.count || 0), 0);
        const groomSet  = groomVoted[meal];
        const maxVotes  = Math.max(...places.map(p => votes[p.id]?.count || 0), 1);

        let inner = '';
        if (groomSet) {
            inner += `<div style="margin-bottom:.6rem"><span class="badge-groom">נועם קבע <span class="crown-inline">${CROWN}</span></span></div>`;
        }
        if (places.length === 0) {
            inner += `<div class="empty-state"><div class="empty-icon">${ICONS.pin}</div>עוד לא נוספו מקומות</div>`;
        }
        const myMealVotes = myVotes[meal] || [];
        // מנצח ביום שעבר: וטו של נועם גובר תמיד. בלי וטו - מוביל יחיד. תיקו = "ממתין לוטו"
        const leaders = places.filter(p => (votes[p.id]?.count || 0) === maxVotes && (votes[p.id]?.count || 0) > 0);
        const groomPickPlace = places.find(p => (votes[p.id]?.voters || []).some(v => v.user_id === GROOM_ID));
        const winnerId = groomPickPlace ? groomPickPlace.id : (leaders.length === 1 ? leaders[0].id : null);
        const mealTie = !groomPickPlace && leaders.length > 1;
        for (const place of places) {
            const voteInfo  = votes[place.id] || { count: 0, voters: [] };
            const myVote    = myMealVotes.includes(place.id);
            const isLeading = voteInfo.count === maxVotes && voteInfo.count > 0;

            const dayLocked = dayIsPast(day);
            let btnClass = 'btn-vote', btnText = 'בא לי', btnDisabled = '';
            if (myVote) { btnClass += ' voted'; btnText = 'נבחר'; }
            // בחרת כבר 2? השלישי נעול עד שתשחרר אחד
            else if (myMealVotes.length >= 2) { btnDisabled = 'disabled'; }
            if (groomSet && !IS_GROOM) { btnClass += ' groom-locked'; btnDisabled = 'disabled'; }

            // וטו של נועם: רק הוא מוצג, עם כתר, והמילה "וטו" במקום מספר. אחרים לא רלוונטיים.
            let votersHtml, countHtml;
            const groomPicked = voteInfo.voters.some(v => v.user_id === GROOM_ID);
            if (groomPicked) {
                const g = voteInfo.voters.find(v => v.user_id === GROOM_ID);
                const gi = g && g.photo ? `<img src="${esc(g.photo)}" alt="${esc(g.name)}">` : 'ע';
                votersHtml = `<span class="voter-avatar voter-groom">${gi}<span class="va-crown">${CROWN}</span></span>`;
                countHtml = `<span class="voter-count veto-label">וטו</span>`;
            } else {
                const n = voteInfo.voters.length;
                votersHtml = voteInfo.voters.map((v, vi) => {
                    const inner2 = v.photo ? `<img src="${esc(v.photo)}" alt="${esc(v.name)}">` : esc(v.name.charAt(0));
                    return `<span class="voter-avatar" style="z-index:${n - vi}" title="${esc(v.name)}">${inner2}</span>`;
                }).join('');
                countHtml = `<span class="voter-count">${voteInfo.count}</span>`;
            }

            // יום שעבר: בלי כפתור הצבעה. המקום שנבחר מקבל תג, השאר מעומעמים.
            const isWinner = dayLocked && winnerId === place.id;
            const isTied   = dayLocked && mealTie && isLeading;
            const isGroomPick = voteInfo.voters.some(v => v.user_id === GROOM_ID);
            const actionHtml = dayLocked
                ? (isWinner ? `<span class="place-chosen">נבחר</span>` : (isTied ? `<span class="place-chosen place-tie">ממתין לוטו</span>` : ''))
                : `<button class="${btnClass}" ${btnDisabled}
                        onclick="${myVote ? `cancelVote(${day}, '${meal}', ${place.id})` : `castVote(${day}, '${meal}', ${place.id})`}">${btnText}</button>`;

            const voteBlocked = !dayLocked && !myVote && myMealVotes.length >= 2;
            const urlAttr = voteBlocked
                ? `onclick="voteBlockedToast(event)"`
                : (place.url ? `onclick="openPlaceUrl(event, '${esc(place.url)}')"` : '');
            const votersBar = voteInfo.count > 0
                ? `<div class="vote-bar"><div class="vote-voters">${votersHtml}${countHtml}</div></div>`
                : '';
            inner += `<div class="place-card ${place.url ? 'has-url' : ''} ${isLeading ? 'leading' : ''} ${dayLocked && !isWinner && !isTied ? 'past-dim' : ''} ${isWinner || isGroomPick ? 'winner' : ''}" id="place-${place.id}" ${urlAttr}>
                <div class="place-card-top">
                    <div class="place-card-info">
                        <h3>${esc(place.name)}</h3>
                        <p>${esc(place.description || '')}</p>
                    </div>
                    ${actionHtml}
                </div>
                ${votersBar}
            </div>`;
        }

        slides += `<div class="meal-panel" data-meal="${meal}" ${meal === 'dinner' ? 'style="display:none"' : ''}>${inner}</div>`;
    }

    wrap.innerHTML = `<div class="meal-panels" id="stack-${day}">${slides}</div>`;
    setupMealPanels(day);
}

const DAY_CAPTIONS = {
    1: { lunch: 'רק נחתתם. לאט לאט, מישהו ידאג למי שצמא.', dinner: 'לילה ראשון. מי שנרדם ראשון - מצויר.' },
    2: { lunch: 'בוקר אחרי. מישהו זוכר מה היה אתמול?', dinner: 'הערב מתחמם. כולם כבר בכושר.' },
    3: { lunch: 'צהריים עצלים. הבריכה לא תשתה את עצמה.', dinner: 'ליל שישי. תכינו את הכבד.' },
    4: { lunch: 'הצהריים האחרון. שומרים כוח ללילה?', dinner: 'הלילה האחרון. אין רחמים.' },
    5: { lunch: 'טסים הביתה. כבר מתגעגעים.', dinner: 'עד הפעם הבאה.' },
};

const mealStacks = {}; // day -> {show(meal)}

function syncMealTabs(meal) {
    document.querySelectorAll('.meal-tab').forEach(t =>
        t.classList.toggle('active', t.dataset.meal === meal));
}

function showMeal(meal) {
    const api = mealStacks[currentDay];
    if (api) api.show(meal);
}

function setupMealPanels(day) {
    const wrap = document.getElementById(`stack-${day}`);
    if (!wrap) return;
    const panels = [...wrap.querySelectorAll('.meal-panel')];
    mealStacks[day] = {
        show(meal) {
            panels.forEach(p => {
                const on = p.dataset.meal === meal;
                p.style.display = on ? '' : 'none';
                if (on) { p.classList.remove('day-in-next'); void p.offsetWidth; p.classList.add('day-in-next'); }
            });
            syncMealTabs(meal);
        }
    };
    if (day === currentDay) syncMealTabs('lunch');
}

async function castVote(day, meal, placeId) {
    try {
        const res  = await fetch('api/vote.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ day, meal, place_id: placeId }),
        });
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        loadedDays.delete(day);
        loadVotes(day);
    } catch {
        alert('שגיאה בשמירת ההצבעה');
    }
}

async function cancelVote(day, meal, placeId) {
    try {
        const res  = await fetch('api/vote.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ day, meal, place_id: placeId, action: 'cancel' }),
        });
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        loadedDays.delete(day);
        loadVotes(day);
    } catch {
        alert('שגיאה בביטול ההצבעה');
    }
}

/* ===== Packing list (לא לשכוח) ===== */
async function loadPacking() {
    const list = document.getElementById('packList');
    if (!list) return;
    try {
        const res = await fetch('api/packing.php');
        const data = await res.json();
        const checked = new Set(data.checked || []);
        const items = data.items || [];
        list.innerHTML = items.map(it => {
            const on = checked.has(it);
            return `<label class="pack-item ${on ? 'done' : ''}">
                <input type="checkbox" value="${esc(it)}" ${on ? 'checked' : ''} onchange="togglePack(this)">
                <span class="pack-box"></span>
                <span class="pack-name">${esc(it)}</span>
            </label>`;
        }).join('');
        updatePackCount();
    } catch {}
}
function packAddToggle() {
    const inp = document.getElementById('packNew');
    const btn = document.getElementById('packAddBtn');
    if (btn) btn.classList.toggle('visible', inp.value.trim().length > 0);
}
async function addPackItem() {
    const inp = document.getElementById('packNew');
    const item = inp.value.trim();
    if (!item) return;
    inp.value = '';
    packAddToggle();
    try {
        const res = await fetch('api/packing.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', item }),
        });
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        loadPacking();
    } catch { alert('שגיאה בהוספה'); }
}
function updatePackCount() {
    const list = document.getElementById('packList');
    const chip = document.getElementById('packCount');
    if (!list || !chip) return;
    const total = list.querySelectorAll('input[type=checkbox]').length;
    const done  = list.querySelectorAll('input[type=checkbox]:checked').length;
    chip.textContent = done > 0 ? `${done}/${total}` : '';
}
async function togglePack(cb) {
    cb.closest('.pack-item').classList.toggle('done', cb.checked);
    updatePackCount();
    try {
        await fetch('api/packing.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item: cb.value, checked: cb.checked }),
        });
    } catch {}
}

/* ===== תמונה ביום (hype) ===== */
async function loadHype() {
    const g = document.getElementById('hypeGallery');
    if (!g) return;
    try {
        const res = await fetch('api/hype.php');
        const data = await res.json();
        const photos = data.photos || [];
        const addBtn = document.getElementById('hypeAdd');
        if (addBtn) addBtn.style.display = (data.posted_today || !photos.length) ? 'none' : '';
        dayPhotos['hype'] = photos.map(p => ({ filename: p.file.replace(/^uploads\//, ''), type: 'image', short_name: p.first }));
        if (!photos.length) {
            g.innerHTML = `<div class="gal-empty"><p class="gal-empty-text">עוד אין תמונות. תהיו הראשונים להתרגש.</p><button class="btn-upload gal-empty-btn" onclick="triggerHype()">${ICONS.upload} העלאת התמונה של היום</button></div>`;
            return;
        }
        let html = '', lastDate = '';
        photos.forEach((p, i) => {
            const d = new Date((p.at || '').replace(' ', 'T'));
            const dateKey = isNaN(d) ? '' : d.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit' });
            if (dateKey !== lastDate) { html += `<div class="hype-date">${dateKey}</div>`; lastDate = dateKey; }
            const del = p.mine ? `<button class="photo-trash" onclick="event.stopPropagation();deleteHype(${p.id})" aria-label="מחק"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>` : '';
            html += `<div class="hype-item" onclick="openLightbox('hype', ${i})"><img src="${esc(p.file)}" alt="" loading="lazy">${del}<span class="uploader-tag">${esc(p.first)}</span></div>`;
        });
        g.innerHTML = html;
    } catch { g.innerHTML = '<div class="empty-state">שגיאה בטעינה</div>'; }
}
function triggerHype() {
    const inp = document.getElementById('hype-file');
    inp.value = '';
    inp.onchange = () => handleHype(inp.files);
    inp.click();
}
async function handleHype(files) {
    if (!files || !files.length) return;
    const crown = document.getElementById('uploadCrown');
    const txt   = document.getElementById('ucText');
    crown.classList.add('active');
    setCrownProgress(0);
    if (txt) startUploadLines(txt);
    try {
        const file = await prepFile(files[0]);
        const fd = new FormData(); fd.append('file', file);
        const data = await uploadWithProgress(fd, p => setCrownProgress(p * 100), 'api/hype.php');
        setCrownProgress(100);
        if (data && data.error) {
            clearInterval(uploadLineTimer);
            crown.classList.remove('active');
            alert(data.error);
            return;
        }
        clearInterval(uploadLineTimer);
        if (txt) txt.textContent = 'עלה!';
        setTimeout(() => { crown.classList.remove('active'); setCrownProgress(0); loadHype(); }, 700);
    } catch {
        clearInterval(uploadLineTimer);
        crown.classList.remove('active');
        alert('שגיאה בהעלאה');
    }
}
async function deleteHype(id) {
    if (!confirm('למחוק את התמונה?')) return;
    try {
        const res = await fetch('api/hype.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete', id }) });
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        loadHype();
    } catch { alert('שגיאה במחיקה'); }
}

/* ===== Blessings ===== */
async function loadBlessings(day) {
    const statusEl = document.getElementById(`bless-status-${day}`);
    const listEl   = document.getElementById(`bless-list-${day}`);
    try {
        const res  = await fetch(`api/blessing.php?day=${day}`);
        const data = await res.json();
        const myBlessing = data.blessings?.find(b => b.user_id === USER_ID);

        if (myBlessing) {
            statusEl.innerHTML = `<div class="blessing-sent">
                <div class="check">${ICONS.check} ברכתך נשלחה</div>
                <div class="content">${esc(myBlessing.content)}</div>
            </div>`;
        } else {
            statusEl.innerHTML = `<button class="btn-bless" onclick="openBlessModal(${day})">
                ${ICONS.star} כתוב ברכה לנועם
            </button>`;
        }

        const others = (data.blessings || []).filter(b => b.user_id !== USER_ID);
        listEl.innerHTML = others.map(b => `
            <div class="blessing-card">
                <div class="bless-author">${esc(b.name)}</div>
                <div class="bless-text">${esc(b.content)}</div>
            </div>
        `).join('');
    } catch {
        statusEl.innerHTML = '<div class="empty-state">שגיאה בטעינת ברכות</div>';
    }
}

function openBlessModal(day) {
    blessModal_day = day;
    document.getElementById('blessText').value = '';
    document.getElementById('blessSubmitBtn').disabled = false;
    openModal('blessModal');
}

async function submitBlessing() {
    const content = document.getElementById('blessText').value.trim();
    if (!content) { alert('כתוב ברכה לפני שליחה'); return; }
    const btn = document.getElementById('blessSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'שולח...';
    try {
        const res  = await fetch('api/blessing.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ day: blessModal_day, content }),
        });
        const data = await res.json();
        if (data.error) { alert(data.error); btn.disabled = false; btn.textContent = 'שלח ברכה'; return; }
        closeModal('blessModal');
        loadedDays.delete(blessModal_day);
        loadBlessings(blessModal_day);
    } catch {
        alert('שגיאה בשליחת הברכה');
        btn.disabled = false;
        btn.textContent = 'שלח ברכה';
    }
}

/* ===== Gallery ===== */
async function loadGallery(day) {
    const grid = document.getElementById(`gallery-grid-${day}`);
    try {
        const res  = await fetch(`api/upload.php?day=${day}`);
        const data = await res.json();
        dailyMax = data.max || 6;
        if (day === PHOTO_DAY) updatePhotoQuota(data.my_count || 0);
        renderGallery(day, data.uploads || []);
    } catch {
        grid.innerHTML = '<div class="empty-state">שגיאה בטעינת גלריה</div>';
    }
}

/* תג ההקצאה בהאדר: + לפני ההעלאה הראשונה, אחר כך 1/6 */
function updatePhotoQuota(count) {
    const chip = document.getElementById('hdrQuota');
    if (chip) {
        const left = dailyMax - count;
        // לפני העלאה ראשונה: + | באמצע: כמה נשאר | סיימת: וי
        chip.textContent = count === 0 ? '+' : (left <= 0 ? '✓' : String(left));
    }
    window._myPhotoCount = count;
    updateGalAdd();
}

/* הפלוס ליד "גלריה" - רק כשכבר יש תמונות היום ונשארה הקצאה */
function updateGalAdd() {
    const addBtn = document.getElementById(`gal-add-${PHOTO_DAY}`);
    if (!addBtn) return;
    const hasPhotos = (dayPhotos[PHOTO_DAY] || []).length > 0;
    addBtn.style.display = hasPhotos && (window._myPhotoCount || 0) < dailyMax ? '' : 'none';
}


function trashBtn(u, day) {
    // פח רק על תמונות שלי, ורק ביום הנוכחי (אז ההקצאה חוזרת)
    if (u.user_id !== USER_ID || day !== PHOTO_DAY) return '';
    return `<button class="photo-trash" onclick="event.stopPropagation(); deletePhoto(${u.id})" aria-label="מחק"><svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"3 6 5 6 21 6\"/><path d=\"M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2\"/></svg></button>`;
}

async function deletePhoto(id) {
    if (!confirm('למחוק את התמונה? ההקצאה תחזור אליך.')) return;
    try {
        const res = await fetch('api/upload.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id }),
        });
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        loadGallery(PHOTO_DAY);
    } catch { alert('שגיאה במחיקה'); }
}

const dayPhotos = {}; // day -> uploads list (ללייטבוקס)

function renderGallery(day, uploads) {
    const grid = document.getElementById(`gallery-grid-${day}`);
    // התמונות שלי קודם, אחר כך של כולם
    uploads = [...uploads.filter(u => u.user_id === USER_ID), ...uploads.filter(u => u.user_id !== USER_ID)];
    dayPhotos[day] = uploads;
    if (day === PHOTO_DAY) updateGalAdd();
    if (uploads.length === 0) {
        grid.innerHTML = day === PHOTO_DAY
            ? `<div class="gal-empty"><p class="gal-empty-text">יאללה, מי עושה סיפתח להיום?</p><button class="btn-upload gal-empty-btn" onclick="triggerDailyUpload()">${ICONS.upload} העלאת תמונה</button></div>`
            : `<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">${ICONS.camera}</div>אין תמונות מהיום הזה</div>`;
        return;
    }
    grid.innerHTML = uploads.map((u, i) => {
        const src = `uploads/${esc(u.filename)}`;
        const media = u.type === 'video'
            ? `<video src="${src}" playsinline muted loop></video>`
            : `<img src="${src}" alt="תמונה" loading="lazy">`;
        return `<div class="gallery-item" onclick="openLightbox(${day}, ${i})">${media}${trashBtn(u, day)}<span class="uploader-tag">${esc(u.short_name)}</span></div>`;
    }).join('');
}

/* ===== Lightbox ===== */
let lbxDay = 0, lbxIdx = 0;
function openLightbox(day, i) {
    if (!dayPhotos[day] || !dayPhotos[day].length) return;
    lbxDay = day; lbxIdx = i;
    renderLightbox();
    document.getElementById('lightbox').classList.add('open');
}
function renderLightbox() {
    const list = dayPhotos[lbxDay] || [];
    const u = list[lbxIdx];
    if (!u) return;
    const src = `uploads/${esc(u.filename)}`;
    document.getElementById('lbxMedia').innerHTML = u.type === 'video'
        ? `<video src="${src}" playsinline controls autoplay loop></video>`
        : `<img src="${src}" alt="תמונה">`;
    document.getElementById('lbxCaption').textContent = `${u.short_name} · ${lbxIdx + 1}/${list.length}`;
    const multi = list.length > 1;
    document.querySelector('.lbx-prev').style.display = multi ? '' : 'none';
    document.querySelector('.lbx-next').style.display = multi ? '' : 'none';
}
function lightboxStep(dir) {
    const list = dayPhotos[lbxDay] || [];
    lbxIdx = (lbxIdx + dir + list.length) % list.length;
    renderLightbox();
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.getElementById('lbxMedia').innerHTML = '';
}
(function(){
    const lb = document.getElementById('lightbox');
    if (!lb) return;
    lb.addEventListener('click', e => { if (e.target === lb) closeLightbox(); });
    let sx = null;
    lb.addEventListener('touchstart', e => { sx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', e => {
        if (sx === null) return;
        const dx = e.changedTouches[0].clientX - sx;
        if (Math.abs(dx) > 45) lightboxStep(dx > 0 ? -1 : 1);
        sx = null;
    });
    document.addEventListener('keydown', e => {
        if (!lb.classList.contains('open')) return;
        if (e.key === 'ArrowLeft') lightboxStep(1);
        if (e.key === 'ArrowRight') lightboxStep(-1);
    });
})();

function triggerUpload(day) {
    const input = document.getElementById(`file-input-${day}`);
    input.value = '';
    input.onchange = () => handleFiles(day, input.files);
    input.click();
}

async function handleFiles(day, files) {
    if (!files || files.length === 0) return;
    const fileArr = Array.from(files).slice(0, 5);
    const prog    = document.getElementById(`progress-${day}`);
    const fill    = document.getElementById(`progress-fill-${day}`);
    prog.classList.add('active');

    for (let i = 0; i < fileArr.length; i++) {
        const file = await prepFile(fileArr[i]);
        fill.style.width = `${Math.round(((i) / fileArr.length) * 100)}%`;
        const fd = new FormData();
        fd.append('day', day);
        fd.append('file', file);
        try {
            const res  = await fetch('api/upload.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.error) { alert(data.error); break; }
        } catch {
            alert('שגיאה בהעלאת הקובץ');
            break;
        }
    }

    fill.style.width = '100%';
    setTimeout(() => {
        prog.classList.remove('active');
        fill.style.width = '0%';
        loadedDays.delete(day);
        loadGallery(day);
    }, 500);
}

/* המרת HEIC של אייפון ל-JPEG בצד הלקוח (השרת לא יודע לקרוא HEIC).
   הספרייה נטענת רק כשבאמת מגיע קובץ HEIC - לא מכבידה על הטעינה הרגילה. */
let _heicLibPromise = null;
function loadHeicLib() {
    if (window.heic2any) return Promise.resolve();
    if (!_heicLibPromise) {
        _heicLibPromise = new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'assets/heic2any.min.js?v=1';
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }
    return _heicLibPromise;
}
function _loadImg(src) {
    return new Promise((res, rej) => { const im = new Image(); im.onload = () => res(im); im.onerror = rej; im.src = src; });
}
/* הכנת קובץ להעלאה: ממיר דרך canvas ל-JPEG ומקטין ל-2200px.
   אייפון מפענח HEIC נייטיב -> מהיר ואמין. אם הדפדפן לא יודע לפענח (אנדרואיד+HEIC) - גיבוי heic2any. */
async function prepFile(file) {
    const isHeic = /image\/(heic|heif)/i.test(file.type) || /\.(heic|heif)$/i.test(file.name || '');
    const toJpeg = (img) => {
        const max = 2200;
        const scale = Math.min(1, max / Math.max(img.naturalWidth || img.width, img.naturalHeight || img.height));
        const cw = Math.round((img.naturalWidth || img.width) * scale);
        const ch = Math.round((img.naturalHeight || img.height) * scale);
        const cv = document.createElement('canvas'); cv.width = cw; cv.height = ch;
        cv.getContext('2d').drawImage(img, 0, 0, cw, ch);
        return new Promise(r => cv.toBlob(r, 'image/jpeg', 0.85));
    };
    // 1) ניסיון נייטיב דרך canvas (עובד ל-HEIC באייפון ולכל פורמט רגיל)
    try {
        const url = URL.createObjectURL(file);
        const img = await _loadImg(url);
        const blob = await toJpeg(img);
        URL.revokeObjectURL(url);
        if (blob) return new File([blob], (file.name || 'photo').replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
    } catch {}
    // 2) גיבוי: HEIC שהדפדפן לא פיענח
    if (isHeic) {
        try {
            await loadHeicLib();
            const b = await window.heic2any({ blob: file, toType: 'image/jpeg', quality: 0.85 });
            const o = Array.isArray(b) ? b[0] : b;
            return new File([o], (file.name || 'photo').replace(/\.(heic|heif)$/i, '') + '.jpg', { type: 'image/jpeg' });
        } catch {}
    }
    return file; // מוצא אחרון
}

/* ===== Daily photos hub (modal) ===== */
/* תמונות עולות ומוצגות רק ליום הנוכחי של הטיול - אין העלאה אחורה או קדימה */
const PHOTO_DAY = Math.max(1, Math.min(5, TRIP_DAY_NOW || 1));
let dailyMax = 6;
function triggerDailyUpload() {
    const input = document.getElementById('daily-file-input');
    input.value = '';
    input.onchange = () => handleDailyFiles(input.files);
    input.click();
}
function setCrownProgress(pct) {
    const water = document.getElementById('ucWater');
    if (water) water.style.height = `${pct}%`;
}

/* שטויות שמתחלפות בזמן ההעלאה */
const UPLOAD_LINES = [
    'התמונה עולה...',
    'זה תמונה או סרטון? מה עשית שם?',
    'אה?',
    'רגע, זה עולה',
    'שנייה, האינטרנט ביוון',
    'וואו, כבד. מה צילמת שם?',
    'עוד רגע זה שם',
    'הכתר מתמלא, תירגע',
    'אל תזוז, זה באמצע',
    'יפה צילמת. כנראה.',
    'טוען... כמו הכבד שלך',
    'זה עולה, נשבע',
    'נועם יאהב את זה',
    'אחלה פריים אחי',
    'כמעט שם...',
];
let uploadLineTimer = null;
function startUploadLines(txt) {
    let i = Math.floor(Math.random() * UPLOAD_LINES.length);
    txt.textContent = UPLOAD_LINES[i];
    clearInterval(uploadLineTimer);
    uploadLineTimer = setInterval(() => {
        i = (i + 1) % UPLOAD_LINES.length;
        txt.textContent = UPLOAD_LINES[i];
    }, 2000);
}

/* העלאה עם XHR כדי לקבל progress אמיתי - הכתר מתמלא לפי ההתקדמות */
function uploadWithProgress(fd, onPct, url) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url || 'api/upload.php');
        xhr.upload.onprogress = e => { if (e.lengthComputable) onPct(e.loaded / e.total); };
        xhr.onload = () => { try { resolve(JSON.parse(xhr.responseText)); } catch { reject(); } };
        xhr.onerror = () => reject();
        xhr.send(fd);
    });
}

async function handleDailyFiles(files) {
    if (!files || !files.length) return;
    const left = dailyMax - (window._myPhotoCount || 0);
    if (left <= 0) { alert(`ניצלת את כל ${dailyMax} התמונות להיום`); return; }
    const arr   = Array.from(files).slice(0, left);
    const crown = document.getElementById('uploadCrown');
    const txt   = document.getElementById('ucText');
    crown.classList.add('active');
    setCrownProgress(0);
    if (txt) startUploadLines(txt);
    for (let i = 0; i < arr.length; i++) {
        const fd = new FormData();
        fd.append('day', PHOTO_DAY);
        fd.append('file', await prepFile(arr[i]));
        try {
            const data = await uploadWithProgress(fd, p => setCrownProgress(((i + p) / arr.length) * 100));
            if (data.error) { alert(data.error); break; }
        } catch { alert('שגיאה בהעלאת הקובץ'); break; }
    }
    setCrownProgress(100);
    clearInterval(uploadLineTimer);
    if (txt) txt.textContent = 'עלה!';
    setTimeout(() => {
        crown.classList.remove('active');
        setCrownProgress(0);
        loadGallery(PHOTO_DAY);
    }, 700);
}

/* ===== Return flight countdown (day 5) ===== */
const RETURN_MS = new Date(2026, 6, 5, 14, 15, 0).getTime();
let fhTimer = null;
function startFlightCountdown() {
    const el = document.getElementById('fhCount');
    if (!el || fhTimer) return;
    const tick = () => {
        const diff = RETURN_MS - Date.now();
        if (diff <= 0) { el.innerHTML = '<div class="fh-done">ההמראה מאחורינו. נתראה בארץ.</div>'; clearInterval(fhTimer); return; }
        const h = Math.floor(diff / 3600000), m = Math.floor(diff / 60000) % 60, sec = Math.floor(diff / 1000) % 60;
        el.innerHTML = `
            <div class="countdown fh-countdown">
                <div class="cd-box"><span class="cd-num">${h}</span><span class="cd-label">שעות</span></div>
                <div class="cd-box"><span class="cd-num">${m}</span><span class="cd-label">דקות</span></div>
                <div class="cd-box"><span class="cd-num">${sec}</span><span class="cd-label">שניות</span></div>
            </div>`;
    };
    tick();
    fhTimer = setInterval(tick, 1000);
}

/* ===== Modal helpers ===== */
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('click', () => document.querySelectorAll('.info-wrap.open').forEach(w => w.classList.remove('open')));
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

/* ===== Swipe-down to close ===== */
document.querySelectorAll('.modal-sheet').forEach(sheet => {
    let startY = null, dy = 0;
    sheet.addEventListener('touchstart', e => { startY = e.touches[0].clientY; dy = 0; }, {passive:true});
    sheet.addEventListener('touchmove', e => {
        if (startY === null) return;
        dy = e.touches[0].clientY - startY;
        if (dy > 0) sheet.style.transform = `translateY(${dy}px)`;
    }, {passive:true});
    sheet.addEventListener('touchend', () => {
        sheet.style.transform = '';
        if (dy > 90) sheet.closest('.modal-overlay').classList.remove('open');
        startY = null;
    });
});

/* ===== Nav ===== */
function showDays() {
    document.getElementById('appMain').style.display = '';
}

/* ===== Escape helper ===== */
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ===== Suggestions (teaser mode) ===== */
async function loadSuggestions() {
    const list = document.getElementById('suggestList');
    if (!list) return;
    try {
        const res  = await fetch('api/suggest.php');
        const data = await res.json();
        renderSuggestions(data);
    } catch {
        list.innerHTML = '<div class="empty-state">שגיאה בטעינת הצעות</div>';
    }
}

function renderSuggestions(data) {
    const list  = document.getElementById('suggestList');
    const count = document.getElementById('sugCount');
    const btn   = document.getElementById('sugSubmit');
    const reached = data.my_count >= data.max;

    // לפני שיתוף ראשון: אייקון i עם הסבר. אחרי: רק חיווי המספרים - היוזר כבר מכיר.
    const info = document.getElementById('sugInfo');
    if (count) {
        if (data.my_count > 0) {
            count.textContent = `${data.my_count}/${data.max}`;
            count.style.display = '';
            if (info) info.style.display = 'none';
        } else {
            count.style.display = 'none';
            if (info) info.style.display = '';
        }
    }
    const urlEl = document.getElementById('sugUrl');
    if (urlEl) urlEl.disabled = reached;
    const form = document.getElementById('suggestForm');
    if (form) form.style.display = reached ? 'none' : '';
    toggleSugSend();

    if (!data.suggestions.length) {
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">' + ICONS.pin + '</div>עוד אין הצעות. תהיו הראשונים.</div>';
        return;
    }

    list.innerHTML = data.suggestions.map(s => {
        const del = s.mine
            ? `<button class="sug-del" onclick="event.preventDefault();event.stopPropagation();deleteSuggestion(${s.id})" aria-label="מחק">×</button>`
            : '';
        const title = (s.name && s.name.trim()) ? esc(s.name) : 'מקום ששותף';
        let host = s.site;
        if (!host) { try { host = new URL(s.url).hostname.replace(/^www\./,''); } catch {} }
        const thumb = s.image
            ? `<div class="sug-thumb"><img src="${esc(s.image)}" alt="" loading="lazy" onerror="this.parentNode.classList.add('noimg')"></div>`
            : `<div class="sug-thumb noimg">${ICONS.pin}</div>`;
        return `<a class="sug-card" href="${esc(s.url)}" target="_blank" rel="noopener">
            ${thumb}
            <div class="sug-body">
                <div class="sug-title">${title}</div>
                <div class="sug-meta">
                    <span class="sug-host">${esc(host || 'קישור')}</span>
                    <span class="sug-by">${esc(s.by)}</span>
                </div>
            </div>
            ${del}
        </a>`;
    }).join('');
}

function toggleSugForm() {
    const form = document.getElementById('suggestForm');
    const btn = document.getElementById('sugToggle');
    const open = form.classList.toggle('open');
    btn.classList.toggle('to-x', open);
    if (open) setTimeout(() => document.getElementById('sugUrl').focus(), 250);
    else { document.getElementById('sugUrl').value = ''; toggleSugSend(); hideSugError(); }
}

function isLink(v) {
    return /^(https?:\/\/|www\.)\S+$/i.test(v) || /^[\w-]+(\.[\w-]+)+(\/\S*)?$/i.test(v);
}
function hideSugError() { document.getElementById('sugError')?.classList.remove('show'); document.getElementById('sugUrl')?.classList.remove('err'); }

function toggleSugSend() {
    const urlEl = document.getElementById('sugUrl');
    const btn = document.getElementById('sugSubmit');
    if (!urlEl || !btn) return;
    btn.classList.toggle('visible', urlEl.value.trim().length > 0);
    hideSugError();
}

async function submitSuggestion() {
    const urlEl = document.getElementById('sugUrl');
    const url = urlEl.value.trim();
    if (!url) return;
    if (!isLink(url)) {
        urlEl.classList.add('err');
        document.getElementById('sugError').classList.add('show');
        return;
    }
    const btn = document.getElementById('sugSubmit');
    btn.disabled = true;
    try {
        const res  = await fetch('api/suggest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', name: '', url, note: '' }),
        });
        const data = await res.json();
        if (data.error) { alert(data.error); btn.disabled = false; return; }
        document.getElementById('sugUrl').value = '';
        btn.disabled = false;
        toggleSugForm(); // נסגר אחרי שליחה
        loadSuggestions();
    } catch {
        alert('שגיאה בשיתוף');
        btn.disabled = false;
    }
}

async function deleteSuggestion(id) {
    if (!confirm('למחוק את ההצעה?')) return;
    try {
        await fetch('api/suggest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id }),
        });
        loadSuggestions();
    } catch {
        alert('שגיאה במחיקה');
    }
}

/* ===== Countdown (teaser mode) ===== */
function tickCountdown() {
    const el = document.getElementById('countdown');
    if (!el) return;
    const diff = FLIGHT_MS - Date.now();
    if (diff <= 0) { el.innerHTML = '<div class="cd-open">ממריאים! רעננו את הדף</div>'; return; }
    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    const cell = (v, l) => `<div class="cd-cell"><span class="cd-num">${v}</span><span class="cd-lbl">${l}</span></div>`;
    el.innerHTML = cell(d,'ימים') + cell(h,'שעות') + cell(m,'דקות') + cell(s,'שניות');
}

/* ===== Spin wheel ===== */
const WHEEL_LINES = [
    'דרכון, ארנק, כבוד עצמי. בערך בסדר הזה.',
    'מי ששוכח מטען - ישן בלובי.',
    'הטיסה ב-10:00. לא ב-10:00 שלכם.',
    'מי שמאחר לשדה - משלם סיבוב.',
    'אל תשכחו את נועם. הוא החתן.',
    'תטעינו את הטלפון. יש פה אפליקציה.',
    'מי שמביא רמקול - אחראי על הפלייליסט.',
    'תישנו בטיסה. הלילה ארוך.',
    'שתו מים בין הבירות. מתישהו.',
    'תביאו מטען נייד. תאמינו לי.',
    'בלי לאבד את הקבוצה ביום הראשון.',
    'מי שמקיא במונית - מנקה את המונית.',
    'תצלמו הכל. בשביל הסחיטות אחר כך.',
    'אל תסמכו על הזיכרון. תסמכו על האפליקציה.',
];

function buildWheel() {
    const wheel = document.getElementById('wheel');
    if (!wheel || !WHEEL_FACES.length) return;
    const n = WHEEL_FACES.length;
    const seg = 360 / n;
    wheel.innerHTML = WHEEL_FACES.map((f, i) => {
        const angle = i * seg + seg / 2;
        const img = f.photo
            ? `<img src="${esc(f.photo)}" alt="${esc(f.name)}">`
            : `<span>${esc(f.name.charAt(0))}</span>`;
        const crown = f.groom ? `<span class="wheel-crown">${CROWN}</span>` : '';
        return `<div class="wheel-face${f.groom ? ' groom' : ''}" data-angle="${angle}" style="transform: rotate(${angle}deg) translateY(${-WHEEL_RADIUS}px) rotate(${-angle}deg)"><div class="wheel-face-inner">${img}</div>${crown}</div>`;
    }).join('');
}

let wheelSpinning = false;
let wheelRot = 0;
const WHEEL_RADIUS = 96;
const WHEEL_EASE = 'transform 4.6s cubic-bezier(0.12, 0.8, 0.16, 1)';
let wheelTimer = null;
let wheelBoosts = 0;

function applyWheelTransform(wheel, ease) {
    const tr = ease || WHEEL_EASE;
    wheel.style.transition = tr;
    wheel.style.transform = `rotate(${wheelRot}deg)`;
    // keep faces upright while the wheel spins
    wheel.querySelectorAll('.wheel-face').forEach(f => {
        const a = +f.dataset.angle;
        f.style.transition = tr;
        f.style.transform = `rotate(${a}deg) translateY(${-WHEEL_RADIUS}px) rotate(${-a - wheelRot}deg)`;
    });
}

function finishSpin() {
    const wheel  = document.getElementById('wheel');
    const result = document.getElementById('wheelResult');
    const stage  = wheel.closest('.wheel-stage');
    wheelSpinning = false;
    wheelBoosts = 0;
    if (stage) stage.classList.remove('spinning');
    const line = WHEEL_LINES[Math.floor(Math.random() * WHEEL_LINES.length)];
    result.innerHTML = `<div class="wheel-bubble">${esc(line)}</div>`;
    result.classList.add('show');
    // הציטוט נעלם לבד בפייד אחרי כמה שניות
    clearTimeout(window._bubbleTimer);
    window._bubbleTimer = setTimeout(() => result.classList.remove('show'), 3200);
}

function spinWheel() {
    const wheel  = document.getElementById('wheel');
    const result = document.getElementById('wheelResult');
    const n = WHEEL_FACES.length;
    const seg = 360 / n;

    // טאפ נוסף באמצע סיבוב = עוד תנופה ומהירות. כפולות של 360 שומרות על אותה נחיתה.
    if (wheelSpinning) {
        if (wheelBoosts >= 8) return; // שלא יסתובב לנצח
        wheelBoosts++;
        wheelRot += 720;
        // דחיפה: סיבוב קצר וזריז יותר - מרגישים את ההאצה מיד
        applyWheelTransform(wheel, 'transform 3.2s cubic-bezier(0.16, 0.9, 0.18, 1)');
        const c = document.getElementById('wheelSpin');
        if (c) { c.classList.remove('pop'); void c.offsetWidth; c.classList.add('pop'); }
        clearTimeout(wheelTimer);
        wheelTimer = setTimeout(finishSpin, 3300);
        return;
    }

    wheelSpinning = true;
    result.classList.remove('show');
    const stage = wheel.closest('.wheel-stage');
    if (stage) stage.classList.add('spinning');

    const idx = Math.floor(Math.random() * n);
    const spins = 5 + Math.floor(Math.random() * 3);

    // absolute target so the chosen face lands at the BOTTOM (under the pointer)
    const current = ((wheelRot % 360) + 360) % 360;
    const targetAngle = ((180 - (idx * seg + seg / 2)) % 360 + 360) % 360;
    const delta = (targetAngle - current + 360) % 360;
    wheelRot += spins * 360 + delta;

    applyWheelTransform(wheel);
    wheelTimer = setTimeout(finishSpin, 4700);
}

/* ===== Scroll reveal ===== */
function initReveal(root) {
    const els = (root || document).querySelectorAll('.reveal:not(.in)');
    if (!('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('in')); return; }
    const io = new IntersectionObserver((entries) => {
        entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    els.forEach(e => io.observe(e));
}

/* ===== Init ===== */
if (LOCKED) {
    loadSuggestions();
    tickCountdown();
    setInterval(tickCountdown, 1000);
    buildWheel();
    const ws = document.getElementById('wheelSpin');
    if (ws) ws.addEventListener('click', spinWheel);
} else {
    switchDay(currentDay);
    if (COLLECTION_MODE) { tickCountdown(); setInterval(tickCountdown, 1000); loadPacking(); loadHype(); }
    // חזרה מעמוד משחק - גוללים לסקשן המשחקים אחרי שהתוכן נטען
    if (location.hash === '#games') {
        setTimeout(() => document.getElementById('games')?.scrollIntoView({ behavior: 'smooth' }), 350);
    }
}
initReveal();
</script>
<script>
/* no service worker - remove any stale one + clear caches (fixes wrong-page caching) */
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(rs => rs.forEach(r => r.unregister()));
}
if (window.caches) { caches.keys().then(ks => ks.forEach(k => caches.delete(k))); }
</script>
<script src="assets/profile.js?v=<?= filemtime(__DIR__ . '/assets/profile.js') ?>"></script>
<?php include __DIR__ . '/demo_guard.php'; ?>
</body>
</html>
