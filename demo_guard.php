<?php if (function_exists('demo_mode') && demo_mode()): ?>
<div id="demoModal" class="demo-modal" onclick="this.classList.remove('show')">
    <div class="demo-modal-card" onclick="event.stopPropagation()">
        <div class="dm-emoji">🙂</div>
        <p class="dm-title">אני רק דמו</p>
        <p class="dm-sub">אל תגזימו :) זו הדגמה - פעולות שמירה כבויות. שחקו, דפדפו, ותהנו מהמוצר!</p>
        <button class="dm-btn" type="button" onclick="document.getElementById('demoModal').classList.remove('show')">הבנתי</button>
    </div>
</div>
<script>
(function () {
    // פעולות "שקטות" (לא מקפיצות מודל) - משחקים/לוגים
    var SILENT = ['score.php', 'game_play.php', 'nav_log.php'];
    var _fetch = window.fetch;
    window.fetch = function (url, opts) {
        opts = opts || {};
        var m = (opts.method || 'GET').toUpperCase();
        var u = String(url);
        if (m === 'POST' && u.indexOf('api/') !== -1) {
            var silent = SILENT.some(function (s) { return u.indexOf(s) !== -1; });
            if (!silent) { var el = document.getElementById('demoModal'); if (el) el.classList.add('show'); }
            return Promise.resolve(new Response(JSON.stringify({ ok: silent }), { status: 200, headers: { 'Content-Type': 'application/json' } }));
        }
        return _fetch.apply(this, arguments);
    };
})();
</script>
<?php endif; ?>
