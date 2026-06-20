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
$isGroom = !empty($me['is_groom']);

// current trip day (1.7-5.7.2026), clamped 1..5
$tripStart = mktime(0, 0, 0, 7, 1, 2026);
$curDay = (int)floor((time() - $tripStart) / 86400) + 1;
if ($curDay < 1) $curDay = 1;
if ($curDay > 5) $curDay = 5;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#06060a">
    <title>ברכות לנועם - <?= htmlspecialchars(APP_NAME) ?></title>
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
<?php $lockMsg = 'מומלץ להירגע באווירת חופש. חכה קצת'; include __DIR__ . '/locked_page.php'; ?>
<?php else: ?>
<main class="app-wrap bless-page">
    <div class="bless-wall" id="blessWall"><div class="spinner"></div></div>
</main>

<!-- שכבת טשטוש: הפתקים נמוגים מתחת לכפתור -->
<div class="bless-fade"></div>
<button class="btn-upload bless-cta-fixed <?= $isGroom ? 'bless-cta-groom' : '' ?>" onclick="openBless()"><?= icon('star', 'i') ?> <?= $isGroom ? 'מסר לצוות' : 'כתוב ברכה לנועם' ?></button>

<!-- Modal: write blessing -->
<div class="modal-overlay" id="blessModal" onclick="closeModal('blessModal')">
    <button class="modal-close" onclick="closeModal('blessModal')" aria-label="סגור">&times;</button>
    <div class="modal-sheet" role="dialog" aria-modal="true" tabindex="-1" onclick="event.stopPropagation()">
        <div class="modal-handle"></div>
        <div class="modal-title"><?= $isGroom ? 'מסר לצוות' : 'ברכה לנועם' ?></div>
        <div class="bless-crown" id="blessCrown">
            <div class="uc-stage uc-mini">
                <svg class="uc-crown uc-base" viewBox="0 0 100 100" aria-hidden="true"><path fill="rgba(255,255,255,0.10)" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="rgba(255,255,255,0.10)"/></svg>
                <div class="uc-fill-wrap uc-loop">
                    <div class="uc-water">
                        <svg class="uc-wave" viewBox="0 0 120 10" preserveAspectRatio="none" aria-hidden="true"><path fill="#E9C355" d="M0 10 V5 Q7.5 0 15 5 T30 5 T45 5 T60 5 T75 5 T90 5 T105 5 T120 5 V10 Z"/></svg>
                        <div class="uc-water-body"></div>
                    </div>
                </div>
            </div>
        </div>
        <textarea class="bless-textarea" id="blessText" aria-label="טקסט הברכה" placeholder="<?= $isGroom ? 'דבר אלינו, חתן. מה בלב...' : 'יאללה שפוך ' . htmlspecialchars($myFirst) . '...' ?>" oninput="document.getElementById('blessSubmitBtn').disabled = !this.value.trim()"></textarea>
        <button class="btn-primary" id="blessSubmitBtn" disabled onclick="submitBless()">תלה על הקיר</button>
    </div>
</div>
<?php endif; ?>

<!-- Bottom Navbar -->
<?php $NAV_ACTIVE = 'bless'; include __DIR__ . '/navbar.php'; ?>

<script>
const CUR_DAY = <?= $curDay ?>;
const ME = <?= json_encode($user_id) ?>;
const NOTE_TONES = ['t1','t2','t3','t4'];
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function openModal(id){document.getElementById(id).classList.add('open');}
function openBless(){
    openModal('blessModal');
    // פוקוס מיידי בתוך מחוות המשתמש - פותח מקלדת והשדה מסומן
    const ta = document.getElementById('blessText');
    ta.focus();
    setTimeout(() => ta.focus(), 350);
}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.open').forEach(m=>m.classList.remove('open'));});
async function submitBless(){
    const content = document.getElementById('blessText').value.trim();
    if(!content) return;
    const btn = document.getElementById('blessSubmitBtn');
    const crown = document.getElementById('blessCrown');
    btn.disabled = true; btn.textContent = 'תולה...';
    crown.classList.add('active');
    try {
        const res = await fetch('api/blessing.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ day: CUR_DAY, content })
        });
        const data = await res.json();
        if(data.error){ alert(data.error); }
        else {
            document.getElementById('blessText').value='';
            await loadWall();
            setTimeout(() => closeModal('blessModal'), 350);
        }
    } catch { alert('שגיאה בשליחה'); }
    crown.classList.remove('active');
    btn.disabled=false; btn.textContent='תלה על הקיר';
}
async function loadWall(){
    const wall = document.getElementById('blessWall');
    try {
        const res = await fetch('api/blessing.php?all=1');
        const data = await res.json();
        const b = data.blessings || [];
        if (!b.length){ wall.innerHTML = '<div class="bless-empty">יאללה, מי הראשון?</div>'; return; }
        const DAY_NAMES = {1:'יום ראשון',2:'יום שני',3:'יום שלישי',4:'יום רביעי',5:'יום חמישי'};
        wall.innerHTML = b.map((x,i)=>{
            const tone = NOTE_TONES[i % NOTE_TONES.length];
            const rot = ((i*37)%5) - 2; // -2..2 deg pseudo-random
            const d = new Date((x.at || '').replace(' ', 'T'));
            const when = isNaN(d) ? '' : d.toLocaleDateString('he-IL',{day:'2-digit',month:'2-digit'});
            const del = x.user_id === ME ? `<button class="note-del" onclick="deleteBless(${x.id})" aria-label="מחק">×</button>` : '';
            return `<div class="bless-note ${tone}" style="transform:rotate(${rot}deg)">
                ${del}
                <div class="note-text">${esc(x.content)}</div>
                <div class="note-author">- ${esc(x.first)}</div>
                <div class="note-date">${DAY_NAMES[x.day] || ''}${when ? ' · ' + when : ''}</div>
            </div>`;
        }).join('');
    } catch { wall.innerHTML = '<div class="empty-state">שגיאה בטעינה</div>'; }
}
async function deleteBless(id){
    if(!confirm('למחוק את הברכה? אין עריכה - רק כתיבה מחדש.')) return;
    try {
        const res = await fetch('api/blessing.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', id })
        });
        const data = await res.json();
        if(data.error){ alert(data.error); return; }
        loadWall();
    } catch { alert('שגיאה במחיקה'); }
}
if (document.getElementById('blessWall')) loadWall();
</script>
<script src="assets/profile.js?v=<?= filemtime(__DIR__ . '/assets/profile.js') ?>"></script>
</body>
</html>
