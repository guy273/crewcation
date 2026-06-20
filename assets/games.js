/* משחקי הפרלמנט: טטריס, קנדי ראס, באולינג. הפרצופים מ-GAME_FACES (נועם = groom). */
(function () {
    if (typeof GAME_FACES === 'undefined') return;

    const groom = GAME_FACES.find(f => f.groom) || GAME_FACES[0];
    const faceImgs = GAME_FACES.map(f => { const i = new Image(); i.src = f.photo; return i; });
    const groomImg = (() => { const i = new Image(); i.src = groom.photo; return i; })();

    function circleFace(ctx, img, x, y, r) {
        ctx.save();
        ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2); ctx.clip();
        if (img.complete && img.naturalWidth) {
            const s = Math.min(img.naturalWidth, img.naturalHeight);
            ctx.drawImage(img, (img.naturalWidth - s) / 2, (img.naturalHeight - s) / 2, s, s, x - r, y - r, r * 2, r * 2);
        } else {
            ctx.fillStyle = '#D4A017'; ctx.fill();
        }
        ctx.restore();
        ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(212,160,23,0.55)'; ctx.lineWidth = 1.2; ctx.stroke();
    }

    /* ===== משחק הזיכרון: זוגות פרצופים - החברה + אורחות ===== */
    window.gMemory = (function () {
        const WOMEN_IMGS = typeof WOMEN_POOL !== 'undefined' ? [...WOMEN_POOL] : [];
        let first = null, lock = false, moves = 0, matched = 0, total = 0, lastN = 0;

        function pool(pairs) {
            // רק החתן והבנות. החברה לא בקלפים.
            const faces = [groom.photo];
            const women = [...WOMEN_IMGS].sort(() => Math.random() - 0.5);
            while (faces.length < pairs && women.length) faces.push(women.pop());
            return faces.slice(0, pairs);
        }
        function updateScore() {
            const e = document.getElementById('tetrisScore');
            if (e) e.textContent = total ? `${matched / 2}/${total / 2} זוגות · ${moves} ניסיונות` : '';
        }
        function build(cards) {
            const grid = document.getElementById('memGrid');
            grid.innerHTML = '';
            grid.style.display = '';
            document.getElementById('memLevels').style.display = 'none';
            cards.forEach(src => {
                const c = document.createElement('button');
                c.className = 'mem-card';
                c.innerHTML = `<span class="mem-inner">
                    <span class="mem-face mem-back"><svg viewBox="0 0 100 100"><path fill="rgba(212,160,23,0.8)" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="rgba(212,160,23,0.8)"/></svg></span>
                    <span class="mem-face mem-front"><img src="${src}" alt=""></span>
                </span>`;
                c.dataset.src = src;
                c.addEventListener('click', () => flip(c));
                grid.appendChild(c);
            });
        }
        function flip(c) {
            if (lock || c.classList.contains('open') || c.classList.contains('done')) return;
            c.classList.add('open');
            if (!first) { first = c; return; }
            moves++; updateScore();
            const a = first; first = null;
            if (a.dataset.src === c.dataset.src) {
                a.classList.add('done'); c.classList.add('done');
                matched += 2; updateScore();
                if (matched === total) setTimeout(win, 450);
            } else {
                lock = true;
                setTimeout(() => { a.classList.remove('open'); c.classList.remove('open'); lock = false; }, 750);
            }
        }
        function win() {
            const ov = document.getElementById('memOver');
            document.querySelector('#memOver .go-text').textContent = `כל הזוגות ב-${moves} ניסיונות. לא רע.`;
            ov.classList.add('show');
            document.getElementById('memGrid').style.opacity = '0.12';
            // שיא = הכי מעט ניסיונות (נמוך יותר = טוב יותר)
            if (window.saveScore) window.saveScore('tetris', moves, () => window.loadChamp && window.loadChamp('tetris', true));
        }

        return {
            start() {
                // "עוד פעם" אחרי ניצחון = אותה רמה מיד. בחירת כמות אחרת - יוצאים ונכנסים מחדש
                if (lastN) { this.level(lastN); return; }
                document.getElementById('memLevels').style.display = '';
                document.getElementById('memGrid').style.display = 'none';
                document.getElementById('memOver').classList.remove('show');
                first = null; lock = false; moves = 0; matched = 0; total = 0;
                updateScore();
            },
            level(n) {
                lastN = n;
                first = null; lock = false; moves = 0; matched = 0; total = n;
                document.getElementById('memOver').classList.remove('show');
                const grid = document.getElementById('memGrid');
                grid.style.opacity = '1';
                grid.dataset.size = n;
                const cols = ({8:2, 12:3, 24:4, 32:4})[n] || 4;
                const rows = Math.ceil(n / cols);
                const cards = pool(n / 2).flatMap(srcv => [srcv, srcv]).sort(() => Math.random() - 0.5);
                build(cards);
                // בלי גלילה אף פעם: גודל קלף = מה שנכנס גם ברוחב וגם בגובה שנשארו
                const GAP = 10;
                const availW = grid.parentElement.clientWidth;
                const availH = Math.max(180, window.innerHeight - grid.getBoundingClientRect().top - 18);
                const cell = Math.floor(Math.min(
                    (availW - (cols - 1) * GAP) / cols,
                    (availH - (rows - 1) * GAP) / rows
                ));
                grid.style.gridTemplateColumns = `repeat(${cols}, ${cell}px)`;
                grid.style.gridAutoRows = `${cell}px`;
                grid.style.justifyContent = 'center';
                grid.style.width = 'auto';
                updateScore();
            },
        };
    })();

    /* ===== קנדי ראס: DOM קבוע + אנימציות החלקה, פיצוץ ונפילה ===== */
    window.gCandy = (function () {
        const N = 5;
        let board, sel = null, score = 0, busy = false, cells = [];

        function el() { return document.getElementById('candyBoard'); }
        function rndFace() { return Math.floor(Math.random() * GAME_FACES.length); }

        function build() {
            const root = el();
            root.innerHTML = '';
            root.style.gridTemplateColumns = `repeat(${N}, 1fr)`;
            cells = [];
            for (let y = 0; y < N; y++) for (let x = 0; x < N; x++) {
                const c = document.createElement('div');
                c.className = 'candy-cell';
                c.innerHTML = '<img alt="">';
                c.addEventListener('click', () => gCandy.tap(x, y));
                root.appendChild(c);
                cells.push(c);
            }

            /* גרירה חלקה: הפרצוף עוקב אחרי האצבע, השכן זז נגדית, וההחלפה קורית בשחרור.
               מיפוי מדויק עם elementFromPoint (כולל רווחים), רברבנד עדין בקצוות. */
            if (!root.dataset.swipe) {
                root.dataset.swipe = '1';
                const GAP = 8;
                let sx0 = null, sy0 = null, cx = -1, cy = -1, axis = null, nb = null, amt = 0;
                const cellFromPoint = (px, py) => {
                    const t = document.elementFromPoint(px, py);
                    const cell = t && t.closest ? t.closest('.candy-cell') : null;
                    const idx = cell ? cells.indexOf(cell) : -1;
                    return idx >= 0 ? { gx: idx % N, gy: Math.floor(idx / N) } : null;
                };
                const reset = () => {
                    if (cx >= 0) { const c = cellAt(cx, cy); if (c) { c.style.transition = ''; c.style.transform = ''; c.style.zIndex = ''; } }
                    if (nb) { nb.style.transition = ''; nb.style.transform = ''; nb.style.zIndex = ''; }
                    sx0 = null; axis = null; nb = null; amt = 0; cx = cy = -1;
                };
                root.addEventListener('touchstart', e => {
                    if (busy) return;
                    const p = cellFromPoint(e.touches[0].clientX, e.touches[0].clientY);
                    if (!p) return;
                    sx0 = e.touches[0].clientX; sy0 = e.touches[0].clientY;
                    cx = p.gx; cy = p.gy; axis = null; nb = null; amt = 0;
                    if (e.cancelable) e.preventDefault();
                }, { passive: false });
                root.addEventListener('touchmove', e => {
                    if (sx0 === null || busy) return;
                    if (e.cancelable) e.preventDefault();
                    const dx = e.touches[0].clientX - sx0, dy = e.touches[0].clientY - sy0;
                    if (!axis) {
                        if (Math.hypot(dx, dy) < 7) return;
                        axis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
                    }
                    const c = cellAt(cx, cy);
                    const size = c.offsetWidth + GAP;
                    let d = axis === 'x' ? dx : dy;
                    d = Math.max(-size, Math.min(size, d));
                    const tx = cx + (axis === 'x' ? (d > 0 ? 1 : -1) : 0);
                    const ty = cy + (axis === 'y' ? (d > 0 ? 1 : -1) : 0);
                    const inBounds = tx >= 0 && tx < N && ty >= 0 && ty < N;
                    if (!inBounds) d *= 0.22; // רברבנד עדין בקצה
                    const newNb = inBounds ? cellAt(tx, ty) : null;
                    if (nb && nb !== newNb) { nb.style.transition = ''; nb.style.transform = ''; }
                    nb = newNb; amt = d;
                    c.style.transition = 'none'; c.style.zIndex = 3;
                    c.style.transform = axis === 'x' ? `translate3d(${d}px,0,0)` : `translate3d(0,${d}px,0)`;
                    if (nb) {
                        nb.style.transition = 'none';
                        nb.style.transform = axis === 'x' ? `translate3d(${-d}px,0,0)` : `translate3d(0,${-d}px,0)`;
                    }
                }, { passive: false });
                root.addEventListener('touchend', async () => {
                    if (sx0 === null) { reset(); return; }
                    if (!axis) { const tx = cx, ty = cy; reset(); gCandy.tap(tx, ty); return; } // טאפ קצר
                    const c = cellAt(cx, cy);
                    const size = c.offsetWidth + GAP;
                    const commit = Math.abs(amt) > size * 0.42 && nb;
                    const tx = cx + (axis === 'x' ? (amt > 0 ? 1 : -1) : 0);
                    const ty = cy + (axis === 'y' ? (amt > 0 ? 1 : -1) : 0);
                    const ease = 'transform 0.16s cubic-bezier(0.25, 0.8, 0.3, 1)';
                    c.style.transition = ease; if (nb) nb.style.transition = ease;
                    if (commit) {
                        const full = (amt > 0 ? 1 : -1) * size;
                        c.style.transform = axis === 'x' ? `translateX(${full}px)` : `translateY(${full}px)`;
                        nb.style.transform = axis === 'x' ? `translateX(${-full}px)` : `translateY(${-full}px)`;
                        busy = true;
                        const fx = cx, fy = cy;
                        await wait(170);
                        reset();
                        const a = board[fy][fx]; board[fy][fx] = board[ty][tx]; board[ty][tx] = a;
                        sel = null; sync();
                        if (findMatches().size) { busy = false; await resolve(); }
                        else {
                            await wait(90);
                            await slide(fx, fy, tx, ty);
                            const b2 = board[fy][fx]; board[fy][fx] = board[ty][tx]; board[ty][tx] = b2;
                            sync(); busy = false;
                        }
                    } else {
                        c.style.transform = ''; if (nb) nb.style.transform = '';
                        await wait(170);
                        reset();
                    }
                }, { passive: true });
                root.addEventListener('touchcancel', reset, { passive: true });
            }
        }

        async function doSwap(sx, sy, x, y) {
            if (busy) return;
            busy = true;
            await slide(sx, sy, x, y);
            const a = board[sy][sx]; board[sy][sx] = board[y][x]; board[y][x] = a;
            sync();
            if (findMatches().size) {
                busy = false;
                await resolve();
            } else {
                await wait(120);
                await slide(sx, sy, x, y);
                const b2 = board[sy][sx]; board[sy][sx] = board[y][x]; board[y][x] = b2;
                sync();
                busy = false;
            }
        }
        function cellAt(x, y) { return cells[y * N + x]; }
        function sync() {
            for (let y = 0; y < N; y++) for (let x = 0; x < N; x++) {
                const c = cellAt(x, y);
                c.querySelector('img').src = GAME_FACES[board[y][x]].photo;
                c.classList.toggle('sel', !!(sel && sel.x === x && sel.y === y));
            }
        }
        function findMatches() {
            const kill = new Set();
            for (let y = 0; y < N; y++) for (let x = 0; x < N; x++) {
                const v = board[y][x];
                if (x < N - 2 && board[y][x+1] === v && board[y][x+2] === v) [0,1,2].forEach(i => kill.add(`${y},${x+i}`));
                if (y < N - 2 && board[y+1][x] === v && board[y+2][x] === v) [0,1,2].forEach(i => kill.add(`${y+i},${x}`));
            }
            return kill;
        }
        const wait = ms => new Promise(r => setTimeout(r, ms));

        /* החלקה ויזואלית של שני תאים אחד לעבר השני */
        async function slide(x1, y1, x2, y2) {
            const a = cellAt(x1, y1), b = cellAt(x2, y2);
            const dx = b.offsetLeft - a.offsetLeft, dy = b.offsetTop - a.offsetTop;
            a.style.transition = b.style.transition = 'transform 0.2s cubic-bezier(0.25, 0.8, 0.3, 1)';
            a.style.transform = `translate(${dx}px, ${dy}px)`;
            b.style.transform = `translate(${-dx}px, ${-dy}px)`;
            a.style.zIndex = 2;
            await wait(230);
            a.style.transition = b.style.transition = 'none';
            a.style.transform = b.style.transform = '';
            a.style.zIndex = '';
        }

        async function resolve() {
            busy = true;
            let kill = findMatches();
            while (kill.size) {
                // פיצוץ: התאים הקיימים מתכווצים ונעלמים
                // הבזק עדין סביב כל תא שמתפוצץ
                const root = el();
                kill.forEach(k => {
                    const [y, x] = k.split(',').map(Number);
                    const c = cellAt(x, y);
                    c.classList.add('pop');
                    const b = document.createElement('span');
                    b.className = 'candy-burst';
                    b.style.left = c.offsetLeft + c.offsetWidth / 2 + 'px';
                    b.style.top = c.offsetTop + c.offsetHeight / 2 + 'px';
                    // ניצוצות זהב קטנים עפים החוצה
                    for (let si = 0; si < 5; si++) {
                        const sp = document.createElement('i');
                        sp.className = 'candy-spark';
                        const ang = (Math.PI * 2 / 5) * si + Math.random();
                        sp.style.setProperty('--sx', Math.cos(ang) * (26 + Math.random() * 14) + 'px');
                        sp.style.setProperty('--sy', Math.sin(ang) * (26 + Math.random() * 14) + 'px');
                        b.appendChild(sp);
                    }
                    root.appendChild(b);
                    setTimeout(() => b.remove(), 750);
                });
                score += kill.size * 10; updateScore();
                await wait(340);
                // כבידה בלוח
                const fromRow = {};
                for (let x = 0; x < N; x++) {
                    const col = [];
                    for (let y = N - 1; y >= 0; y--) if (!kill.has(`${y},${x}`)) col.push(board[y][x]);
                    for (let y = N - 1; y >= 0; y--) {
                        const nv = col[N - 1 - y];
                        const isNew = nv === undefined;
                        const newVal = isNew ? rndFace() : nv;
                        if (board[y][x] !== newVal || isNew) fromRow[`${y},${x}`] = true;
                        board[y][x] = newVal;
                    }
                }
                // נפילה: תאים שהשתנו נופלים פנימה מלמעלה
                cells.forEach(c => c.classList.remove('pop'));
                sync();
                Object.keys(fromRow).forEach(k => {
                    const [y, x] = k.split(',').map(Number);
                    const c = cellAt(x, y);
                    c.classList.remove('fall'); void c.offsetWidth; c.classList.add('fall');
                });
                await wait(330);
                cells.forEach(c => c.classList.remove('fall'));
                kill = findMatches();
            }
            busy = false;
        }
        function updateScore() {
            const e = document.getElementById('candyScore'); if (e) e.textContent = score;
        }
        // טיימר 67 שניות - אחרי הזמן: סוף משחק ושמירת שיא
        let timeT = null, timeLeft = 67, ended = false;
        function fmtTime(s) { return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); }
        function showTimer() { const el = document.getElementById('candyTimer'); if (el) el.textContent = fmtTime(Math.max(0, timeLeft)); }
        function endGame() {
            ended = true; busy = true; clearInterval(timeT);
            if (window.saveScore) window.saveScore('candy', score, () => window.loadChamp && window.loadChamp('candy', false));
            const sc = document.getElementById('candyOverScore'); if (sc) sc.textContent = score;
            const ov = document.getElementById('candyOver'); if (ov) ov.classList.add('show');
            const tm = document.getElementById('candyTimer'); if (tm) tm.textContent = '0:00';
        }

        return {
            start() {
                const ov = document.getElementById('candyOver'); if (ov) ov.classList.remove('show');
                board = Array.from({ length: N }, () => Array.from({ length: N }, rndFace));
                while (findMatches().size) board = Array.from({ length: N }, () => Array.from({ length: N }, rndFace));
                sel = null; score = 0; busy = false; ended = false; updateScore(); build(); sync();
                if (window.loadChamp) window.loadChamp('candy', false);
                timeLeft = 67; showTimer();
                clearInterval(timeT);
                timeT = setInterval(() => { timeLeft--; showTimer(); if (timeLeft <= 0) endGame(); }, 1000);
            },
            async tap(x, y) {
                if (busy) return;
                if (!sel) { sel = { x, y }; sync(); return; }
                const sx = sel.x, sy = sel.y;
                const dx = Math.abs(sx - x), dy = Math.abs(sy - y);
                if (dx + dy !== 1) { sel = { x, y }; sync(); return; }
                sel = null; sync();
                await doSwap(sx, sy, x, y);
            },
        };
    })();

    /* ===== הגנת החתן: לייזרים, נשקים מיוחדים שנופלים, מוד פאוור ===== */
    window.gDefense = (function () {
        const WOMEN = (typeof WOMEN_POOL !== 'undefined' ? WOMEN_POOL : []).map(src => {
            const i = new Image(); i.src = src; return i;
        });
        let cv, ctx, raf = null, last = 0;
        let amit, lasers, women, score = 0, over = false, spawnT = 0, fireT = 0, speed = 1;
        let pickups = [], powerUntil = 0, powerType = null, lastPowerEnd = 0;
        let demo = false;

        function reset() {
            amit = { x: cv.width / 2, r: 24 };
            lasers = []; women = [];
            pickups = []; powerUntil = 0; powerType = null; lastPowerEnd = 0;
            score = 0; over = false; spawnT = 0; fireT = 0; speed = 1;
            updateScore(); updateTimer(0);
            const ov = document.getElementById('defOver'); if (ov) ov.classList.remove('show');
            cv.parentElement.classList.remove('power-mode');
            cv.style.opacity = '1';
        }
        function updateScore() { const e = document.getElementById('bowlScore'); if (e) e.textContent = score; }
        function updateTimer(secs) {
            const t = document.getElementById('defTimer');
            if (!t) return;
            if (secs > 0) { t.style.display = ''; t.textContent = Math.ceil(secs); }
            else t.style.display = 'none';
        }
        function gameOver() {
            over = true;
            cv.style.opacity = '0.12';
            cv.parentElement.classList.remove('power-mode');
            updateTimer(0);
            const ov = document.getElementById('defOver'); if (ov) ov.classList.add('show');
            if (window.saveScore) window.saveScore('bowling', score, () => window.loadChamp && window.loadChamp('bowling', false));
        }
        function step(ts) {
            const dt = Math.min((ts - last) / 16.7, 2.5) || 1;
            last = ts;
            const W = cv.width, H = cv.height;
            const now = performance.now();
            const powered = now < powerUntil;
            if (!powered && powerType) { powerType = null; lastPowerEnd = now; cv.parentElement.classList.remove('power-mode'); }
            updateTimer(powered ? (powerUntil - now) / 1000 : 0);

            if (!over) {
                // דמו: נועם מכוון לבד אל הקרובה ביותר
                if (demo) {
                    let target = cv.width / 2;
                    let best = -1;
                    women.forEach(w => { if (w.y > best) { best = w.y; target = w.x; } });
                    amit.x += (target - amit.x) * Math.min(0.085 * dt, 1);
                    amit.x = Math.max(amit.r, Math.min(W - amit.r, amit.x));
                }
                fireT += dt * 16.7;
                const rate = powered ? 250 : 330;
                if (fireT > rate) {
                    fireT = 0;
                    if (powered && powerType === 'split') {
                        lasers.push({ x: amit.x, y: H - 64, vx: 0, kind: 'gold' });
                        lasers.push({ x: amit.x, y: H - 64, vx: -2.4, kind: 'gold' });
                        lasers.push({ x: amit.x, y: H - 64, vx: 2.4, kind: 'gold' });
                    } else if (powered && powerType === 'red') {
                        lasers.push({ x: amit.x, y: H - 64, vx: 0, kind: 'orb' });
                    } else if (powered && powerType === 'flower') {
                        [-4.8, -2.4, 0, 2.4, 4.8].forEach(vx => lasers.push({ x: amit.x, y: H - 64, vx, kind: 'gold' }));
                    } else if (powered && powerType === 'moon') {
                        lasers.push({ x: amit.x, y: H - 64, vx: 0, kind: 'wide' });
                    } else {
                        lasers.push({ x: amit.x, y: H - 64, vx: 0, kind: 'gold' });
                    }
                }
                lasers.forEach(l => { l.y -= (l.kind === 'orb' ? 8.5 : l.kind === 'wide' ? 9.5 : 11) * dt; l.x += (l.vx || 0) * dt; });
                lasers = lasers.filter(l => l.y > -30 && l.x > -20 && l.x < W + 20);

                spawnT += dt * 16.7;
                // קושי מתון שעולה בהדרגה: התחלה נוחה, האצה עדינה, רצפה הגיונית
                const interval = demo ? 1700 : Math.max(1500 - score * 3, 640);
                if (spawnT > interval) {
                    spawnT = 0;
                    speed = demo ? 1 : Math.min(1 + score / 320, 2.4);
                    women.push({ x: 30 + Math.random() * (W - 60), y: -24, r: 21, img: WOMEN[Math.floor(Math.random() * WOMEN.length)], vy: (1 + Math.random() * 0.6) * speed, sway: Math.random() * Math.PI * 2 });
                    // נשק מיוחד נופל בין הבנות - נדיר, לא בזמן פאוור, עם קולדאון
                    if (!demo && !powered && pickups.length === 0 && now - lastPowerEnd > 6000 && Math.random() < 0.18) {
                        const types = ['split', 'red', 'moon', 'flower'];
                        pickups.push({ x: 40 + Math.random() * (W - 80), y: -20, spin: 0, type: types[Math.floor(Math.random() * types.length)] });
                    }
                }
                women.forEach(w => { w.y += w.vy * dt; w.x += Math.sin(w.y / 34 + w.sway) * 0.8 * dt; });

                pickups.forEach(p => { p.y += 2.1 * dt; p.spin += 0.09 * dt; });
                pickups = pickups.filter(p => {
                    if (!powered && Math.hypot(p.x - amit.x, p.y - (H - 50)) < 32) {
                        powerType = p.type;
                        powerUntil = performance.now() + 20000;
                        cv.parentElement.classList.add('power-mode');
                        return false;
                    }
                    return p.y < H + 30;
                });

                women = women.filter(w => {
                    const hit = lasers.findIndex(l => Math.hypot(l.x - w.x, l.y - w.y) < w.r + (l.kind === 'orb' ? 11 : l.kind === 'wide' ? 18 : 6));
                    if (hit >= 0) {
                        if (lasers[hit].kind !== 'orb' && lasers[hit].kind !== 'wide') lasers.splice(hit, 1); // אדום וירח חודרים וממשיכים
                        if (!demo) { score += powered ? 20 : 10; updateScore(); }
                        return false;
                    }
                    return true;
                });

                // עברה את הקו של נועם = נפסלנו, בלי מגע ישיר
                if (women.some(w => w.y > H - 58)) gameOver();
            }

            ctx.clearRect(0, 0, W, H);
            lasers.forEach(l => {
                if (l.kind === 'orb') {
                    ctx.save();
                    ctx.shadowColor = 'rgba(255,80,60,0.95)'; ctx.shadowBlur = 14;
                    ctx.fillStyle = '#ff5a48';
                    ctx.beginPath(); ctx.arc(l.x, l.y, 9, 0, Math.PI * 2); ctx.fill();
                    ctx.restore();
                } else if (l.kind === 'wide') {
                    ctx.save();
                    const g = ctx.createLinearGradient(l.x, l.y - 30, l.x, l.y + 8);
                    g.addColorStop(0, 'rgba(255,217,102,0)');
                    g.addColorStop(0.6, 'rgba(255,228,140,0.9)');
                    g.addColorStop(1, 'rgba(255,245,200,1)');
                    ctx.strokeStyle = g; ctx.lineWidth = 16; ctx.lineCap = 'round';
                    ctx.shadowColor = 'rgba(255,217,102,0.9)'; ctx.shadowBlur = 18;
                    ctx.beginPath(); ctx.moveTo(l.x, l.y - 30); ctx.lineTo(l.x, l.y + 6); ctx.stroke();
                    ctx.restore();
                } else {
                    const g = ctx.createLinearGradient(l.x, l.y - 26, l.x, l.y + 6);
                    g.addColorStop(0, 'rgba(255,217,102,0)');
                    g.addColorStop(0.6, 'rgba(255,217,102,0.95)');
                    g.addColorStop(1, 'rgba(255,236,170,1)');
                    ctx.strokeStyle = g;
                    ctx.lineWidth = 3.5;
                    ctx.lineCap = 'round';
                    ctx.shadowColor = 'rgba(212,160,23,0.9)'; ctx.shadowBlur = 10;
                    ctx.beginPath(); ctx.moveTo(l.x, l.y - 26); ctx.lineTo(l.x, l.y + 4); ctx.stroke();
                    ctx.shadowBlur = 0;
                }
            });
            pickups.forEach(p => {
                ctx.save();
                ctx.translate(p.x, p.y); ctx.rotate(p.spin);
                const col = p.type === 'red' ? '#ff5a48' : '#FFD966';
                ctx.shadowColor = col; ctx.shadowBlur = 16;
                ctx.fillStyle = col; ctx.strokeStyle = col;
                if (p.type === 'moon') {
                    // סהר
                    ctx.beginPath(); ctx.arc(0, 0, 13, 0, Math.PI * 2); ctx.fill();
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.beginPath(); ctx.arc(5, -2, 11, 0, Math.PI * 2); ctx.fill();
                } else if (p.type === 'flower') {
                    // פרח - 6 עלים + מרכז
                    for (let i = 0; i < 6; i++) {
                        ctx.beginPath();
                        ctx.ellipse(Math.cos(i * Math.PI / 3) * 8, Math.sin(i * Math.PI / 3) * 8, 5, 8, i * Math.PI / 3, 0, Math.PI * 2);
                        ctx.fill();
                    }
                    ctx.fillStyle = '#1a1505';
                    ctx.beginPath(); ctx.arc(0, 0, 4, 0, Math.PI * 2); ctx.fill();
                } else {
                    // כוכב (split זהב / red אדום)
                    ctx.beginPath();
                    for (let i = 0; i < 10; i++) {
                        const r2 = i % 2 ? 6 : 13;
                        const a2 = (Math.PI / 5) * i - Math.PI / 2;
                        ctx[i ? 'lineTo' : 'moveTo'](Math.cos(a2) * r2, Math.sin(a2) * r2);
                    }
                    ctx.closePath(); ctx.fill();
                }
                ctx.restore();
            });
            women.forEach(w => circleFace(ctx, w.img, w.x, w.y, w.r));
            ctx.save();
            ctx.strokeStyle = powered ? (powerType === 'red' ? 'rgba(255,90,72,0.25)' : 'rgba(255,217,102,0.25)') : 'rgba(255,255,255,0.07)';
            ctx.setLineDash([5, 7]);
            ctx.beginPath(); ctx.moveTo(0, H - 58); ctx.lineTo(W, H - 58); ctx.stroke();
            ctx.restore();
            if (powered) {
                ctx.save();
                const col = powerType === 'red' ? 'rgba(255,90,72,0.9)' : 'rgba(255,217,102,0.95)';
                ctx.shadowColor = col; ctx.shadowBlur = 22;
                ctx.beginPath(); ctx.arc(amit.x, H - 50, amit.r + 4, 0, Math.PI * 2);
                ctx.strokeStyle = col; ctx.lineWidth = 2.5; ctx.stroke();
                ctx.restore();
            }
            circleFace(ctx, groomImg, amit.x, H - 50, amit.r);
            raf = requestAnimationFrame(step);
        }
        function pos(e) {
            const r = cv.getBoundingClientRect();
            const t = e.touches ? e.touches[0] : e;
            return (t.clientX - r.left) * (cv.width / r.width);
        }

        return {
            start(opts) {
                demo = !!(opts && opts.demo);
                cv = document.getElementById('bowlCanvas'); ctx = cv.getContext('2d');
                const w = cv.parentElement.clientWidth;
                cv.width = w; cv.height = Math.max(Math.round(w * 1.5), Math.min(640, window.innerHeight - 220));
                reset();
                if (!cv._wired) {
                    cv._wired = true;
                    const mv = e => { amit.x = Math.max(amit.r, Math.min(cv.width - amit.r, pos(e))); e.preventDefault(); };
                    cv.addEventListener('touchstart', mv, { passive: false });
                    cv.addEventListener('touchmove', mv, { passive: false });
                    cv.addEventListener('mousemove', e => { amit.x = Math.max(amit.r, Math.min(cv.width - amit.r, pos(e))); });
                }
                cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(step);
            },
            /* השתלטות חלקה מהדמו: אותו מצב, בלי הפתעות קשות */
            takeover() {
                demo = false;
                score = 0; updateScore();
                women = women.filter(w => w.y < cv.height * 0.4);
                pickups = []; spawnT = 0;
            },
            restart() { demo = false; reset(); },
            stop() { cancelAnimationFrame(raf); raf = null; },
        };
    })();
})();
