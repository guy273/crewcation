<?php if (function_exists('demo_mode') && demo_mode()): ?>
<?php $__ph = $_SESSION['demo_phase'] ?? 'before'; ?>
<style>
.demo-phase { position: fixed; top: calc(env(safe-area-inset-top) + 70px); left: 50%; transform: translateX(-50%);
    z-index: 45; display: inline-flex; align-items: center; gap: 6px; padding: 5px;
    background: rgba(10,10,16,0.92); backdrop-filter: blur(12px); border: 1px solid var(--border-gold);
    border-radius: 100px; box-shadow: 0 8px 26px rgba(0,0,0,0.5); }
.demo-phase .dp-tag { font-size: .66rem; color: var(--gold-light); font-weight: 600; padding: 0 6px; white-space: nowrap; }
.demo-phase .dp-btn { font-size: .8rem; font-weight: 600; text-decoration: none; color: var(--text-muted);
    padding: 7px 15px; border-radius: 100px; white-space: nowrap; transition: all .2s; }
.demo-phase .dp-btn.active { background: var(--grad-gold); color: #1a1505; }
</style>
<div class="demo-phase" aria-label="מצב הדגמה">
    <span class="dp-tag">הדגמה</span>
    <a class="dp-btn <?= $__ph !== 'during' ? 'active' : '' ?>" href="/demo?phase=before">לפני הטיסה</a>
    <a class="dp-btn <?= $__ph === 'during' ? 'active' : '' ?>" href="/demo?phase=during">יום הטיסה</a>
</div>
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

    // בדמו: כל לינק חיצוני נפתח בטאב חדש (שלא ינווטו החוצה מהדמו)
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a) return;
        var href = a.getAttribute('href') || '';
        if (/^https?:\/\//i.test(href) && a.host && a.host !== location.host && a.target !== '_blank') {
            e.preventDefault();
            window.open(href, '_blank', 'noopener');
        }
    }, true);
})();
</script>
<?php endif; ?>
