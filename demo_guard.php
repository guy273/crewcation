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
/* כפתור סגירה (×) לפופאפ הדמו */
.demo-modal-card { position: relative; }
.dm-x { position: absolute; top: 12px; inset-inline-start: 14px; background: none; border: 0;
    color: rgba(255,255,255,0.45); font-size: 1.5rem; line-height: 1; cursor: pointer; padding: 0; }
.dm-x:hover { color: rgba(255,255,255,0.8); }
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
<div id="a2hsTip" class="demo-modal">
    <div class="demo-modal-card" onclick="event.stopPropagation()">
        <button class="dm-x" type="button" id="a2hsX" aria-label="סגירה">&times;</button>
        <div class="dm-emoji">📲</div>
        <p class="dm-title">הוסיפו למסך הבית</p>
        <p class="dm-sub">לחוויה חלקה יותר - מסך מלא ופתיחה בלחיצה: כפתור השיתוף בדפדפן, ואז "הוסף למסך הבית".</p>
        <button class="dm-btn" type="button" id="a2hsOk">הבנתי</button>
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
        function start(cx, cy) {
            on = true; el.classList.add('dragging');
            var r = el.getBoundingClientRect(); ox = r.left; oy = r.top; sx = cx; sy = cy;
            el.style.left = ox + 'px'; el.style.top = oy + 'px'; el.style.right = 'auto'; el.style.bottom = 'auto'; el.style.transform = 'none';
        }
        function move(cx, cy) {
            if (!on) return;
            var nx = ox + (cx - sx), ny = oy + (cy - sy);
            nx = Math.max(6, Math.min(window.innerWidth - el.offsetWidth - 6, nx));
            ny = Math.max(6, Math.min(window.innerHeight - el.offsetHeight - 6, ny));
            el.style.left = nx + 'px'; el.style.top = ny + 'px';
        }
        function end() { if (!on) return; on = false; el.classList.remove('dragging');
            try { localStorage.setItem('cw-demo-pos', JSON.stringify({ left: el.style.left, top: el.style.top })); } catch (e) {} }
        // עכבר
        grip.addEventListener('mousedown', function (e) { start(e.clientX, e.clientY); e.preventDefault(); });
        document.addEventListener('mousemove', function (e) { move(e.clientX, e.clientY); });
        document.addEventListener('mouseup', end);
        // מגע
        grip.addEventListener('touchstart', function (e) { var t = e.touches[0]; start(t.clientX, t.clientY); e.preventDefault(); }, { passive: false });
        document.addEventListener('touchmove', function (e) { if (on) { var t = e.touches[0]; move(t.clientX, t.clientY); e.preventDefault(); } }, { passive: false });
        document.addEventListener('touchend', end); document.addEventListener('touchcancel', end);
    })();

    // פופאפ "הוסף למסך הבית" - מובייל, פעם אחת בלבד, ולא כשכבר רץ כאפליקציה (standalone)
    (function () {
        var pop = document.getElementById('a2hsTip'); if (!pop) return;
        var mobile = window.matchMedia('(max-width: 768px)').matches || (('ontouchstart' in window) && window.matchMedia('(pointer: coarse)').matches);
        var standalone = window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;
        var seen; try { seen = localStorage.getItem('cw-a2hs'); } catch (e) {}
        function dismiss() { pop.classList.remove('show'); }
        if (mobile && !standalone && !seen) {
            try { localStorage.setItem('cw-a2hs', '1'); } catch (e) {}   // מסומן מיד - לא יקפוץ שוב לעולם
            setTimeout(function () { pop.classList.add('show'); }, 900);
        }
        var ok = document.getElementById('a2hsOk'), x = document.getElementById('a2hsX');
        if (ok) ok.addEventListener('click', dismiss);
        if (x) x.addEventListener('click', dismiss);
        pop.addEventListener('click', function (e) { if (e.target === pop) dismiss(); });
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
