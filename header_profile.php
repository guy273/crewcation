<?php /* expects $myFirst, $myPhoto, icon() available */ ?>
<div class="hdr-profile">
    <button class="profile-trigger" id="profileTrigger">
        <span class="profile-avatar">
            <?php if (!empty($myPhoto)): ?>
                <img src="<?= htmlspecialchars($myPhoto) ?>" alt="">
            <?php else: ?>
                <?= htmlspecialchars(mb_substr($myFirst, 0, 1)) ?>
            <?php endif; ?>
        </span>
        <span class="profile-greet">אהלן, <?= htmlspecialchars($myFirst) ?></span>
        <svg class="profile-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="profile-menu" id="profileMenu">
        <button class="pm-item" id="pmChangePhoto"><?= icon('camera', 'pm-ico') ?> החלף תמונה</button>
        <a class="pm-item pm-logout" href="logout.php"><?= icon('navigation', 'pm-ico') ?> התנתק</a>
    </div>
    <input type="file" id="pmPhotoInput" accept="image/*" hidden>
</div>
