/* Header profile menu + photo change (shared across pages) */
(function () {
    const trigger = document.getElementById('profileTrigger');
    const menu    = document.getElementById('profileMenu');
    if (!trigger || !menu) return;

    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('open');
        trigger.classList.toggle('open');
    });
    document.addEventListener('click', () => {
        menu.classList.remove('open');
        trigger.classList.remove('open');
    });
    menu.addEventListener('click', (e) => e.stopPropagation());

    // change photo
    const changeBtn = document.getElementById('pmChangePhoto');
    const input     = document.getElementById('pmPhotoInput');
    if (changeBtn && input) {
        changeBtn.addEventListener('click', () => input.click());
        input.addEventListener('change', async () => {
            if (!input.files || !input.files[0]) return;
            changeBtn.textContent = 'מעלה...';
            const fd = new FormData();
            fd.append('photo', input.files[0]);
            try {
                const res = await fetch('api/profile_photo.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.error) { alert(data.error); changeBtn.textContent = 'החלף תמונה'; return; }
                location.reload();
            } catch {
                alert('שגיאה בהעלאת התמונה');
                changeBtn.textContent = 'החלף תמונה';
            }
        });
    }
})();

/* rotating drunk lines under "נווט למלון" - blur in/out */
(function () {
    const subs = document.querySelectorAll('.bng-sub');
    if (!subs.length) return;
    const lines = ['יא שיכור', 'אתה כבר איש מבוגר תתאפס', 'לא מתאים איך שאתה מתנהג', 'פעמיים מים'];
    let i = 0;
    setInterval(() => {
        i = (i + 1) % lines.length;
        subs.forEach(s => {
            s.classList.add('swap');
            setTimeout(() => { s.textContent = lines[i]; s.classList.remove('swap'); }, 350);
        });
    }, 3500);
})();

/* PillTabs: אינדיקטור זהב מחליק עם spring overshoot (left/width, לא transform) */
(function () {
    document.querySelectorAll('.meal-tabs').forEach(g => {
        if (g.dataset.pt) return;
        g.dataset.pt = '1';
        const ind = document.createElement('span');
        ind.className = 'pt-indicator';
        g.prepend(ind);
        let userInteracted = false;

        function place(skipAnim) {
            const act = g.querySelector('.meal-tab.active');
            if (!act) { ind.style.opacity = '0'; return; }
            ind.style.opacity = '1';
            ind.style.transition = (skipAnim || !userInteracted)
                ? 'none'
                : 'left 0.42s cubic-bezier(0.34, 1.42, 0.64, 1), width 0.42s cubic-bezier(0.34, 1.42, 0.64, 1)';
            ind.style.left = act.offsetLeft + 'px';
            ind.style.top = act.offsetTop + 'px';
            ind.style.width = act.offsetWidth + 'px';
            ind.style.height = act.offsetHeight + 'px';
        }

        place(true);
        new MutationObserver(() => place(false)).observe(g, { subtree: true, attributes: true, attributeFilter: ['class'] });
        if ('ResizeObserver' in window) new ResizeObserver(() => place(true)).observe(g);
        // capture: מסומן לפני שה-onclick מחליף active
        g.addEventListener('click', e => {
            if (!e.target.closest('.meal-tab')) return;
            userInteracted = true;
            g.animate(
                [{ transform: 'scale(1)' }, { transform: 'scale(0.98)' }, { transform: 'scale(1)' }],
                { duration: 200, easing: 'ease-out' }
            );
        }, true);
    });
})();


/* נאבבר: PillTabs המקורי - הבועה מחליקה עם spring מול העיניים, bump, crossfade, ואז מעבר.
   הדף הבא כבר prerendered אז המעבר עצמו מיידי. */
(function () {
    const nav = document.querySelector('.bottom-nav');
    if (!nav) return;
    const ind = document.createElement('span');
    ind.className = 'pt-indicator';
    nav.prepend(ind);

    function place(el, spring) {
        if (!el) { ind.style.opacity = '0'; return; }
        ind.style.opacity = '1';
        /* 0.28s ולא 0.42s: המעבר לדף הבא יורה ב-290ms, והקפיץ חייב לנחות לפני -
           אחרת הדף מתחלף באמצע ה-overshoot ורואים קפיצה אחורה */
        ind.style.transition = spring
            ? 'left 0.28s cubic-bezier(0.34, 1.42, 0.64, 1), width 0.28s cubic-bezier(0.34, 1.42, 0.64, 1)'
            : 'none';
        ind.style.left = el.offsetLeft + 'px';
        ind.style.top = el.offsetTop + 'px';
        ind.style.width = el.offsetWidth + 'px';
        ind.style.height = el.offsetHeight + 'px';
    }
    // בטעינה: נצמד בלי אנימציה
    place(nav.querySelector('.nav-btn.active'), false);
    if ('ResizeObserver' in window) new ResizeObserver(() => {
        if (!navigating) place(nav.querySelector('.nav-btn.active'), false);
    }).observe(nav);

    let navigating = false;
    function go(e) {
        const btn = e.target.closest('.nav-btn');
        if (!btn || navigating) return;
        // bump - בכל לחיצה
        nav.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(0.98)' }, { transform: 'scale(1)' }],
            { duration: 200, easing: 'ease-out' }
        );
        if (btn.classList.contains('active')) return;
        const href = btn.getAttribute('href');
        // crossfade צבעים + החלקת הבועה עם ה-spring
        nav.querySelectorAll('.nav-btn').forEach(b => b.classList.toggle('active', b === btn));
        place(btn, true);
        if (href) {
            e.preventDefault();
            navigating = true;
            // נותנים לבאונס לנחות ואז מחליפים דף - שכבר טעון מראש
            setTimeout(() => { location.href = href; }, 290);
        }
    }
    nav.addEventListener('pointerup', e => { if (e.pointerType !== 'mouse') go(e); });
    nav.addEventListener('click', e => {
        const btn = e.target.closest('.nav-btn');
        if (btn && btn.getAttribute('href')) e.preventDefault();
        if (e.pointerType === 'mouse' || !('ontouchstart' in window)) go(e);
    });
})();


