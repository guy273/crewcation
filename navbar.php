<?php
// נאבבר תחתון אחיד. הדף המארח מגדיר $NAV_ACTIVE: home | bless | team | nav
// במצב נעול: שני טאבים בלבד, נאבבר קומפקטי ממורכז.
$NAV_ACTIVE = $NAV_ACTIVE ?? '';
$act = fn(string $k): string => $NAV_ACTIVE === $k ? ' active' : '';
$cur = fn(string $k): string => $NAV_ACTIVE === $k ? ' aria-current="page"' : '';
?>
<?php
// Speculation Rules: הדפדפן מרנדר מראש את דפי הנאבבר, אז המעבר ביניהם מיידי
// (בלי זה ה-View Transition מחכה לטעינת הדף החדש ומרגיש תקוע)
$specUrls = ['app.php', 'blessings.php', 'members.php', 'navigate.php'];
?>
<script type="speculationrules">
{ "prerender": [{ "urls": <?= json_encode($specUrls) ?>, "eagerness": "moderate" }] }
</script>

<nav class="bottom-nav" aria-label="ניווט ראשי">
    <?php if (basename($_SERVER['SCRIPT_NAME']) === 'app.php' && !$locked): ?>
    <button class="nav-btn<?= $act('home') ?>"<?= $cur('home') ?> id="nav-days" onclick="showDays()"><?= icon('home', 'nav-icon') ?><span>בית</span></button>
    <?php else: ?>
    <a class="nav-btn<?= $act('home') ?>"<?= $cur('home') ?> href="app.php"><?= icon('home', 'nav-icon') ?><span>בית</span></a>
    <?php endif; ?>
    <a class="nav-btn<?= $act('bless') ?>"<?= $cur('bless') ?> href="blessings.php"><?= icon('star', 'nav-icon') ?><span>ברכות</span></a>
    <a class="nav-btn<?= $act('team') ?>"<?= $cur('team') ?> href="members.php"><?= icon('team', 'nav-icon') ?><span>צוות</span></a>
    <a class="nav-btn<?= $act('nav') ?>"<?= $cur('nav') ?> href="navigate.php"><?= icon('navigation', 'nav-icon nav-icon-go') ?><span>ניווט למלון</span></a>
</nav>

<?php if ($locked): ?>
<script>
/* רגע השחרור: כשהשעון מגיע ל-UNLOCK_TS הדף מתרענן לבד ונכנסים למוצר המלא */
(function unlockReload(){
    const msLeft = <?= (UNLOCK_TS - time()) ?> * 1000;
    if (msLeft <= 0 || msLeft > 36 * 3600 * 1000) return;
    setTimeout(() => {
        const o = document.createElement('div');
        o.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(6,6,10,0.92);backdrop-filter:blur(8px);color:#E9C355;font-size:1.3rem;font-weight:600';
        o.textContent = '<?= htmlspecialchars(APP_NAME) ?> נפתח. רגע...';
        document.body.appendChild(o);
        setTimeout(() => location.reload(), 1200 + Math.random() * 2000);
    }, msLeft + 1500);
})();
</script>
<?php endif; ?>
