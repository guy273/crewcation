<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

$user_id = require_login();
$locked  = app_locked();
// משחקים זמינים גם לפני הטיסה - חלק מהטיזר

$game = $_GET['g'] ?? 'wheel';
$titles = ['wheel' => 'הגלגל האהוב', 'tetris' => 'משחק הזיכרון', 'candy' => 'קנדי ראס', 'bowling' => 'הגנת החתן'];
$rules = [
    'wheel'   => 'מסובבים, יוצא מישהו, הוא עושה מה שיוצא. אין ערעורים, אין בכי. טאפים באמצע מאיצים את הגלגל.',
    'tetris'  => 'מקווה שאתה צלול כרגע. אחרת זה אבוד לך.',
    'candy'   => 'גוררים פרצוף אל השכן. שלושה זהים ברצף = בום. שרשראות שוות יותר כבוד.',
    'bowling' => 'גוררים את נועם, הלייזר יורה לבד. כוכב שנופל = נשק משודרג ל-20 שניות. בחורה שעוברת את הקו - נגמר הסיפור.',
];
if (!isset($titles[$game])) $game = 'wheel';

$womenImgs = [];
foreach (glob(__DIR__ . '/assets/women/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [] as $w) {
    $womenImgs[] = 'assets/women/' . basename($w);
}

$gameFaces = [];
foreach (USERS as $uid => $u) {
    foreach (['webp','png','jpg','jpeg'] as $ext) {
        if (file_exists(__DIR__ . "/assets/members/{$uid}.{$ext}")) {
            $gameFaces[] = ['name' => explode(' ', $u['name'])[0], 'photo' => "assets/members/{$uid}.{$ext}", 'groom' => !empty($u['is_groom'])];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#06060a">
    <title><?= $titles[$game] ?> - <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/crown.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body class="game-page">
<div class="app-bg-depth" aria-hidden="true"></div><div class="app-bg-sheen" aria-hidden="true"></div>

<header class="app-header game-header">
    <a class="game-back" href="app.php#games" aria-label="חזרה">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    <div class="logo-area"><h1><?= $titles[$game] ?></h1></div>
    <span class="game-back-spacer"></span>
</header>

<main class="app-wrap game-main">

<!-- עמוד כניסה: חוקים, שיאן, התחל. בהגנת החתן הדמו רץ מאחורה -->
<div class="game-intro <?= $game === 'bowling' ? 'gi-overlay' : '' ?>" id="gameIntro">
    <p class="gi-rules"><?= $rules[$game] ?></p>
    <p class="gi-top" id="giTop"></p>
    <button class="go-restart gi-start" onclick="startGame()">התחל משחק</button>
    <?php if ($game === 'tetris'): ?>
    <a class="gi-bail" href="app.php#games">צודק, לא זמן מתאים</a>
    <?php endif; ?>
</div>

<div id="gameBody" style="display:none">
<?php if ($game === 'wheel'): ?>
    <div class="wheel-top" id="wheelTop"></div>
    <div class="wheel-section">
        <div class="wheel-stage">
            <div class="wheel" id="wheel"></div>
            <button class="wheel-center" id="wheelSpin" aria-label="סובב">סובב</button>
            <div class="wheel-pointer"><svg viewBox="0 0 28 24" aria-hidden="true"><defs><linearGradient id="ptg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#D4A017"/><stop offset="1" stop-color="#FFE89A"/></linearGradient></defs><polygon points="14,20 5,5 23,5" fill="url(#ptg)" stroke="url(#ptg)" stroke-width="4" stroke-linejoin="round"/></svg></div>
        </div>
        <div class="wheel-result" id="wheelResult"></div>
    </div>
<?php elseif ($game === 'tetris'): ?>
    <div class="game-top">
        <div class="gt-champ" id="gtChamp"></div>
        <div class="game-score" id="tetrisScore"></div>
    </div>
    <div class="mem-levels" id="memLevels">
        <p class="mem-q">כמה קלפים?</p>
        <div class="mem-level-btns">
            <button class="meal-tab" onclick="gMemory.level(8)">8</button>
            <button class="meal-tab" onclick="gMemory.level(12)">12</button>
            <button class="meal-tab" onclick="gMemory.level(24)">24</button>
            <button class="meal-tab" onclick="gMemory.level(32)">32</button>
        </div>
    </div>
    <div class="tetris-wrap mem-wrap">
        <div id="memGrid" class="mem-grid" style="display:none"></div>
        <div class="game-over" id="memOver">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><circle cx="9" cy="10" r="0.7" fill="currentColor"/><circle cx="15" cy="10" r="0.7" fill="currentColor"/><path d="M8.5 15.5c1 1.3 2.2 2 3.5 2s2.5-0.7 3.5-2"/></svg>
            <div class="go-text"></div>
            <button class="go-restart" onclick="gMemory.start()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg> עוד פעם</button>
        </div>
    </div>
<?php elseif ($game === 'candy'): ?>
    <div class="game-top">
        <div class="candy-timer" id="candyTimer">1:07</div>
        <div class="gt-champ" id="gtChamp"></div>
        <div class="gt-metric"><span class="gt-big" id="candyScore">0</span><span class="gt-label">ניקוד</span></div>
    </div>
    <div id="candyBoard" class="candy-board"></div>
    <p class="game-hint">החלף שני פרצופים סמוכים. שלושה ברצף = בום.</p>
    <div class="candy-over" id="candyOver">
        <div class="co-card">
            <div class="co-crown"><svg viewBox="0 0 100 100" aria-hidden="true"><path fill="#FFD966" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="#FFD966"/></svg></div>
            <div class="co-score" id="candyOverScore">0</div>
            <div class="co-sub">הזמן נגמר</div>
            <button class="go-restart" onclick="gCandy.start()">עוד פעם</button>
        </div>
    </div>
<?php else: ?>
    <div class="game-top">
        <div class="gt-champ" id="gtChamp"></div>
        <div class="gt-metric"><span class="gt-big" id="bowlScore">0</span><span class="gt-label">ניקוד</span></div>
    </div>
    <div class="def-timer" id="defTimer" style="display:none">20</div>
    <div class="tetris-wrap">
        <canvas id="bowlCanvas" class="game-canvas"></canvas>
        <div class="game-over" id="defOver">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><circle cx="9" cy="10" r="0.7" fill="currentColor"/><circle cx="15" cy="10" r="0.7" fill="currentColor"/><path d="M8.5 16c1-1.3 2.2-2 3.5-2s2.5 0.7 3.5 2"/></svg>
            <div class="go-text">נפסלת. היא הגיעה אליו.</div>
            <button class="go-restart" onclick="gDefense.restart()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg> התחל מחדש</button>
        </div>
    </div>
    <p class="game-hint">גרור את נועם. הלייזר אוטומטי. אל תיתן להן להגיע.</p>
<?php endif; ?>
</div>
</main>

<script>
const GAME_FACES = <?= json_encode($gameFaces, JSON_UNESCAPED_UNICODE) ?>;
const WOMEN_POOL = <?= json_encode($womenImgs) ?>;
const WHEEL_FACES = GAME_FACES;
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
const CROWN = '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path fill="#FFD966" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="#FFD966"/></svg>';
const WHEEL_LINES = [
    'תנשום. אתה בחופשה.',
    'שתה מים. עכשיו.',
    'הבריכה לא הולכת לשום מקום. גם אתה.',
    'איפה נועם? תוודא שהוא חי.',
    'גירוס עכשיו. בלי לחשוב פעמיים.',
    'עוד קפה יווני לא יזיק לאף אחד.',
    'מי שמדבר על עבודה - שותה.',
    'תורידו הילוך. זה החופש.',
    'השעה הכי טובה לבריכה היא עכשיו.',
    'צהריים זה מצב נפשי.',
    'קרם הגנה. מישהו כבר דאג, אבל בכל זאת.',
    'הכבד שלכם עובד קשה. תעריכו אותו.',
    'ספירת ראשים: חמישה? מצוין.',
    'עוד סיבוב? עוד סיבוב.',
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
</script>
<script src="assets/games.js?v=<?= filemtime(__DIR__ . '/assets/games.js') ?>"></script>
<script>
const G = <?= json_encode($game) ?>;
let wheelWired = false;
const CHAMP_CROWN = '<span class="gtc-crown"><svg viewBox="0 0 100 100" aria-hidden="true"><path fill="#FFD966" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="#FFD966"/></svg></span>';

// שמירת שיא + טעינת שיאן (משותף לכל המשחקים עם שיאן)
window.saveScore = function (game, score, cb) {
    fetch('api/score.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ game, score }) })
        .then(() => cb && cb()).catch(() => {});
};
window.loadChamp = function (game) {
    const el = document.getElementById('gtChamp');
    if (!el) return;
    fetch('api/score.php?game=' + game).then(r => r.json()).then(d => {
        const t = d.top;
        if (!t) { el.innerHTML = '<span class="gtc-empty">עוד אין שיאן. תהיה הראשון.</span>'; return; }
        const img = t.photo ? `<img src="${t.photo}" alt="">` : '';
        el.innerHTML = `${CHAMP_CROWN}${img}<span class="gtc-meta"><span class="gtc-name">${t.first}</span><span class="gtc-score">${t.score}</span></span>`;
    }).catch(() => {});
};
window.loadWheelTop = function () {
    const el = document.getElementById('wheelTop');
    if (!el) return;
    fetch('api/game_play.php?game=wheel').then(r => r.json()).then(d => {
        const t = d.top;
        if (!t) { el.innerHTML = ''; return; }
        const img = t.photo ? `<img src="${t.photo}" alt="">` : '';
        el.innerHTML = `<span class="wt-label">הכי מבקר פה</span>${img}<span class="wt-name">${t.first}</span><span class="wt-count">${t.plays} פעמים</span>`;
    }).catch(() => {});
};

// שיאן הבית (טקסט בעמוד הכניסה)
fetch(`api/game_play.php?game=${G}`).then(r => r.json()).then(d => {
    const el = document.getElementById('giTop');
    if (el) el.textContent = d.top ? `שיאן הבית: ${d.top.first} · ${d.top.plays} משחקים` : '';
}).catch(() => {});
if (G === 'wheel') loadWheelTop();
if (G === 'bowling') loadChamp('bowling');

function startGame() {
    fetch('api/game_play.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ game: G }) }).catch(() => {});
    document.getElementById('gameIntro').style.display = 'none';
    const body = document.getElementById('gameBody');
    if (G === 'bowling') {
        // הדמו כבר רץ מאחורה - לוקחים שליטה מאותה נקודה
        gDefense.takeover();
        return;
    }
    body.style.display = '';
    body.classList.add('day-in-next');
    if (G === 'wheel') { if (!wheelWired) { wheelWired = true; buildWheel(); document.getElementById('wheelSpin').addEventListener('click', spinWheel); } }
    if (G === 'tetris') { gMemory.start(); loadChamp('tetris'); }
    if (G === 'candy') gCandy.start();
}

// הגנת החתן: נועם משחק לבד מאחורי עמוד הכניסה - עושה חשק
if (G === 'bowling') {
    document.getElementById('gameBody').style.display = '';
    gDefense.start({ demo: true });
}
// קנדי ראס: בלי לובי - הלוח מסביר את עצמו, ישר למשחק
if (G === 'candy') {
    document.getElementById('gameIntro').style.display = 'none';
    document.getElementById('gameBody').style.display = '';
    gCandy.start();
    fetch('api/game_play.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ game: G }) }).catch(() => {});
}
// הגלגל מוכר מהטיזר - בלי עמוד כניסה, ישר לעניין
if (G === 'wheel') {
    document.getElementById('gameIntro').style.display = 'none';
    document.getElementById('gameBody').style.display = '';
    wheelWired = true;
    buildWheel();
    document.getElementById('wheelSpin').addEventListener('click', spinWheel);
    fetch('api/game_play.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ game: G }) }).catch(() => {});
}
</script>
<script src="assets/profile.js?v=<?= filemtime(__DIR__ . '/assets/profile.js') ?>"></script>
</body>
</html>
