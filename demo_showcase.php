<?php
// עמוד תצוגת דמו: האפליקציה בתוך מוקאפ טלפון + מחליף צבעים (כרום של הדמו, לא של המוצר)
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
        .showcase {
            min-height: 100vh; display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 22px; padding: 28px 16px 36px;
            background:
                radial-gradient(ellipse 80% 50% at 50% 0%, rgba(var(--accent-rgb), 0.10) 0%, transparent 60%),
                linear-gradient(180deg, #0a0a0f 0%, #060608 100%);
            font-family: 'Noto Sans Hebrew', sans-serif; color: var(--text);
            transition: background .4s ease;
        }
        .sc-head { text-align: center; }
        .sc-title {
            font-size: 1.9rem; font-weight: 800; margin: 0;
            background: var(--grad-gold-text); -webkit-background-clip: text;
            background-clip: text; -webkit-text-fill-color: transparent;
        }
        .sc-sub { color: var(--text-muted); font-size: .95rem; margin: 6px 0 0; }
        /* מוקאפ טלפון */
        .phone {
            height: min(800px, 78vh); aspect-ratio: 390 / 844; position: relative;
            background: #050507; border-radius: 48px; padding: 11px;
            box-shadow: 0 30px 70px rgba(0,0,0,.65), 0 0 0 2px #1c1c24, 0 0 0 11px #0c0c11,
                        0 0 0 13px #23232c;
        }
        .phone::before {
            content: ''; position: absolute; top: 16px; left: 50%; transform: translateX(-50%);
            width: 116px; height: 26px; background: #050507; border-radius: 16px; z-index: 3;
        }
        .phone iframe { width: 100%; height: 100%; border: 0; border-radius: 38px; background: #06060a; display: block; }
        /* מחליף הצבעים */
        .sc-picker { text-align: center; }
        .sc-picker .tp-label { font-size: .85rem; color: var(--text-muted); margin: 0 0 12px; }
        .sc-made { font-size: .85rem; color: var(--text-subtle); margin: 4px 0 0; }
        .sc-made a { color: var(--gold-light); text-decoration: none; font-weight: 600; }
        .sc-made a:hover { text-decoration: underline; }
        @media (max-width: 480px) { .phone { height: 72vh; } .sc-title { font-size: 1.6rem; } }
    </style>
</head>
<body>
    <main class="showcase">
        <div class="sc-head">
            <h1 class="sc-title"><?= htmlspecialchars(APP_NAME) ?></h1>
            <p class="sc-sub">אפליקציית טיול חבר'ה - הדגמה חיה. שחקו, ובחרו צבע למטה.</p>
        </div>

        <div class="phone">
            <iframe id="appFrame" src="app.php" title="<?= htmlspecialchars(APP_NAME) ?>"></iframe>
        </div>

        <div class="sc-picker">
            <p class="tp-label">בחרו צבע</p>
            <div class="theme-picker" id="themePicker">
                <button class="theme-dot t-gold active"   data-theme="gold"   aria-label="זהב"></button>
                <button class="theme-dot t-pink"          data-theme="pink"   aria-label="ורוד"></button>
                <button class="theme-dot t-purple"        data-theme="purple" aria-label="סגול"></button>
                <button class="theme-dot t-sky"           data-theme="sky"    aria-label="תכלת"></button>
            </div>
            <p class="sc-made">נעשה באהבה ע"י <a href="https://resolve.co.il" target="_blank" rel="noopener">סטודיו ריזולב</a></p>
        </div>
    </main>

    <script>
    (function () {
        var pick  = document.getElementById('themePicker');
        var frame = document.getElementById('appFrame');
        var dots  = pick.querySelectorAll('.theme-dot');
        var cur = 'gold';
        try { cur = localStorage.getItem('cw-theme') || 'gold'; } catch (e) {}

        function paintRoot(t) {
            if (t && t !== 'gold') document.documentElement.dataset.theme = t;
            else delete document.documentElement.dataset.theme;
        }
        function paintFrame(t) {
            try {
                var doc = frame.contentDocument;
                if (!doc || !doc.documentElement) return;
                if (t && t !== 'gold') doc.documentElement.dataset.theme = t;
                else delete doc.documentElement.dataset.theme;
            } catch (e) {}
        }
        function apply(t) {
            try { localStorage.setItem('cw-theme', t); } catch (e) {}
            paintRoot(t); paintFrame(t);
            dots.forEach(function (d) { d.classList.toggle('active', d.dataset.theme === t); });
        }

        apply(cur);
        // ודא שהתמה מוחלת גם כשהאייפריים מסיים לטעון / מנווט פנימה
        frame.addEventListener('load', function () { paintFrame(localStorage.getItem('cw-theme') || 'gold'); });
        pick.addEventListener('click', function (e) {
            var d = e.target.closest('.theme-dot');
            if (d) apply(d.dataset.theme);
        });
    })();
    </script>
</body>
</html>
