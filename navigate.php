<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

$user_id = require_login();
$locked  = app_locked();
$_ts=mktime(0,0,0,7,1,2026); $_td=(int)floor((time()-$_ts)/86400)+1; if($_td<0)$_td=0; if(is_dev_env()) $_td=isset($_GET['simday'])?max(0,min(5,(int)$_GET['simday'])):3; $featureLocked=collection_mode($_td);
$me      = get_user($user_id);
$myFirst = explode(' ', $me['name'] ?? '')[0];
$myPhoto = member_photo($user_id);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#06060a">
    <title>ניווט למלון - <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/crown.svg">
    <link rel="icon" type="image/png" href="assets/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body>
<div class="app-bg-depth" aria-hidden="true"></div><div class="app-bg-sheen" aria-hidden="true"></div>

<header class="app-header">
    <?php include __DIR__ . '/header_profile.php'; ?>
    <div class="logo-area">
        <svg class="logo-crown" viewBox="0 0 100 100" aria-hidden="true"><defs><linearGradient id="lcg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFD966"/><stop offset="0.5" stop-color="#D4A017"/><stop offset="1" stop-color="#9A7414"/></linearGradient></defs><path fill="url(#lcg)" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="url(#lcg)"/><circle cx="14" cy="32" r="5" fill="#FFD966"/><circle cx="50" cy="20" r="5.5" fill="#FFD966"/><circle cx="86" cy="32" r="5" fill="#FFD966"/></svg>
        <h1><?= htmlspecialchars(APP_NAME) ?></h1>
    </div>
    <?php include __DIR__ . '/header_photo_btn.php'; ?>
</header>
<?php include __DIR__ . '/soon_modal.php'; ?>

<?php if ($featureLocked): ?>
<?php $lockMsg = 'איפה תלך דוד?'; include __DIR__ . '/locked_page.php'; ?>
<?php else: ?>
<main class="app-wrap nav-page">
    <section class="navlog-section">
        <div class="navlog-title" id="navlogTitle" style="display:none">תיעודים</div>
        <div class="navlog-list" id="navlogList"></div>
    </section>
</main>

<!-- סטייט ריק: כותרת + תת-כותרת צמודות מעל הכפתור. נעלמות ברגע שיש תיעוד -->
<div class="members-header nav-empty-head" id="navEmptyHead" style="display:none">
    <h1>או שיכורים או סתם סקרנים</h1>
    <p>כל מי שלחץ נווט - מתועד. מי, מתי, ולמה.</p>
</div>

<!-- כפתור ניווט קבוע מעל הנאבר, הלוג נמוג מתחתיו -->
<div class="bless-fade"></div>
<button class="btn-primary btn-nav-go nav-cta-fixed" onclick="openModal('navModal')">
    <span class="bng-main"><?= icon('navigation', 'i') ?> נווט למלון</span>
    <span class="bng-sub">יא שיכור</span>
</button>

<!-- Modal: Navigation -->
<div class="modal-overlay" id="navModal" onclick="closeModal('navModal')">
    <button class="modal-close" onclick="closeModal('navModal')" aria-label="סגור">&times;</button>
    <div class="modal-sheet" role="dialog" aria-modal="true" tabindex="-1" onclick="event.stopPropagation()">
        <div class="modal-handle"></div>
        <div class="modal-title nav-modal-title">ניווט למלון</div>
        <p class="nav-modal-q">מה הסיבה?</p>
        <div class="nav-reasons">
            <button class="nav-reason" onclick="pickReason(this)">שיכור מדי</button>
            <button class="nav-reason" onclick="pickReason(this)">לא יודע איפה אני</button>
            <button class="nav-reason" onclick="pickReason(this)">מי אני בכלל?</button>
            <button class="nav-reason" onclick="pickReason(this)">איבדתי את החבר'ה</button>
            <button class="nav-reason" onclick="pickReason(this)">צריך מיטה דחוף</button>
            <button class="nav-reason" onclick="pickReason(this)">סתם, ליתר ביטחון</button>
            <button class="nav-reason" onclick="pickReason(this)">דוד שלי חביב</button>
            <button class="nav-reason" onclick="pickReason(this)">על 6 צפון לכפר יסיף</button>
            <button class="nav-reason" onclick="pickReason(this)">אני דולפין</button>
            <button class="nav-reason" onclick="pickReason(this)">אירוע רציני</button>
            <button class="nav-reason" onclick="pickReason(this)">אטצ׳ו (דן מתעטש)</button>
        </div>
        <button class="btn-primary btn-nav-go" id="navGoBtn" disabled onclick="startNav()">
            <span class="bng-main"><?= icon('navigation', 'i') ?> נווט למלון</span>
            <span class="bng-sub">יא שיכור</span>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Bottom Navbar -->
<?php $NAV_ACTIVE = 'nav'; include __DIR__ . '/navbar.php'; ?>

<script>
const HOTEL_URL = <?= json_encode(HOTEL_URL) ?>;
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function pickReason(btn){
    document.querySelectorAll('.nav-reason').forEach(b => b.classList.remove('picked'));
    btn.classList.add('picked');
    const go = document.getElementById('navGoBtn');
    if (go) go.disabled = false;
}
async function startNav(){
    const picked = document.querySelector('.nav-reason.picked');
    const reason = picked ? picked.textContent.trim() : '';
    try {
        await fetch('api/nav_log.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({reason}),keepalive:true});
    } catch {}
    closeModal('navModal');
    loadNavLog();
    window.open(HOTEL_URL, '_blank', 'noopener');
}
async function loadNavLog() {
    const el = document.getElementById('navlogList');
    try {
        const res = await fetch('api/nav_log.php');
        const data = await res.json();
        const log = data.log || [];
        const title = document.getElementById('navlogTitle');
        const head  = document.getElementById('navEmptyHead');
        if (!log.length) {
            if (title) title.style.display = 'none';
            if (head)  head.style.display = '';
            el.innerHTML = '';
            return;
        }
        if (title) title.style.display = '';
        if (head)  head.style.display = 'none';
        el.innerHTML = log.map(x => {
            const d = new Date((x.at || '').replace(' ', 'T'));
            const when = isNaN(d) ? esc(x.at) : d.toLocaleDateString('he-IL',{day:'2-digit',month:'2-digit'}) + ' · ' + d.toLocaleTimeString('he-IL',{hour:'2-digit',minute:'2-digit'});
            const tag = x.reason ? `<span class="navlog-tag">${esc(x.reason)}</span>` : '';
            const avatar = x.photo
                ? `<span class="navlog-avatar"><img src="${esc(x.photo)}" alt="${esc(x.name)}"></span>`
                : `<span class="navlog-avatar">${esc((x.name || '?').charAt(0))}</span>`;
            return `<div class="navlog-row">
                ${avatar}
                <span class="navlog-name">${esc(x.name)}</span>
                <span class="navlog-mid">${tag}</span>
                <span class="navlog-when">${when}</span>
            </div>`;
        }).join('');
    } catch { el.innerHTML = '<div class="empty-state">שגיאה בטעינה</div>'; }
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});
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
if (document.getElementById('navlogList')) loadNavLog();
</script>
<script src="assets/profile.js?v=<?= filemtime(__DIR__ . '/assets/profile.js') ?>"></script>
</body>
</html>
