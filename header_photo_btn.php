<?php
// כפתור התמונה היומית בהאדר. לפני הטיול: עמום, "בקרוב", פותח מודל "סבלנות קצת".
$_ts=mktime(0,0,0,7,1,2026); $_td=(int)floor((time()-$_ts)/86400)+1; if($_td<0)$_td=0;
if (is_dev_env()) $_td = isset($_GET['simday']) ? max(0,min(5,(int)$_GET['simday'])) : 3;
elseif (($_dsd = demo_sim_day()) !== null) $_td = $_dsd;
$hp_locked = collection_mode($_td);
if ($hp_locked): ?>
<button class="hdr-photo hdr-photo-soon" onclick="openModal('soonModal')" aria-label="בקרוב">
    <?= icon('image', 'i') ?>
    <span class="hdr-soon-label">בקרוב</span>
</button>
<?php elseif (basename($_SERVER['SCRIPT_NAME']) === 'app.php'): ?>
<button class="hdr-photo" onclick="triggerDailyUpload()" aria-label="העלאת תמונה"><?= icon('image', 'i') ?><span class="hdr-photo-plus" id="hdrQuota">+</span></button>
<?php else: ?>
<a class="hdr-photo" href="app.php" aria-label="התמונה היומית"><?= icon('image', 'i') ?><span class="hdr-photo-plus">+</span></a>
<?php endif; ?>
