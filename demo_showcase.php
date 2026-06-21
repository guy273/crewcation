<?php
// עמוד תצוגת דמו (פלייגראונד): טלפון משמאל, פאנל בקרה+הנחיות מימין, קרדיט במרכז למטה
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&family=Bricolage+Grotesque:opsz,wght@12..96,300&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
    <style>
        html { scrollbar-gutter: stable; }
        html, body { margin: 0; min-height: 100%; }
        body { font-family: 'Noto Sans Hebrew', sans-serif; color: var(--text);
            background: linear-gradient(180deg, #0a0a0f 0%, #060608 100%); }

        .pg { position: relative; height: 100vh; overflow: hidden; box-sizing: border-box;
            padding: 30px clamp(20px, 5vw, 64px) 24px; display: flex; flex-direction: column; gap: clamp(18px, 2.6vh, 30px); }
        /* בלוב צהוב/אקסנט נע ברקע - מתחלף עם הצבע */
        .pg::before { content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(circle 36vw at 72% 8%, rgba(var(--accent-rgb), 0.16), transparent 56%);
            filter: blur(34px); animation: blob-drift 26s ease-in-out infinite alternate; transition: background .4s ease; }
        .pg > * { position: relative; z-index: 1; }
        @keyframes blob-drift {
            0%   { transform: translate(0, 0) scale(1); }
            33%  { transform: translate(-13vw, 11vh) scale(1.1); }
            66%  { transform: translate(7vw, -5vh) scale(0.95); }
            100% { transform: translate(-4vw, 9vh) scale(1.05); }
        }

        /* כותרת ולוגו - ימין למעלה, מיושר עם הפאנל */
        .pg-head { display: flex; flex-direction: column; align-items: flex-start; gap: 4px;
            max-width: 1240px; width: 100%; margin: 0 auto; }
        .pg-brand { display: flex; align-items: center; gap: 11px; }
        .pg-crown { width: 38px; height: 38px; filter: drop-shadow(0 0 14px var(--gold-glow)); }
        .pg-title { font-family: 'Space Grotesk', 'Noto Sans Hebrew', sans-serif; font-size: clamp(1.6rem, 2.8vw, 2.3rem); font-weight: 700; margin: 0; letter-spacing: -1px;
            background: var(--grad-gold-text); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .pg-sub { color: rgba(255,255,255,0.82); font-weight: 400; font-size: clamp(.85rem, 1.2vw, .98rem); margin: 0; }

        .pg-body { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(320px, 440px) 1fr;
            gap: clamp(28px, 5vw, 80px); align-items: start; max-width: 1240px; width: 100%; margin: 0 auto; }
        .pg-stage { display: flex; justify-content: center; align-items: flex-start; }

        /* מוקאפ טלפון - רחב */
        .phone { height: min(1120px, calc(100vh - 215px)); aspect-ratio: 390 / 844; position: relative;
            background: #050507; border-radius: 54px; padding: 13px;
            box-shadow: 0 36px 80px rgba(0,0,0,.7), 0 0 0 2px #1c1c24, 0 0 0 13px #0c0c11, 0 0 0 15px #24242e; }
        .phone::before { content: ''; position: absolute; top: 18px; left: 50%; transform: translateX(-50%);
            width: 122px; height: 27px; background: #050507; border-radius: 16px; z-index: 3; }
        .phone iframe { width: 100%; height: 100%; border: 0; border-radius: 43px; background: #06060a; display: block; transition: opacity .16s ease; }

        /* פאנל ימני */
        .pg-panel { display: flex; flex-direction: column; gap: 26px; padding-top: 6px; }
        .pg-acc { margin-top: 14px; }
        .ctrl-group { display: flex; flex-direction: column; gap: 10px; align-items: flex-start; }
        .ctrl-label { font-size: .8rem; color: var(--gold-light); font-weight: 600; letter-spacing: .02em; }

        /* טוגלים עדינים - כמו צהריים/ערב במוצר */
        .phase-toggle { display: inline-flex; gap: 8px; }
        .phase-btn { font-family: inherit; font-size: .82rem; font-weight: 500; color: var(--text-muted);
            background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: var(--radius-pill);
            padding: .5rem 1.25rem; cursor: pointer; white-space: nowrap;
            transition: color .25s, background .25s, border-color .25s, transform .35s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .phase-btn.active { color: var(--gold-light); background: var(--gold-dim); border-color: var(--border-gold); }
        .phase-btn:active { transform: scale(0.92); }
        .swatches { display: flex; gap: 15px; }

        /* אקורדיונים בשפת המוצר (.acc) - טקסט לייט, אנימציה עדינה */
        .pg-acc .acc summary { padding: .95rem 0.25rem; }
        .pg-acc .acc-title { font-weight: 300; font-size: 1rem; }
        .pg-acc .acc-body { color: var(--text); font-size: .92rem; font-weight: 300; line-height: 1.75;
            padding: .2rem 0.25rem 1rem; animation: pg-acc-in .32s ease; }
        @keyframes pg-acc-in { from { opacity: 0; } to { opacity: 1; } }
        .pg-acc .acc-body ul { margin: 0; padding-inline-start: 18px; }
        .pg-acc .acc-body li { margin: 5px 0; }
        .pg-acc .acc-body code { background: rgba(255,255,255,0.08); padding: 1px 6px; border-radius: 6px; font-size: .85em; font-weight: 400; }

        /* לינקים - קפסולות, אייקונים SVG */
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
        /* היררכיה גבוהה: זוהר בגוון הנבחר + תזוזה עדינה */
        .pg-link.primary { color: #1a1505; background: var(--grad-gold); border-color: transparent; font-weight: 600;
            box-shadow: 0 0 22px rgba(var(--accent-rgb), 0.4), 0 0 0 1px rgba(var(--accent-rgb), 0.15);
            animation: pg-btn-float 4.5s ease-in-out infinite; }
        .pg-link.primary:hover { color: #1a1505; box-shadow: 0 0 34px rgba(var(--accent-rgb), 0.55); transform: translateY(-2px); }
        @keyframes pg-btn-float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-3px)} }
        @media (prefers-reduced-motion: reduce) { .pg-link.primary{animation:none} }

        /* קרדיט ריזולב - מרכז למטה, לוגו + אנימציה פסיכדלית */
        .pg-foot { display: flex; justify-content: center; padding-top: 6px; }
        .resolve-credit { display: inline-flex; flex-direction: column; align-items: center; gap: 3px; text-decoration: none;
            opacity: .85; transition: opacity .28s ease; }
        .resolve-credit:hover { opacity: 1; }
        .resolve-credit__made { font-size: 11px; color: #fff; opacity: .9; }
        .resolve-credit__wordmark { font-family: 'Bricolage Grotesque', sans-serif; font-size: 22px; font-weight: 300; line-height: 1;
            background: linear-gradient(90deg, #8B8EE0 0%, #FFB089 28%, #B9E8A2 52%, #8B8EE0 76%, #FFB089 100%);
            background-size: 280% auto; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
            animation: resolve-shimmer 32s ease-in-out infinite, resolve-hue 54s ease-in-out infinite; }
        .resolve-credit__dot { display: inline-block; animation: resolve-hue 40s ease-in-out infinite reverse; }
        @keyframes resolve-hue {
            0%   { filter: hue-rotate(0deg)   brightness(1.02) drop-shadow(0 0 10px rgba(139,142,224,0.35)); }
            50%  { filter: hue-rotate(180deg) brightness(1.05) drop-shadow(0 0 14px rgba(185,232,162,0.28)); }
            100% { filter: hue-rotate(360deg) brightness(1.02) drop-shadow(0 0 10px rgba(139,142,224,0.35)); }
        }
        @keyframes resolve-shimmer { 0%{background-position:180% center} 50%{background-position:-180% center} 100%{background-position:180% center} }
        @media (prefers-reduced-motion: reduce) {
            .resolve-credit__wordmark,.resolve-credit__dot{animation:none;background:none;-webkit-text-fill-color:#6C6FD4}
            .pg::before{animation:none}
        }

        @media (max-width: 900px) {
            .pg-body { grid-template-columns: 1fr; gap: 28px; }
            .pg-stage { order: -1; }
            .pg-panel { max-width: 460px; margin: 0 auto; width: 100%; }
            .phone { height: 72vh; }
            .pg-head { align-items: center; text-align: center; }
            .ctrl-group { align-items: center; }
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
                <div class="ctrl-group">
                    <span class="ctrl-label">מצב המוצר</span>
                    <div class="phase-toggle" id="phaseToggle">
                        <button class="phase-btn active" data-phase="before" type="button">לפני הטיסה</button>
                        <button class="phase-btn" data-phase="during" type="button">במהלך הטיול</button>
                    </div>
                </div>
                <div class="ctrl-group">
                    <span class="ctrl-label">צבע</span>
                    <div class="swatches" id="themePicker">
                        <button class="theme-dot t-gold active"   data-theme="gold"   aria-label="זהב"></button>
                        <button class="theme-dot t-pink"          data-theme="pink"   aria-label="ורוד"></button>
                        <button class="theme-dot t-purple"        data-theme="purple" aria-label="סגול"></button>
                        <button class="theme-dot t-sky"           data-theme="sky"    aria-label="תכלת"></button>
                    </div>
                </div>

                <div class="pg-acc">
                    <?php $chev = '<span class="acc-chevron"><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>'; ?>
                    <details class="acc" open>
                        <summary><span class="acc-title">מה זה?</span><?= $chev ?></summary>
                        <div class="acc-body">כל טיול חבר'ה ראוי לאפליקציה משלו. מתכננים יחד לאן הולכים בכל יום, סופרים לאחור לטיסה, מעלים תמונות ומשחקים - ובמהלך הטיול מצביעים, מדרגים ומתעדים הכל. מוצר אחד שחי איתכם מהרגע שקנו את הכרטיסים ועד הנחיתה בחזרה.</div>
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
                    <iframe id="appFrame" src="app.php?phase=before" title="<?= htmlspecialchars(APP_NAME) ?>"></iframe>
                </div>
            </div>
        </div>

        <footer class="pg-foot">
            <a href="https://resolve.co.il" target="_blank" rel="noopener" class="resolve-credit" aria-label="Resolve Studio">
                <span class="resolve-credit__made">נעשה באהבה ע"י</span>
                <span class="resolve-credit__wordmark" aria-hidden="true">Resolve<span class="resolve-credit__dot">.</span></span>
            </a>
        </footer>
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
                    // הקטנה אחידה (zoom) - הכל קטן ומרווח, בלי לשבור את הלייאאוט; + הסתרת סקרולר
                    st.textContent = 'html{zoom:0.86;scrollbar-width:none}body::-webkit-scrollbar,html::-webkit-scrollbar{width:0;height:0;display:none}'
                        + '.app-header{padding-top:36px}'        /* מרווח שההאדר יֵרד מתחת לנוץ' של המוקאפ */
                        + '.profile-greet{font-size:.72rem}';    /* פונט "אהלן" קטן יותר */
                    doc.head.appendChild(st);
                }
            } catch (e) {}
            paintFrame(localStorage.getItem('cw-theme') || 'gold');
            frame.style.opacity = '1'; // fade-in נקי
        });
        pick.addEventListener('click', function (e) { var d = e.target.closest('.theme-dot'); if (d) apply(d.dataset.theme); });

        var toggle = document.getElementById('phaseToggle');
        toggle.addEventListener('click', function (e) {
            var b = e.target.closest('.phase-btn'); if (!b || b.classList.contains('active')) return;
            toggle.querySelectorAll('.phase-btn').forEach(function (x) { x.classList.toggle('active', x === b); });
            var ph = b.dataset.phase;
            frame.style.opacity = '0.55';               // dip עדין (לא לבן מלא)
            setTimeout(function () { frame.src = 'app.php?phase=' + ph; }, 110);
        });

        var accs = document.querySelectorAll('.pg-acc .acc');
        accs.forEach(function (d) { d.addEventListener('toggle', function () { if (d.open) accs.forEach(function (o) { if (o !== d) o.open = false; }); }); });
    })();
    </script>
</body>
</html>
