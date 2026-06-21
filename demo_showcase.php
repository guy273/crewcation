<?php
// עמוד תצוגה: מוקאפ טלפון סטטי (תמונה) + מחליף צבע + לינק לשחק עם הדמו המלא ב-/demo
declare(strict_types=1);
$v = @filemtime(__DIR__ . '/assets/demo-screen-gold.jpg') ?: 1;
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&family=Bricolage+Grotesque:opsz,wght@12..96,300&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
    <style>
        html { scrollbar-gutter: stable; }
        html, body { margin: 0; min-height: 100%; }
        body { font-family: 'Noto Sans Hebrew', sans-serif; color: var(--text);
            background: linear-gradient(180deg, #0a0a0f 0%, #060608 100%); }

        .pg { position: relative; min-height: 100vh; box-sizing: border-box;
            padding: 32px clamp(20px, 5vw, 64px) 26px; display: flex; flex-direction: column; gap: clamp(20px, 3vh, 34px); }
        /* הילה מטושטשת מאחורי הטלפון - תזוזת תאורה עדינה ולא מורגשת, מתחלפת עם הצבע */
        .pg::before { content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(circle 42vw at 27% 56%, rgba(var(--accent-rgb), 0.15), transparent 60%);
            filter: blur(44px); animation: blob-drift 34s ease-in-out infinite; transition: background .4s ease; }
        .pg > * { position: relative; z-index: 1; }
        @keyframes blob-drift {
            0%   { transform: translate(0, 0) scale(1);       opacity: .8; }
            50%  { transform: translate(2.5vw, -2vh) scale(1.05); opacity: 1; }
            100% { transform: translate(0, 0) scale(1);       opacity: .8; }
        }

        /* כותרת ולוגו - ימין למעלה, מיושר עם הפאנל */
        .pg-head { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; max-width: 1240px; width: 100%; margin: 0 auto; }
        .pg-brand { display: flex; align-items: center; gap: 11px; }
        .pg-crown { width: 38px; height: 38px; color: var(--gold-bright); filter: drop-shadow(0 0 14px var(--gold-glow)); transition: color .3s ease; }
        .pg-title { font-family: 'Space Grotesk', 'Noto Sans Hebrew', sans-serif; font-size: clamp(1.6rem, 2.8vw, 2.3rem); font-weight: 700; margin: 0; letter-spacing: -1px;
            background: var(--grad-gold-text); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .pg-sub { color: rgba(255,255,255,0.82); font-weight: 400; font-size: clamp(.85rem, 1.2vw, .98rem); margin: 0; }

        .pg-body { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(320px, 440px) 1fr;
            gap: clamp(28px, 5vw, 80px); align-items: start; max-width: 1240px; width: 100%; margin: 0 auto; }
        .pg-stage { display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 48px; align-self: center; }

        /* מוקאפ טלפון סטטי */
        .phone { height: min(1040px, calc(100vh - 235px)); aspect-ratio: 390 / 844; position: relative;
            background: #050507; border-radius: 54px; padding: 13px;
            box-shadow: 0 36px 80px rgba(0,0,0,.7), 0 0 0 2px #1c1c24, 0 0 0 13px #0c0c11, 0 0 0 15px #24242e; }
        .phone::before { content: ''; position: absolute; top: 18px; left: 50%; transform: translateX(-50%);
            width: 122px; height: 27px; background: #050507; border-radius: 16px; z-index: 3; }
        .phone-screen { width: 100%; height: 100%; border: 0; border-radius: 43px; background: #06060a; display: block;
            object-fit: cover; object-position: top; transition: opacity .2s ease; }

        @property --dp-spin { syntax: '<angle>'; initial-value: 0deg; inherits: false; }
        .demo-play { display: inline-flex; align-items: center; gap: 9px; text-decoration: none; padding: 15px 40px;
            border-radius: var(--radius-pill); color: var(--gold-light); position: relative;
            border: 2px solid transparent;
            background:
                linear-gradient(#0c0c11, #0c0c11) padding-box,
                conic-gradient(from var(--dp-spin), transparent 0deg, var(--gold-bright) 55deg, var(--gold) 105deg, transparent 190deg, transparent 360deg) border-box;
            font-weight: 700; font-size: 1.02rem; transition: transform .12s, box-shadow .2s, filter .2s;
            animation: dp-spin 3.6s linear infinite; }
        @keyframes dp-spin { to { --dp-spin: 360deg; } }
        .demo-play svg { width: 14px; height: 14px; }
        .demo-play:hover { box-shadow: var(--glow-gold-sm); transform: translateY(-2px); filter: brightness(1.15); }
        .demo-play:active { transform: scale(.97); }
        @media (prefers-reduced-motion: reduce) { .demo-play { animation: none; } }

        /* פאנל ימני */
        .pg-panel { display: flex; flex-direction: column; gap: 26px; padding-top: 6px; align-self: center; }
        .ctrl-group { display: flex; flex-direction: column; gap: 10px; align-items: flex-start; }
        .ctrl-label { font-size: .8rem; color: var(--gold-light); font-weight: 600; letter-spacing: .02em; }
        .swatches { display: flex; gap: 15px; }

        /* אקורדיונים בשפת המוצר */
        .pg-acc-label { font-size: .8rem; color: var(--gold-light); font-weight: 600; letter-spacing: .02em; margin: 0 0 4px; }
        .pg-acc .acc summary { padding: .9rem 0.25rem; }
        .pg-acc .acc-title { font-weight: 300; font-size: .96rem; }
        .pg-acc .acc-body { color: rgba(255,255,255,0.66); font-size: .88rem; font-weight: 300; line-height: 1.8; padding: .2rem 0.25rem 1rem; animation: pg-acc-in .32s ease; }
        @keyframes pg-acc-in { from { opacity: 0; } to { opacity: 1; } }
        .pg-acc .acc-body ul { margin: 0; padding-inline-start: 18px; }
        .pg-acc .acc-body li { margin: 5px 0; }
        .pg-acc .acc-body code { background: rgba(255,255,255,0.08); padding: 1px 6px; border-radius: 6px; font-size: .85em; font-weight: 400; }

        /* לינקים */
        .pg-links-wrap { margin-top: 26px; }
        .pg-links-intro { font-size: .86rem; color: rgba(255,255,255,0.7); font-weight: 400; margin: 0 0 12px; line-height: 1.6; }
        .pg-links { display: flex; gap: 12px; flex-wrap: wrap; }
        .pg-link { flex: 1; min-width: 150px; display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            text-decoration: none; padding: 13px 18px; border-radius: var(--radius-pill); font-weight: 500; font-size: .92rem;
            color: var(--text); border: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.015));
            transition: border-color .18s, color .18s, transform .12s, box-shadow .18s; white-space: nowrap; }
        .pg-link svg { width: 18px; height: 18px; flex: none; }
        .pg-link:hover { border-color: var(--border-gold-strong); color: var(--gold-light); transform: translateY(-1px); box-shadow: var(--glow-gold-sm); }
        .pg-link.primary { color: #1a1505; background: var(--grad-gold); border-color: transparent; font-weight: 600; }
        .pg-link.primary:hover { color: #1a1505; box-shadow: var(--glow-gold); }

        /* קרדיט ריזולב - מרכז למטה */
        .pg-foot { display: flex; justify-content: center; padding-top: 8px; }
        .resolve-credit { display: inline-flex; flex-direction: column; align-items: center; gap: 3px; text-decoration: none; opacity: .85; transition: opacity .28s ease; }
        .resolve-credit:hover { opacity: 1; }
        .resolve-credit__made { font-size: 11px; color: #fff; opacity: .9; }
        /* גרדיאנט המותג האמיתי של ריזולב - acid/cyan/violet/magenta/sunshine זורם */
        .resolve-credit__wordmark { font-family: 'Bricolage Grotesque', sans-serif; font-size: 22px; font-weight: 300; line-height: 1;
            background-image: linear-gradient(90deg, #C3E85B, #4AE8FF, #B19EFF, #FF6FB4, #FFE74C, #C3E85B);
            background-size: 300% 100%; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;
            animation: resolve-flow 12s linear infinite; }
        .resolve-credit__dot { display: inline-block; }
        @keyframes resolve-flow { 0%{background-position:0% 50%} 100%{background-position:300% 50%} }
        @media (prefers-reduced-motion: reduce) {
            .resolve-credit__wordmark{animation:none;background:none;-webkit-text-fill-color:#B19EFF;color:#B19EFF}
            .pg::before{animation:none}
        }

        @media (max-width: 900px) {
            .pg { padding-bottom: 100px; }                 /* מקום לכפתור הצף */
            .pg-body { grid-template-columns: 1fr; gap: 26px; align-items: start; }
            .pg-stage { order: -1; gap: 0; }
            .pg-panel { max-width: 460px; margin: 0 auto; width: 100%; align-self: auto; }
            .phone { display: none; }                       /* במובייל בלי מוקאפ - חוסך גלילה מיותרת */
            .pg-head { align-items: center; text-align: center; }
            .ctrl-group { align-items: center; }
            /* כפתור צף קבוע בתחתית המסך, מעל התוכן */
            .demo-play { position: fixed; bottom: calc(14px + env(safe-area-inset-bottom)); left: 16px; right: 16px;
                justify-content: center; z-index: 60; padding: 16px; font-size: 1.05rem;
                background: rgba(10,10,16,0.94); backdrop-filter: blur(10px);
                box-shadow: 0 8px 30px rgba(0,0,0,0.6); }
        }
    </style>
</head>
<body>
    <div class="pg">
        <header class="pg-head">
            <div class="pg-brand">
                <svg class="pg-crown" viewBox="0 0 100 100" aria-hidden="true"><path fill="currentColor" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="currentColor"/><circle cx="14" cy="32" r="5" fill="currentColor"/><circle cx="50" cy="20" r="5.5" fill="currentColor"/><circle cx="86" cy="32" r="5" fill="currentColor"/></svg>
                <h1 class="pg-title"><?= htmlspecialchars(APP_NAME) ?></h1>
            </div>
            <p class="pg-sub">אפליקציית טיול חבר'ה. הציצו, ושחקו עם הדמו המלא.</p>
        </header>

        <div class="pg-body">
            <aside class="pg-panel">
                <p class="pg-acc-label">על המוצר</p>
                <div class="pg-acc">
                    <?php $chev = '<span class="acc-chevron"><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>'; ?>
                    <details class="acc">
                        <summary><span class="acc-title">מה זה?</span><?= $chev ?></summary>
                        <div class="acc-body">אפליקציה לטיול של חבר'ה - בעיקר למסיבות רווקים ורווקות (אופי המשחקים מכוון לשם), אבל מתאימה לכל טיול חבורה. מתכננים יחד לאן הולכים בכל יום, סופרים לאחור לטיסה, מעלים תמונות ומשחקים - ובמהלך הטיול מצביעים, מדרגים ומתעדים הכל. מוצר אחד שחי איתכם מהרגע שקניתם כרטיסים ועד הנחיתה בחזרה.</div>
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

                <div class="pg-links-wrap">
                    <p class="pg-links-intro">אהבתם? הכל קוד פתוח. קחו את הקוד, שנו, והקימו לעצמכם - או צללו למדריך המארגן.</p>
                    <div class="pg-links">
                        <a class="pg-link primary" href="https://github.com/guy273/crewcation" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5C5.7.5.5 5.7.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.2.8-.5v-1.7c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.7 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.2 0 0 1-.3 3.3 1.2a11.5 11.5 0 0 1 6 0C17 4.4 18 4.7 18 4.7c.6 1.7.2 2.9.1 3.2.8.8 1.2 1.8 1.2 3.1 0 4.4-2.7 5.4-5.3 5.7.4.4.8 1.1.8 2.2v3.3c0 .3.2.6.8.5 4.6-1.5 7.9-5.8 7.9-10.9C23.5 5.7 18.3.5 12 .5z"/></svg>
                            קוד מקור בגיטהאב
                        </a>
                        <a class="pg-link" href="https://github.com/guy273/crewcation/blob/main/docs/ORGANIZER-GUIDE.md" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            מדריך המארגן
                        </a>
                    </div>
                </div>
            </aside>

            <div class="pg-stage">
                <div class="phone">
                    <img id="screenImg" class="phone-screen" src="assets/demo-screen-gold.jpg?v=<?= $v ?>" alt="<?= htmlspecialchars(APP_NAME) ?>">
                </div>
                <a class="demo-play" href="/demo" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg> שחקו עם הדמו</a>
            </div>
        </div>

        <footer class="pg-foot">
            <a href="https://resolve.co.il" target="_blank" rel="noopener" class="resolve-credit" aria-label="Resolve Studio">
                <span class="resolve-credit__made">נעשה באהבה ע"י</span>
                <span class="resolve-credit__wordmark" aria-hidden="true">Resolve<span class="resolve-credit__dot">.</span></span>
            </a>
        </footer>
    </div>

    <style>
        .a2hs { position: fixed; inset: 0; z-index: 200; display: none; align-items: flex-end; justify-content: center;
            background: rgba(4,4,8,0.66); backdrop-filter: blur(4px); padding: 16px; }
        .a2hs.show { display: flex; }
        .a2hs-card { width: 100%; max-width: 420px; background: linear-gradient(180deg, #14140f 0%, #0c0c10 100%);
            border: 1px solid var(--border-gold); border-radius: 24px; padding: 26px 22px 22px; text-align: center;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.55); animation: a2hs-up .28s cubic-bezier(.22,1,.36,1); }
        @keyframes a2hs-up { from { transform: translateY(40px); opacity: 0; } to { transform: none; opacity: 1; } }
        .a2hs-title { font-size: 1.18rem; font-weight: 800; color: var(--gold-light); margin: 0 0 8px; }
        .a2hs-sub { font-size: .92rem; line-height: 1.6; color: rgba(255,255,255,0.74); font-weight: 300; margin: 0 0 18px; }
        .a2hs-sub b { color: var(--gold-light); font-weight: 600; }
        .a2hs-go { display: block; width: 100%; padding: 14px; border-radius: var(--radius-pill); border: 0;
            background: var(--grad-gold); color: #1a1505; font-weight: 800; font-size: 1rem; cursor: pointer; }
        .a2hs-skip { display: inline-block; margin-top: 12px; background: none; border: 0; color: var(--text-muted);
            font-size: .82rem; cursor: pointer; text-decoration: underline; }
    </style>
    <div id="a2hs" class="a2hs">
        <div class="a2hs-card">
            <p class="a2hs-title">רגע לפני - הוסיפו למסך הבית</p>
            <p class="a2hs-sub">לחוויה חלקה יותר (מסך מלא, בלי שורת דפדפן): פתחו את <b>תפריט השיתוף</b> בדפדפן ובחרו <b>"הוסף למסך הבית"</b>. אפשר גם פשוט להמשיך לדמו.</p>
            <button id="a2hsGo" class="a2hs-go" type="button">הבנתי, המשך לדמו</button>
        </div>
    </div>

    <script>
    (function () {
        var accs = document.querySelectorAll('.pg-acc .acc');
        accs.forEach(function (d) { d.addEventListener('toggle', function () { if (d.open) accs.forEach(function (o) { if (o !== d) o.open = false; }); }); });

        // מובייל: לחיצה על "שחקו עם הדמו" -> פופאפ "הוסף למסך הבית", ואז לדמו
        var isMobile = window.matchMedia('(max-width: 768px)').matches || (('ontouchstart' in window) && window.matchMedia('(pointer: coarse)').matches);
        var play = document.querySelector('.demo-play');
        var modal = document.getElementById('a2hs');
        var go = document.getElementById('a2hsGo');
        if (isMobile && play && modal && go) {
            play.addEventListener('click', function (e) { e.preventDefault(); modal.classList.add('show'); });
            go.addEventListener('click', function () { window.location.href = '/demo'; });
            modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('show'); });
        }
    })();
    </script>
</body>
</html>
