<?php if (function_exists('demo_mode') && demo_mode()): ?>
<?php $__ph = $_SESSION['demo_phase'] ?? 'before'; ?>
<style>
.demo-phase { position: fixed; top: calc(env(safe-area-inset-top) + 70px); left: 50%; transform: translateX(-50%);
    z-index: 45; display: inline-flex; align-items: center; gap: 6px; padding: 5px;
    background: rgba(10,10,16,0.92); backdrop-filter: blur(12px); border: 1px solid var(--border-gold);
    border-radius: 100px; box-shadow: 0 8px 26px rgba(0,0,0,0.5); }
.demo-phase .dp-tag { font-size: .66rem; color: var(--gold-light); font-weight: 600; padding: 0 8px; white-space: nowrap; cursor: grab; user-select: none; display: inline-flex; align-items: center; gap: 4px; touch-action: none; }
.demo-phase .dp-tag::before { content: '⠿'; font-size: .9rem; opacity: .7; }
.demo-phase.dragging { cursor: grabbing; }
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

    // מתג ההדגמה - נגרר לכל פינה (הידית = התווית "הדגמה"), המיקום נשמר
    (function () {
        var el = document.querySelector('.demo-phase'); if (!el) return;
        var grip = el.querySelector('.dp-tag'); if (!grip) return;
        try { var p = JSON.parse(localStorage.getItem('cw-demo-pos') || 'null');
            if (p && p.left) { el.style.left = p.left; el.style.top = p.top; el.style.right = 'auto'; el.style.bottom = 'auto'; el.style.transform = 'none'; }
        } catch (e) {}
        var on = false, sx, sy, ox, oy;
        grip.addEventListener('pointerdown', function (e) {
            on = true; el.classList.add('dragging');
            var r = el.getBoundingClientRect(); ox = r.left; oy = r.top; sx = e.clientX; sy = e.clientY;
            el.style.left = ox + 'px'; el.style.top = oy + 'px'; el.style.right = 'auto'; el.style.bottom = 'auto'; el.style.transform = 'none';
            try { grip.setPointerCapture(e.pointerId); } catch (er) {} e.preventDefault();
        });
        grip.addEventListener('pointermove', function (e) {
            if (!on) return;
            var nx = ox + (e.clientX - sx), ny = oy + (e.clientY - sy);
            nx = Math.max(6, Math.min(window.innerWidth - el.offsetWidth - 6, nx));
            ny = Math.max(6, Math.min(window.innerHeight - el.offsetHeight - 6, ny));
            el.style.left = nx + 'px'; el.style.top = ny + 'px';
        });
        function end() { if (!on) return; on = false; el.classList.remove('dragging');
            try { localStorage.setItem('cw-demo-pos', JSON.stringify({ left: el.style.left, top: el.style.top })); } catch (e) {} }
        grip.addEventListener('pointerup', end); grip.addEventListener('pointercancel', end);
    })();

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
