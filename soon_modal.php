<?php
$_ts=mktime(0,0,0,7,1,2026); $_td=(int)floor((time()-$_ts)/86400)+1; if($_td<0)$_td=0;
if (is_dev_env()) $_td = isset($_GET['simday']) ? max(0,min(5,(int)$_GET['simday'])) : 3;
if (collection_mode($_td)): ?>
<div class="modal-overlay" id="soonModal" onclick="closeModal('soonModal')">
    <button class="modal-close" onclick="closeModal('soonModal')" aria-label="סגור">&times;</button>
    <div class="modal-sheet soon-sheet" role="dialog" aria-modal="true" aria-label="סבלנות" tabindex="-1" onclick="event.stopPropagation()">
        <div class="modal-handle"></div>
        <div class="soon-big">סבלנות קצת</div>
        <p class="soon-sub">הכל ייפתח ביום הטיסה.</p>
    </div>
</div>
<?php endif; ?>
