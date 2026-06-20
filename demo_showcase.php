<?php
// עמוד תצוגת דמו (פלייגראונד): האפליקציה במוקאפ טלפון + פאנל הנחיות + מחליף צבעים
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
    <style>
        html, body { margin: 0; height: 100%; }
        body { font-family: 'Noto Sans Hebrew', sans-serif; color: var(--text); }
        .pg {
            height: 100vh; box-sizing: border-box; padding: 24px clamp(20px, 5vw, 64px);
            display: flex; flex-direction: column; gap: clamp(18px, 3vh, 36px); overflow: hidden;
            background:
                radial-gradient(ellipse 70% 45% at 50% -5%, rgba(var(--accent-rgb), 0.10) 0%, transparent 60%),
                linear-gradient(180deg, #0a0a0f 0%, #060608 100%);
            transition: background .4s ease;
        }
        .pg-head { text-align: center; }
        .pg-crown { width: 42px; height: 42px; display: inline-block; filter: drop-shadow(0 0 14px var(--gold-glow)); }
        .pg-title {
            font-size: clamp(1.5rem, 2.6vw, 2.1rem); font-weight: 800; margin: 4px 0 0; letter-spacing: -0.5px;
            background: var(--grad-gold-text); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        }
        .pg-sub { color: var(--text-muted); font-size: clamp(.85rem, 1.3vw, 1rem); margin: 6px 0 0; }

        .pg-body {
            flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(300px, 420px) 1fr;
            gap: clamp(28px, 5vw, 72px); align-items: center; max-width: 1200px; width: 100%; margin: 0 auto;
        }
        .pg-stage { display: flex; flex-direction: column; align-items: center; gap: 26px; min-height: 0; height: 100%; justify-content: center; }

        /* מוקאפ טלפון - רחב ככל שמתאפשר בגובה הזמין */
        .phone {
            height: min(880px, calc(100vh - 250px)); aspect-ratio: 390 / 844; position: relative;
            background: #050507; border-radius: 52px; padding: 12px;
            box-shadow: 0 36px 80px rgba(0,0,0,.7), 0 0 0 2px #1c1c24, 0 0 0 12px #0c0c11, 0 0 0 14px #24242e;
        }
        .phone::before { content: ''; position: absolute; top: 17px; left: 50%; transform: translateX(-50%);
            width: 118px; height: 26px; background: #050507; border-radius: 16px; z-index: 3; }
        .phone iframe { width: 100%; height: 100%; border: 0; border-radius: 42px; background: #06060a; display: block; }

        /* בקרות מתחת לדמו */
        .stage-controls { display: flex; flex-direction: column; align-items: center; gap: 16px; }
        .phase-toggle { display: inline-flex; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-pill); padding: 4px; gap: 4px; }
        .phase-btn { border: 0; background: transparent; color: var(--text-muted); font-family: inherit; font-weight: 600;
            font-size: .9rem; padding: 8px 18px; border-radius: var(--radius-pill); cursor: pointer; transition: all .15s; white-space: nowrap; }
        .phase-btn.active { background: var(--grad-gold); color: #1a1505; }
        .swatches { display: flex; gap: 16px; }

        /* פאנל - בשפת המוצר */
        .pg-panel { display: flex; flex-direction: column; gap: 18px; }
        .panel-card {
            background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-lg);
            overflow: hidden; backdrop-filter: var(--blur-sm);
        }
        .panel-card > details { border-bottom: 1px solid rgba(255,255,255,0.055); }
        .panel-card > details:last-child { border-bottom: 0; }
        .panel-card summary {
            list-style: none; cursor: pointer; padding: 15px 18px; font-weight: 700; font-size: 1rem;
            color: var(--text); display: flex; justify-content: space-between; align-items: center; transition: color .15s;
        }
        .panel-card summary::-webkit-details-marker { display: none; }
        .panel-card summary:hover { color: var(--gold-light); }
        .panel-card summary::after { content: ''; width: 7px; height: 7px; border-right: 2px solid var(--text-muted);
            border-bottom: 2px solid var(--text-muted); transform: rotate(45deg); transition: transform .2s; margin-bottom: 3px; }
        .panel-card details[open] summary { color: var(--gold-light); }
        .panel-card details[open] summary::after { transform: rotate(-135deg); margin-bottom: -3px; }
        .panel-card .acc-body { padding: 0 18px 16px; color: var(--text); font-size: .92rem; line-height: 1.75; opacity: .92; }
        .acc-body ul { margin: 0; padding-inline-start: 18px; }
        .acc-body li { margin: 4px 0; }
        .acc-body code { background: rgba(255,255,255,0.08); padding: 1px 6px; border-radius: 6px; font-size: .85em; }
        .pg-links { display: flex; gap: 12px; flex-wrap: wrap; }
        .pg-link {
            flex: 1; text-align: center; text-decoration: none; padding: 12px 14px; border-radius: var(--radius-md);
            font-weight: 600; font-size: .9rem; border: 1px solid var(--border-gold); color: var(--gold-light);
            background: var(--gold-dim); transition: background .15s, transform .1s; white-space: nowrap;
        }
        .pg-link:hover { background: var(--gold-glow-soft); }
        .pg-link:active { transform: scale(.98); }
        .pg-made { color: var(--text-subtle); font-size: .82rem; margin: 2px 0 0; text-align: center; }
        .pg-made a { color: var(--gold-light); text-decoration: none; font-weight: 600; }

        @media (max-width: 880px) {
            .pg { height: auto; min-height: 100vh; overflow: visible; }
            .pg-body { grid-template-columns: 1fr; gap: 30px; }
            .pg-panel { max-width: 460px; margin: 0 auto; width: 100%; }
            .phone { height: 74vh; }
        }
    </style>
</head>
<body>
    <div class="pg">
        <header class="pg-head">
            <svg class="pg-crown" viewBox="0 0 100 100" aria-hidden="true"><defs><linearGradient id="cg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--gold-bright)"/><stop offset="0.5" stop-color="var(--gold)"/><stop offset="1" stop-color="var(--primary-dark)"/></linearGradient></defs><path fill="url(#cg)" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="url(#cg)"/><circle cx="14" cy="32" r="5" fill="var(--gold-bright)"/><circle cx="50" cy="20" r="5.5" fill="var(--gold-bright)"/><circle cx="86" cy="32" r="5" fill="var(--gold-bright)"/></svg>
            <h1 class="pg-title"><?= htmlspecialchars(APP_NAME) ?></h1>
            <p class="pg-sub">אפליקציית טיול חבר'ה - הדגמה חיה. שחקו עם המוצר, והחליפו צבע למטה.</p>
        </header>

        <div class="pg-body">
            <aside class="pg-panel">
                <div class="panel-card">
                    <details name="acc" open>
                        <summary>מה זה?</summary>
                        <div class="acc-body">אפליקציית טיול לחבורת חברים. מתכננים יחד לאן הולכים בכל יום, סופרים לאחור לטיסה, מעלים תמונות, משחקים - ובמהלך הטיול מצביעים, מדרגים ומתעדים. מוצר אחד שמתפתח לאורך הטיול.</div>
                    </details>
                    <details name="acc">
                        <summary>הפיצ'רים</summary>
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
                    <details name="acc">
                        <summary>טכנולוגיה</summary>
                        <div class="acc-body">PHP 8 + SQLite, בלי פריימוורק, וניל JS. RTL, מובייל-פירסט. כל המוצר - מחקר חוויה, עיצוב וקוד - נבנה מפרומפטים בלבד. אפס נגיעה בפיגמה.</div>
                    </details>
                    <details name="acc">
                        <summary>התאמה אישית</summary>
                        <div class="acc-body">השם למעלה הוא <strong>כינוי הטיול</strong> שלכם - משתנה בקובץ <code>config.php</code>. גם הצוות, התאריכים, הצבע, וכל טקסט. יש מדריך מארגן מלא עם פרומפט AI שכותב את כל הקופי בשפה ובהומור של החבורה שלכם.</div>
                    </details>
                    <details name="acc">
                        <summary>מאיתנו 💛</summary>
                        <div class="acc-body">בנינו את זה באהבה עבור חבר קרוב, וזה ישמש אותנו בטיול הקרוב שלנו. אפשר לפתח ולהוסיף עוד המון פיצ'רים - אבל הבסיס כבר מעולה, ואנחנו מחלקים אותו לקהילה באהבה. קחו, שנו, ותהנו בטיול. 🧳</div>
                    </details>
                </div>

                <div class="pg-links">
                    <a class="pg-link" href="https://github.com/guy273/crewcation" target="_blank" rel="noopener">⭐ קוד מקור בגיטהאב</a>
                    <a class="pg-link" href="https://github.com/guy273/crewcation/blob/main/docs/ORGANIZER-GUIDE.md" target="_blank" rel="noopener">📖 מדריך המארגן</a>
                </div>

                <p class="pg-made">נעשה באהבה ע"י <a href="https://resolve.co.il" target="_blank" rel="noopener">סטודיו ריזולב</a></p>
            </aside>

            <div class="pg-stage">
                <div class="phone">
                    <iframe id="appFrame" src="app.php?phase=before" title="<?= htmlspecialchars(APP_NAME) ?>"></iframe>
                </div>
                <div class="stage-controls">
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
        // הסתרת הסקרולר בתוך הטלפון + החלת התמה אחרי טעינה/ניווט פנימי
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

        // מתג שלב: לפני הטיסה / במהלך הטיול - טוען מחדש את האייפריים
        var toggle = document.getElementById('phaseToggle');
        toggle.addEventListener('click', function (e) {
            var b = e.target.closest('.phase-btn');
            if (!b) return;
            toggle.querySelectorAll('.phase-btn').forEach(function (x) { x.classList.toggle('active', x === b); });
            frame.src = 'app.php?phase=' + b.dataset.phase;
        });

        // אקורדיון בלעדי - פתיחת אחד סוגרת את השאר (גיבוי ל-name)
        var accs = document.querySelectorAll('.panel-card details');
        accs.forEach(function (d) {
            d.addEventListener('toggle', function () {
                if (d.open) accs.forEach(function (o) { if (o !== d) o.open = false; });
            });
        });
    })();
    </script>
</body>
</html>
