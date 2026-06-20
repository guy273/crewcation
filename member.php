<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

$user_id = require_login();
if (app_locked()) { header('Location: members.php'); exit; }

$mid = $_GET['id'] ?? '';
if (!isset(USERS[$mid])) { header('Location: members.php'); exit; }
$m = USERS[$mid];
$first = explode(' ', $m['name'])[0];

function profile_photo(string $uid): ?string {
    foreach (['webp','png','jpg','jpeg'] as $ext) {
        if (file_exists(__DIR__ . "/assets/members/{$uid}.{$ext}")) return "assets/members/{$uid}.{$ext}";
    }
    return null;
}
$photo = profile_photo($mid);

// לעמוד החתן: שאר חברי הצוות שלרשותו
$crew = [];
if (!empty($m['is_groom'])) {
    foreach (USERS as $uid => $u) {
        if ($uid === $mid) continue;
        $crew[] = ['name' => explode(' ', $u['name'])[0], 'photo' => profile_photo($uid)];
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<script>try{var t=localStorage.getItem("cw-theme");if(t&&t!=="gold")document.documentElement.dataset.theme=t;}catch(e){}</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#06060a">
    <title><?= htmlspecialchars($m['name']) ?> - <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/crown.svg">
    <link rel="icon" type="image/png" href="assets/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body class="game-page">
<div class="app-bg-depth" aria-hidden="true"></div><div class="app-bg-sheen" aria-hidden="true"></div>

<header class="app-header game-header">
    <a class="game-back" href="members.php" aria-label="חזרה">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    <div class="logo-area"><h1>כרטיס שחקן</h1></div>
    <span class="game-back-spacer"></span>
</header>

<main class="app-wrap mp-main">

    <div class="mp-photo <?= !empty($m['is_groom']) ? 'mp-groom' : '' ?>">
        <?php if ($photo): ?>
            <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($m['name']) ?>">
        <?php else: ?>
            <span class="mp-initial"><?= mb_substr($m['name'], 0, 1) ?></span>
        <?php endif; ?>
        <?php if (!empty($m['is_groom'])): ?>
        <span class="mp-crown"><?= icon('crown') ?></span>
        <?php endif; ?>
    </div>

    <h2 class="mp-name"><?= htmlspecialchars($m['name']) ?></h2>
    <p class="mp-phrase"><?= htmlspecialchars($m['phrase']) ?></p>

    <?php if (empty($m['is_groom']) && !empty($m['since'])): ?>
        <p class="mp-since">חברים מאז <?= (int)$m['since'] ?></p>
    <?php endif; ?>

    <?php if (!empty($m['is_groom'])): ?>
    <div class="mp-section">
        <h3 class="mp-sec-title">לרשותו לימים הקרובים</h3>
        <div class="mp-crew">
            <?php foreach ($crew as $c): ?>
            <div class="mp-crew-item">
                <div class="mp-crew-avatar">
                    <?php if ($c['photo']): ?>
                        <img src="<?= htmlspecialchars($c['photo']) ?>" alt="<?= htmlspecialchars($c['name']) ?>">
                    <?php else: ?>
                        <?= mb_substr($c['name'], 0, 1) ?>
                    <?php endif; ?>
                </div>
                <span class="mp-crew-name"><?= htmlspecialchars($c['name']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif (empty($m['is_groom'])): ?>
    <div class="mp-tags-block" data-mid="<?= htmlspecialchars($mid) ?>" data-own="<?= $mid === $user_id ? '1' : '0' ?>">
        <div class="mp-tags" id="mpTags"></div>
        <?php if ($mid === $user_id): ?>
        <div class="mp-tag-add-row" id="tagAddRow" style="display:none">
            <div class="suggest-field">
                <input type="text" id="newTag" placeholder="תגית חדשה..." maxlength="30" oninput="tagAddToggle()" onkeydown="if(event.key==='Enter'){event.preventDefault();addTag();}">
                <button class="sug-send" id="newTagBtn" onclick="addTag()" aria-label="הוסף"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
            </div>
        </div>
        <button class="mp-tags-edit" id="tagsEdit" onclick="toggleTagEdit()" aria-label="עריכת תגיות">
            <svg class="mte-pencil" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
            <span class="mte-label">עריכה</span>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($m['bio'])): ?>
    <p class="mp-bio"><?= htmlspecialchars($m['bio']) ?></p>
    <?php endif; ?>

    <a class="mp-wa"
       href="https://wa.me/972<?= ltrim(htmlspecialchars($m['tel']), '0') ?>"
       target="_blank" rel="noopener">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.956 9.956 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" fill="#25D366"/><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.47-.148-.669.149-.198.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.148-.173.198-.297.297-.495.1-.198.05-.372-.025-.521-.074-.149-.668-1.612-.916-2.207-.241-.579-.486-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="#fff"/></svg>
        שלח הודעה ל<?= htmlspecialchars($first) ?>
    </a>

</main>
<script>
(function () {
    const block = document.querySelector('.mp-tags-block');
    if (!block) return;
    const mid = block.dataset.mid;
    const canEdit = block.dataset.own === '1';
    let tags = [], editing = false;
    function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}

    const TRASH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
    function render() {
        const c = document.getElementById('mpTags');
        if (editing && !tags.length) { c.innerHTML = '<span class="mp-tags-empty">אין תגיות עדיין</span>'; return; }
        c.innerHTML = tags.map(t => {
            const del = editing ? `<button class="mp-tag-del" data-tag="${esc(t)}" aria-label="מחק">${TRASH}</button>` : '';
            return `<span class="mp-tag ${editing ? 'jiggle' : ''}" data-tag="${esc(t)}">${esc(t)}${del}</span>`;
        }).join('');
        if (editing) c.querySelectorAll('.mp-tag-del').forEach(b => {
            b.onclick = () => window._delTag(b.dataset.tag, b.closest('.mp-tag'));
        });
    }
    async function load() {
        try {
            const res = await fetch('api/tags.php?id=' + encodeURIComponent(mid));
            const data = await res.json();
            tags = data.tags || [];
            render();
        } catch {}
    }
    window.toggleTagEdit = function () {
        editing = !editing;
        const btn = document.getElementById('tagsEdit');
        const row = document.getElementById('tagAddRow');
        if (btn) btn.classList.toggle('on', editing);
        if (btn) btn.querySelector('.mte-label').textContent = editing ? 'סיום' : 'עריכה';
        const pencil = btn ? btn.querySelector('.mte-pencil') : null;
        if (pencil) pencil.style.display = editing ? 'none' : '';
        if (row) row.style.display = editing ? '' : 'none';
        render();
    };
    window.tagAddToggle = function () {
        const inp = document.getElementById('newTag');
        const b = document.getElementById('newTagBtn');
        if (b) b.classList.toggle('visible', inp.value.trim().length > 0);
    };
    window.addTag = async function () {
        const inp = document.getElementById('newTag');
        const tag = inp.value.trim();
        if (!tag) return;
        inp.value = ''; window.tagAddToggle();
        try {
            const res = await fetch('api/tags.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'add', tag }) });
            const d = await res.json();
            if (d.error) { alert(d.error); return; }
            await load();
        } catch { alert('שגיאה'); }
    };
    window._delTag = async function (tag, el) {
        if (el) { el.classList.remove('jiggle'); el.classList.add('poof'); }   // היעלמות כמו ענן אבק
        fetch('api/tags.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete', tag }) }).catch(() => {});
        setTimeout(() => load(), 360);
    };
    load();
})();
</script>
<script src="assets/profile.js?v=<?= filemtime(__DIR__ . '/assets/profile.js') ?>"></script>
<?php include __DIR__ . '/demo_guard.php'; ?>
</body>
</html>
