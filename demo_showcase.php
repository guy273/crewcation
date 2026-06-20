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
            min-height: 100vh; box-sizing: border-box; padding: 30px clamp(20px, 5vw, 64px) 28px;
            display: flex; flex-direction: column; gap: clamp(24px, 4vh, 48px);
            background:
                radial-gradient(ellipse 70% 45% at 50% -5%, rgba(var(--accent-rgb), 0.10) 0%, transparent 60%),
                linear-gradient(180deg, #0a0a0f 0%, #060608 100%);
            transition: background .4s ease;
        }
        .pg-head { text-align: center; }
        .pg-title {
            font-size: clamp(1.7rem, 3vw, 2.4rem); font-weight: 800; margin: 0; letter-spacing: -0.5px;
            background: var(--grad-gold-text); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        }
        .pg-sub { color: var(--text-muted); font-size: clamp(.9rem, 1.4vw, 1.05rem); margin: 8px 0 0; }

        .pg-body {
            flex: 1; display: grid; grid-template-columns: minmax(320px, 440px) 1fr;
            gap: clamp(28px, 5vw, 72px); align-items: center; max-width: 1180px; width: 100%; margin: 0 auto;
        }
        .pg-stage { display: flex; justify-content: center; align-items: center; }

        /* מוקאפ טלפון */
        .phone {
            height: min(720px, 76vh); aspect-ratio: 390 / 844; position: relative;
            background: #050507; border-radius: 50px; padding: 11px;
            box-shadow: 0 36px 80px rgba(0,0,0,.7), 0 0 0 2px #1c1c24, 0 0 0 11px #0c0c11, 0 0 0 13px #24242e;
        }
        .phone::before { content: ''; position: absolute; top: 16px; left: 50%; transform: translateX(-50%);
            width: 116px; height: 26px; background: #050507; border-radius: 16px; z-index: 3; }
        .phone iframe { width: 100%; height: 100%; border: 0; border-radius: 40px; background: #06060a; display: block; }

        /* פאנל */
        .pg-panel { display: flex; flex-direction: column; gap: 22px; }
        .swatches { display: flex; gap: 14px; }
        .panel-card { background: rgba(255,255,255,0.035); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
        .panel-card > details { border-bottom: 1px solid var(--border); }
        .panel-card > details:last-child { border-bottom: 0; }
        .panel-card summary {
            list-style: none; cursor: pointer; padding: 14px 18px; font-weight: 600; font-size: .98rem;
            display: flex; justify-content: space-between; align-items: center; transition: color .15s;
        }
        .panel-card summary::-webkit-details-marker { display: none; }
        .panel-card summary:hover { color: var(--gold-light); }
        .panel-card summary::after { content: '⌄'; color: var(--text-muted); transition: transform .2s; }
        .panel-card details[open] summary::after { transform: rotate(180deg); }
        .panel-card .acc-body { padding: 0 18px 16px; color: var(--text-muted); font-size: .9rem; line-height: 1.7; }
        .acc-body ul { margin: 0; padding-inline-start: 18px; }
        .acc-body li { margin: 3px 0; }
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

        @media (max-width: 860px) {
            .pg-body { grid-template-columns: 1fr; gap: 30px; }
            .pg-panel { max-width: 460px; margin: 0 auto; width: 100%; }
            .phone { height: 70vh; }
        }
    </style>
</head>
<body>
    <div class="pg">
        <header class="pg-head">
            <h1 class="pg-title"><?= htmlspecialchars(APP_NAME) ?></h1>
            <p class="pg-sub">אפליקציית טיול חבר'ה - הדגמה חיה. שחקו עם המוצר, והחליפו צבע.</p>
        </header>

        <div class="pg-body">
            <aside class="pg-panel">
                <div class="swatches" id="themePicker">
                    <button class="theme-dot t-gold active"   data-theme="gold"   aria-label="זהב"></button>
                    <button class="theme-dot t-pink"          data-theme="pink"   aria-label="ורוד"></button>
                    <button class="theme-dot t-purple"        data-theme="purple" aria-label="סגול"></button>
                    <button class="theme-dot t-sky"           data-theme="sky"    aria-label="תכלת"></button>
                </div>

                <div class="panel-card">
                    <details open>
                        <summary>מה זה?</summary>
                        <div class="acc-body">אפליקציית טיול לחבורת חברים. מתכננים יחד לאן הולכים בכל יום, סופרים לאחור לטיסה, מעלים תמונות, משחקים - ובמהלך הטיול מצביעים, מדרגים ומתעדים. מוצר אחד שמתפתח לאורך הטיול.</div>
                    </details>
                    <details>
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
                    <details>
                        <summary>טכנולוגיה</summary>
                        <div class="acc-body">PHP 8 + SQLite, בלי פריימוורק, וניל JS. RTL, מובייל-פירסט. כל המוצר - מחקר חוויה, עיצוב וקוד - נבנה מפרומפטים בלבד. אפס נגיעה בפיגמה.</div>
                    </details>
                    <details>
                        <summary>התאמה אישית</summary>
                        <div class="acc-body">שם האפליקציה, הצוות, התאריכים, הצבע, וכל טקסט - הכל בקובץ <code>config.php</code> ובמקור. יש מדריך מארגן מלא עם פרומפט AI שכותב את כל הקופי בשפה ובהומור של החבורה שלכם.</div>
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
                    <iframe id="appFrame" src="app.php" title="<?= htmlspecialchars(APP_NAME) ?>"></iframe>
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
        frame.addEventListener('load', function () { paintFrame(localStorage.getItem('cw-theme') || 'gold'); });
        pick.addEventListener('click', function (e) { var d = e.target.closest('.theme-dot'); if (d) apply(d.dataset.theme); });
    })();
    </script>
</body>
</html>
