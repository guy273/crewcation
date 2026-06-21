<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

$user_id = require_login();
$locked  = app_locked();
$me      = get_user($user_id);
$myFirst = explode(' ', $me['name'] ?? '')[0];
$myPhoto = member_photo($user_id);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<script>try{var t=localStorage.getItem("cw-theme");if(t&&t!=="gold")document.documentElement.dataset.theme=t;}catch(e){}</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <title>משתתפים - <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/crown.svg">
    <link rel="icon" type="image/png" href="assets/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body>
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
<?php include __DIR__ . '/soon_modal.php'; ?>

<!-- Members -->
<?php if ($locked): ?>
<div class="members-header">
    <h1>משתתפים</h1>
    <p>צרו קשר במקרה שהלכתם לאיבוד</p>
</div>
<?php else: ?>
<div class="meal-tabs team-tabs">
    <button class="meal-tab active" data-tab="members" onclick="teamTab('members')"><?= icon('team', 'mt-icon') ?> <?= htmlspecialchars(APP_NAME) ?></button>
    <button class="meal-tab" data-tab="board" onclick="teamTab('board')"><?= icon('trophy', 'mt-icon') ?> טבלת התנהגות</button>
</div>
<p class="team-sub" id="teamSub">כל חברי הצוות. צרו קשר במקרה שהלכתם לאיבוד.</p>
<?php endif; ?>

<div class="member-list">
<?php foreach (USERS as $uid => $u): ?>
    <div class="member-card reveal <?= $u['is_groom'] ? 'groom' : '' ?><?= !$locked ? ' member-clickable' : '' ?>"<?= !$locked ? ' onclick="location.href=\'member.php?id=' . htmlspecialchars($uid) . '\'"' : '' ?>>
        <div class="member-avatar">
            <?php
            $exts = ['webp','png','jpg','jpeg'];
            $photo = null;
            foreach ($exts as $ext) {
                if (file_exists(__DIR__ . "/assets/members/{$uid}.{$ext}")) {
                    $photo = "assets/members/{$uid}.{$ext}";
                    break;
                }
            }
            if ($photo): ?>
                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($u['name']) ?>">
            <?php else: ?>
                <?= strtoupper(mb_substr($u['name'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="member-info">
            <div class="member-name">
                <?= htmlspecialchars($u['name']) ?>
                <?php if ($u['is_groom']): ?>
                    <span class="member-crown"><?= icon('crown') ?></span>
                <?php endif; ?>
            </div>
            <div class="member-phrase"><?= htmlspecialchars($u['phrase']) ?></div>
            <?php if (!empty($u['tips'])): ?>
            <div class="member-tip"><?= icon('bulb', 'tip-icon') ?> <?= htmlspecialchars($u['tips'][array_rand($u['tips'])]) ?></div>
            <?php endif; ?>
        </div>
        <?php if (!$locked): ?>
        <a class="member-wa member-enter"
           href="member.php?id=<?= htmlspecialchars($uid) ?>"
           aria-label="לעמוד של <?= htmlspecialchars($u['name']) ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <?php else: ?>
        <a class="member-wa"
           href="https://wa.me/972<?= ltrim(htmlspecialchars($u['tel']), '0') ?>"
           target="_blank"
           rel="noopener"
           aria-label="WhatsApp - <?= htmlspecialchars($u['name']) ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.956 9.956 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" fill="#25D366"/><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.47-.148-.669.149-.198.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.148-.173.198-.297.297-.495.1-.198.05-.372-.025-.521-.074-.149-.668-1.612-.916-2.207-.241-.579-.486-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="#fff"/></svg>
        </a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<?php if (!$locked): ?>
<!-- Behavior leaderboard tab -->
<div class="team-board" id="teamBoard" style="display:none">
    <div class="lb-table" id="teamBoardTable"><div class="spinner"></div></div>
</div>
<?php endif; ?>

<!-- Bottom Navbar -->
<?php $NAV_ACTIVE = 'team'; include __DIR__ . '/navbar.php'; ?>

<script>
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* team tabs: members | behavior board */
let boardLoaded = false;
function teamTab(tab){
    document.querySelectorAll('.team-tabs .meal-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    const list = document.querySelector('.member-list');
    const board = document.getElementById('teamBoard');
    list.style.display = tab === 'members' ? '' : 'none';
    if (board) board.style.display = tab === 'board' ? '' : 'none';
    // מעבר חלק: גלילה לראש + fade-in של התוכן שנכנס (בלי קפיצה)
    try { window.scrollTo({ top: 0, behavior: 'auto' }); } catch(e) { window.scrollTo(0,0); }
    const shown = tab === 'members' ? list : board;
    if (shown) { shown.style.transition = 'none'; shown.style.opacity = '0';
        requestAnimationFrame(() => { shown.style.transition = 'opacity .28s ease'; shown.style.opacity = '1'; }); }
    const sub = document.getElementById('teamSub');
    if (sub) sub.textContent = tab === 'members'
        ? 'כל חברי הצוות. צרו קשר במקרה שהלכתם לאיבוד.'
        : 'נקודות מצטברות מהדירוגים היומיים. בלי בושה.';
    if (tab === 'board' && !boardLoaded) { boardLoaded = true; loadBoard(); }
}
/* הגעה ישירה לטאב הטבלה: members.php?tab=board */
if (new URLSearchParams(location.search).get('tab') === 'board' && document.querySelector('.team-tabs')) {
    teamTab('board');
}
async function loadBoard(){
    const el = document.getElementById('teamBoardTable');
    try {
        const res = await fetch('api/rate.php?board=1');
        const data = await res.json();
        const lb = data.leaderboard || [];
        el.innerHTML = lb.map((p, i) => {
            const ranked = p.points > 0;
            const rankClass = ranked ? `lb-rank-${i + 1 <= 3 ? i + 1 : 'n'}` : '';
            return `<div class="lb-row ${i === 0 && ranked ? 'lb-top' : ''}">
                <span class="lb-rank ${rankClass}">${ranked ? i + 1 : '–'}</span>
                <span class="lb-name">${esc(p.name)}${p.is_groom ? ' <span class="crown-inline"><?= str_replace(["\n","'"], ["","\\'"], icon('crown')) ?></span>' : ''}</span>
                <span class="lb-score">${ranked ? p.points + ' נק׳' : '—'}</span>
            </div>`;
        }).join('');
    } catch { el.innerHTML = '<div class="empty-state">שגיאה בטעינה</div>'; }
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});
/* scroll reveal */
(function(){
    const els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(e=>e.classList.add('in')); return; }
    const io = new IntersectionObserver((ents)=>{ents.forEach(en=>{if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target);}});},{threshold:0.12, rootMargin:'0px 0px -8% 0px'});
    els.forEach(e=>io.observe(e));
})();
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
</script>
<script src="assets/profile.js?v=<?= filemtime(__DIR__ . '/assets/profile.js') ?>"></script>

<?php include __DIR__ . '/demo_guard.php'; ?>
</body>
</html>