/* החזרה עם האצבע מהקצה - כמו באפליקציה אמיתית */
(function () {
    let ex = null, ey = null;
    document.addEventListener('touchstart', e => {
        // לא בתוך משחק - שם הגרירה שייכת למשחק
        if (e.target.closest('.candy-board, .game-canvas, .mem-grid, .wheel-stage')) return;
        const x = e.touches[0].clientX;
        // קצה ימני או שמאלי של המסך
        if (x < 26 || x > window.innerWidth - 26) { ex = x; ey = e.touches[0].clientY; }
    }, { passive: true });
    document.addEventListener('touchend', e => {
        if (ex === null) return;
        const dx = e.changedTouches[0].clientX - ex;
        const dy = Math.abs(e.changedTouches[0].clientY - ey);
        const fromLeft = ex < 26;
        if (dy < 60 && ((fromLeft && dx > 70) || (!fromLeft && dx < -70))) history.back();
        ex = null;
    }, { passive: true });
})();


/* sessionGuard: סשן שפג באמצע - fetch ל-api מקבל redirect ללוגין. עוברים לשם נקי במקום שגיאות סתמיות */
(function sessionGuard(){
    const orig = window.fetch;
    window.fetch = async function(...args){
        const res = await orig.apply(this, args);
        try {
            const url = typeof args[0] === 'string' ? args[0] : (args[0] && args[0].url) || '';
            if (url.includes('api/') && (res.redirected && res.url.includes('login') ||
                (res.headers.get('content-type') || '').includes('text/html'))) {
                location.href = 'login.php';
                return new Promise(() => {});
            }
        } catch {}
        return res;
    };
})();

/* tapFlash: הבזק זכוכית קצר וחד בנקודת המגע - פידבק פיזי לכל לחיצה */
(function tapFlash(){
    const SEL = 'button, .nav-btn, .game-back, .member-card, .place-card, .meal-tab, .mp-wa, .member-wa';
    document.addEventListener('pointerdown', e => {
        const t = e.target.closest(SEL);
        if (!t || t.disabled) return;
        const f = document.createElement('span');
        f.className = 'tap-flash';
        f.style.left = e.clientX + 'px';
        f.style.top = e.clientY + 'px';
        document.body.appendChild(f);
        setTimeout(() => f.remove(), 420);
    }, { passive: true });
})();

/* a11yModalFocus: כשמודאל נפתח - פוקוס נכנס פנימה ולוכד Tab, ובסגירה חוזר לכפתור שפתח.
   מאזין על שינוי class של .modal-overlay (open) - מרכזי לכל הדפים. */
(function a11yModalFocus(){
    let lastFocus = null;
    const FOCUSABLE = 'button, [href], input, textarea, select, [tabindex]:not([tabindex="-1"])';
    function trap(sheet, e){
        if (e.key !== 'Tab') return;
        const f = [...sheet.querySelectorAll(FOCUSABLE)].filter(el => el.offsetParent !== null);
        if (!f.length) return;
        const first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
    document.querySelectorAll('.modal-overlay').forEach(ov => {
        const sheet = ov.querySelector('.modal-sheet');
        if (!sheet) return;
        new MutationObserver(() => {
            if (ov.classList.contains('open')) {
                lastFocus = document.activeElement;
                const target = sheet.querySelector('textarea, input, button') || sheet;
                setTimeout(() => target.focus(), 60);
                sheet._trap = e => trap(sheet, e);
                ov.addEventListener('keydown', sheet._trap);
            } else if (sheet._trap) {
                ov.removeEventListener('keydown', sheet._trap);
                if (lastFocus && lastFocus.focus) lastFocus.focus();
            }
        }).observe(ov, { attributes: true, attributeFilter: ['class'] });
    });
})();
