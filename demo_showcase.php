<?php
// עמוד תצוגת דמו (פלייגראונד): טלפון משמאל, פאנל בקרה+הנחיות מימין
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - דמו</title>
    <link rel="icon" type="image/svg+xml" href="assets/crown.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&family=Bricolage+Grotesque:opsz,wght@12..96,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
    <style>
        html, body { margin: 0; height: 100%; }
        body { font-family: 'Noto Sans Hebrew', sans-serif; color: var(--text); }
        .pg {
            min-height: 100vh; box-sizing: border-box; padding: 30px clamp(20px, 5vw, 64px) 28px;
            display: flex; flex-direction: column; gap: clamp(20px, 3vh, 34px);
            background:
                radial-gradient(ellipse 65% 45% at 85% -5%, rgba(var(--accent-rgb), 0.12) 0%, transparent 60%),
                linear-gradient(180deg, #0a0a0f 0%, #060608 100%);
            transition: background .4s ease;
        }
        /* כותרת ולוגו - ימין למעלה */
        .pg-head { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; }
        .pg-brand { display: flex; align-items: center; gap: 11px; }
        .pg-crown { width: 38px; height: 38px; filter: drop-shadow(0 0 14px var(--gold-glow)); }
        .pg-title {
            font-size: clamp(1.5rem, 2.6vw, 2.1rem); font-weight: 800; margin: 0; letter-spacing: -0.5px;
            background: var(--grad-gold-text); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        }
        .pg-sub { color: var(--text-muted); font-size: clamp(.85rem, 1.2vw, .98rem); margin: 0; }

        .pg-body {
            flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(320px, 430px) 1fr;
            gap: clamp(28px, 5vw, 72px); align-items: center; max-width: 1200px; width: 100%; margin: 0 auto;
        }
        .pg-stage { display: flex; justify-content: center; align-items: center; height: 100%; min-height: 0; }

        /* מוקאפ טלפון */
        .phone {
            height: min(880px, calc(100vh - 200px)); aspect-ratio: 390 / 844; position: relative;
            background: #050507; border-radius: 52px; padding: 12px;
            box-shadow: 0 36px 80px rgba(0,0,0,.7), 0 0 0 2px #1c1c24, 0 0 0 12px #0c0c11, 0 0 0 14px #24242e;
        }
        .phone::before { content: ''; position: absolute; top: 17px; left: 50%; transform: translateX(-50%);
            width: 118px; height: 26px; background: #050507; border-radius: 16px; z-index: 3; }
        .phone iframe { width: 100%; height: 100%; border: 0; border-radius: 42px; background: #06060a; display: block; }

        /* פאנל ימני */
        .pg-panel { display: flex; flex-direction: column; gap: 20px; }
        .pg-controls { display: flex; flex-direction: column; gap: 16px; align-items: flex-start; }
        .phase-toggle { display: inline-flex; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-pill); padding: 4px; gap: 4px; }
        .phase-btn { border: 0; background: transparent; color: var(--text-muted); font-family: inherit; font-weight: 600;
            font-size: .9rem; padding: 9px 20px; border-radius: var(--radius-pill); cursor: pointer; transition: all .15s; white-space: nowrap; }
        .phase-btn.active { background: var(--grad-gold); color: #1a1505; }
        .swatches { display: flex; gap: 15px; }

        /* אקורדיונים בשפת המוצר (.acc) - בתוך עטיפה דקה */
        .pg-acc { padding: 0 4px; }
        .pg-acc .acc summary { padding: 1.05rem 0.25rem; }
        .pg-acc .acc-body { color: var(--text); font-size: .92rem; line-height: 1.7; opacity: .9; padding-top: 0; padding-bottom: 1.1rem; }
        .pg-acc .acc-body ul { margin: 0; padding-inline-start: 18px; }
        .pg-acc .acc-body li { margin: 4px 0; }
        .pg-acc .acc-body code { background: rgba(255,255,255,0.08); padding: 1px 6px; border-radius: 6px; font-size: .85em; }

        /* לינקים - קלאס */
        .pg-links { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 4px; }
        .pg-link { flex: 1; min-width: 150px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none; padding: 13px 16px; border-radius: var(--radius-md); font-weight: 600; font-size: .92rem;
            color: var(--text); border: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.015));
            transition: border-color .18s, color .18s, transform .12s, box-shadow .18s; white-space: nowrap; }
        .pg-link:hover { border-color: var(--border-gold-strong); color: var(--gold-light); transform: translateY(-1px); box-shadow: var(--glow-gold-sm); }
        .pg-link.primary { color: #1a1505; background: var(--grad-gold); border-color: transparent; }
        .pg-link.primary:hover { color: #1a1505; box-shadow: var(--glow-gold); }

        /* קרדיט ריזולב - לוגו + אנימציה פסיכדלית */
        .resolve-credit { display: inline-flex; flex-direction: column; align-items: flex-start; gap: 3px; text-decoration: none;
            opacity: .62; transition: opacity .28s ease; margin-top: 2px; }
        .resolve-credit:hover { opacity: 1; }
        .resolve-credit__made { font-size: 11px; color: var(--text-subtle); }
        .resolve-credit__wordmark { font-family: 'Bricolage Grotesque', sans-serif; font-size: 20px; font-weight: 300; line-height: 1;
            background: linear-gradient(90deg, #6C6FD4 0%, #FF8B5C 25%, #9FE870 50%, #6C6FD4 75%, #FF8B5C 100%);
            background-size: 300% auto; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
            animation: resolve-shimmer 4s linear infinite, resolve-hue 8s linear infinite; }
        .resolve-credit__dot { display: inline-block; animation: resolve-hue 3s linear infinite reverse; }
        @keyframes resolve-hue { 0%{filter:hue-rotate(0) brightness(1)} 25%{filter:hue-rotate(90deg) brightness(1.15)} 50%{filter:hue-rotate(180deg) brightness(1.1)} 75%{filter:hue-rotate(280deg) brightness(1.2)} 100%{filter:hue-rotate(360deg) brightness(1)} }
        @keyframes resolve-shimmer { 0%{background-position:200% center} 100%{background-position:-200% center} }
        @media (prefers-reduced-motion: reduce) { .resolve-credit__wordmark,.resolve-credit__dot{animation:none;background:none;-webkit-text-fill-color:#6C6FD4} }

        @media (max-width: 900px) {
            .pg-body { grid-template-columns: 1fr; gap: 28px; }
            .pg-stage { order: -1; }
            .pg-panel { max-width: 460px; margin: 0 auto; width: 100%; }
            .phone { height: 72vh; }
            .pg-head { align-items: center; text-align: center; }
            .pg-controls { align-items: center; }
        }
    </style>
</head>
<body>
    <div class="pg">
        <header class="pg-head">
            <div class="pg-brand">
                <svg class="pg-crown" viewBox="0 0 100 100" aria-hidden="true"><defs><linearGradient id="cg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--gold-bright)"/><stop offset="0.5" stop-color="var(--gold)"/><stop offset="1" stop-color="var(--primary-dark)"/></linearGradient></defs><path fill="url(#cg)" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="url(#cg)"/><circle cx="14" cy="32" r="5" fill="var(--gold-bright)"/><circle cx="50" cy="20" r="5.5" fill="var(--gold-bright)"/><circle cx="86" cy="32" r="5" fill="var(--gold-bright)"/></svg>
                <h1 class="pg-title"><?= htmlspecialchars(APP_NAME) ?></h1>
            </div>
            <p class="pg-sub">אפליקציית טיול חבר'ה - הדגמה חיה. שחקו עם המוצר, החליפו שלב וצבע.</p>
        </header>

        <div class="pg-body">
            <aside class="pg-panel">
                <div class="pg-controls">
                    <div class="phase-toggle" id="phaseToggle">
                        <button class="phase-btn active" data-phase="before" type="button">לפני הטיסה</button>
                        <button class="phase-btn" data-phase="during" type="button">במהלך הטיול</button>
                    </div>
                    <div class="swatches" id="themePicker">
                        <button class="theme-dot t-gold active"   data-theme="gold"   aria-label="זהב"></button>
                        <button class="theme-dot t-pink"          data-theme="pink"   aria-label="ורוד"></button>
                        <button class="theme-dot t-purple"        data-theme="purple" aria-label="סגול"></button>
                        <button class="theme-dot t-sky"           data-theme="sky"    aria-label="תכלת"></button>
                    </div>
                </div>

                <div class="pg-acc">
                    <?php
                    $chev = '<span class="acc-chevron"><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>';
                    ?>
                    <details class="acc" open>
                        <summary><span class="acc-title">מה זה?</span><?= $chev ?></summary>
                        <div class="acc-body">אפליקציית טיול לחבורת חברים. מתכננים יחד לאן הולכים בכל יום, סופרים לאחור לטיסה, מעלים תמונות, משחקים - ובמהלך הטיול מצביעים, מדרגים ומתעדים. מוצר אחד שמתפתח לאורך הטיול.</div>
                    </details>
                    <details class="acc">
                        <summary><span class="acc-title">הפיצ'רים</span><?= $chev ?></summary>
                        <div class="acc-body"><ul>
                            <li>ספירה לאחור לטיסה</li>
                            <li>שיתוף מקומות לכל יום (צהריים + ערב)</li>
                            <li>4 משחקים עם שיאנים</li>
                            <li>גלריית תמונות ודירוג יומי</li>
                            <li>רשימת "לא לשכוח" משותפת</li>
                            <li>כרטיסי שחקן עם תגיות לעריכה</li>
                            <li>PWA - מותקן למסך הבית</li>
                        </ul></div>
                    </details>
                    <details class="acc">
                        <summary><span class="acc-title">טכנולוגיה</span><?= $chev ?></summary>
                        <div class="acc-body">PHP 8 + SQLite, בלי פריימוורק, וניל JS. RTL, מובייל-פירסט. כל המוצר - מחקר חוויה, עיצוב וקוד - נבנה מפרומפטים בלבד. אפס נגיעה בפיגמה.</div>
                    </details>
                    <details class="acc">
                        <summary><span class="acc-title">התאמה אישית</span><?= $chev ?></summary>
                        <div class="acc-body">השם למעלה הוא <strong>כינוי הטיול</strong> שלכם - משתנה בקובץ <code>config.php</code>. גם הצוות, התאריכים, הצבע, וכל טקסט. יש מדריך מארגן מלא עם פרומפט AI שכותב את כל הקופי בשפה ובהומור של החבורה שלכם.</div>
                    </details>
                    <details class="acc">
                        <summary><span class="acc-title">מאיתנו 💛</span><?= $chev ?></summary>
                        <div class="acc-body">בנינו את זה באהבה עבור חבר קרוב, וזה ישמש אותנו בטיול הקרוב שלנו. אפשר לפתח ולהוסיף עוד המון פיצ'רים - אבל הבסיס כבר מעולה, ואנחנו מחלקים אותו לקהילה באהבה. קחו, שנו, ותהנו בטיול. 🧳</div>
                    </details>
                </div>

                <div class="pg-links">
                    <a class="pg-link primary" href="https://github.com/guy273/crewcation" target="_blank" rel="noopener">⭐ קוד מקור בגיטהאב</a>
                    <a class="pg-link" href="https://github.com/guy273/crewcation/blob/main/docs/ORGANIZER-GUIDE.md" target="_blank" rel="noopener">📖 מדריך המארגן</a>
                </div>

                <a href="https://resolve.co.il" target="_blank" rel="noopener" class="resolve-credit" aria-label="Resolve Studio">
                    <span class="resolve-credit__made">נעשה באהבה ע"י</span>
                    <span class="resolve-credit__wordmark" aria-hidden="true">Resolve<span class="resolve-credit__dot">.</span></span>
                </a>
            </aside>

            <div class="pg-stage">
                <div class="phone">
                    <iframe id="appFrame" src="app.php?phase=before" title="<?= htmlspecialchars(APP_NAME) ?>"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var pick  = document.getElementById('themePicker');
        var frame = document.getElementById('appFrame');
        var dots  = pick.querySelectorAll('.theme-dot');
        var cur = 'gold';
        try { cur = localStorage.getItem('cw-theme') || 'gold'; } catch (e) {}

        function paintRoot(t) { if (t && t !== 'gold') document.documentElement.dataset.theme = t; else delete document.documentElement.dataset.theme; }
        function paintFrame(t) {
            try { var doc = frame.contentDocument; if (!doc || !doc.documentElement) return;
                if (t && t !== 'gold') doc.documentElement.dataset.theme = t; else delete doc.documentElement.dataset.theme;
            } catch (e) {}
        }
        function apply(t) {
            try { localStorage.setItem('cw-theme', t); } catch (e) {}
            paintRoot(t); paintFrame(t);
            dots.forEach(function (d) { d.classList.toggle('active', d.dataset.theme === t); });
        }
        apply(cur);
        frame.addEventListener('load', function () {
            try {
                var doc = frame.contentDocument;
                if (doc && !doc.getElementById('cwNoScroll')) {
                    var st = doc.createElement('style'); st.id = 'cwNoScroll';
                    st.textContent = 'html{scrollbar-width:none}body::-webkit-scrollbar,html::-webkit-scrollbar{width:0;height:0;display:none}';
                    doc.head.appendChild(st);
                }
            } catch (e) {}
            paintFrame(localStorage.getItem('cw-theme') || 'gold');
        });
        pick.addEventListener('click', function (e) { var d = e.target.closest('.theme-dot'); if (d) apply(d.dataset.theme); });

        var toggle = document.getElementById('phaseToggle');
        toggle.addEventListener('click', function (e) {
            var b = e.target.closest('.phase-btn');
            if (!b) return;
            toggle.querySelectorAll('.phase-btn').forEach(function (x) { x.classList.toggle('active', x === b); });
            frame.src = 'app.php?phase=' + b.dataset.phase;
        });

        // אקורדיון בלעדי
        var accs = document.querySelectorAll('.pg-acc .acc');
        accs.forEach(function (d) {
            d.addEventListener('toggle', function () { if (d.open) accs.forEach(function (o) { if (o !== d) o.open = false; }); });
        });
    })();
    </script>
</body>
</html>
