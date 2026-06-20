<?php
// מסך נעול לעמוד שלם. הדף המארח מגדיר $lockMsg לפני include.
$lockMsg = $lockMsg ?? 'יפתח ביום הטיסה';
?>
<main class="app-wrap">
    <div class="page-lock">
        <span class="page-lock-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10.5" width="14" height="9.5" rx="3"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/><circle cx="12" cy="15" r="1.1" fill="currentColor" stroke="none"/></svg>
        </span>
        <div class="page-lock-msg"><?= htmlspecialchars($lockMsg) ?></div>
        <div class="page-lock-sub">הכל ייפתח ביום הטיסה</div>
    </div>
</main>
