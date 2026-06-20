<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

session_start_safe();
// דמו: אין מסך כניסה - ישר פנימה
if (demo_mode() || !empty($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $matchedUid = null;
    $needle = trim($username);
    foreach (USERS as $uid => $user) {
        $full  = $user['name'];
        $first = explode(' ', $full)[0];
        if ($needle === $full || $needle === $first) { $matchedUid = $uid; break; }
    }

    // אימות מול הסיסמה האישית של המשתמש (bcrypt ב-USERS)
    $pwOk = $matchedUid !== null && password_verify($password, USERS[$matchedUid]['password'] ?? '');

    if ($matchedUid === null) {
        $error = 'לא זיהינו את השם. נסה שם פרטי או שם מלא';
    } elseif (!$pwOk) {
        $error = 'סיסמה שגויה';
    } else {
        $_SESSION['user_id'] = $matchedUid;
        header('Location: app.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<script>try{var t=localStorage.getItem("cw-theme");if(t&&t!=="gold")document.documentElement.dataset.theme=t;}catch(e){}</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#080810">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars(APP_NAME) ?>">
    <link rel="apple-touch-icon" href="assets/icon-180.png">
    <link rel="icon" type="image/svg+xml" href="assets/crown.svg">
    <link rel="icon" type="image/png" href="assets/icon-192.png">
    <link rel="manifest" href="manifest.json">
    <title><?= htmlspecialchars(APP_NAME) ?> - מסיבת רווקים לנועם</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
    <style>
        .login-page {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: #0a0a0a;
        }
        .login-logo {
            margin-bottom: 2.5rem;
            text-align: center;
        }
        .login-logo .crown {
            font-size: 3rem;
            line-height: 1;
            margin-bottom: 0.75rem;
        }
        .login-logo h1 {
            font-size: 2.25rem;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: -0.02em;
            line-height: 1;
        }
        .login-logo p {
            font-size: 0.875rem;
            color: var(--muted);
            margin-top: 0.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 360px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem 1.5rem;
        }
        .login-card label {
            display: block;
            font-size: 0.8125rem;
            color: var(--muted);
            margin-bottom: 0.375rem;
            font-weight: 500;
        }
        .login-card input {
            width: 100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            font-family: 'Noto Sans Hebrew', sans-serif;
            color: var(--text);
            margin-bottom: 1rem;
            box-sizing: border-box;
            direction: rtl;
            transition: border-color 0.2s;
        }
        .login-card input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .login-card select {
            width: 100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            font-family: 'Noto Sans Hebrew', sans-serif;
            color: var(--text);
            margin-bottom: 1rem;
            box-sizing: border-box;
            direction: rtl;
            cursor: pointer;
            transition: border-color 0.2s;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23D4A017' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 1rem center;
            background-size: 1.1rem;
        }
        .login-card select:focus { outline: none; border-color: var(--primary); }
        .login-card input[readonly] {
            opacity: 0.7;
            cursor: default;
        }
        .btn-login {
            width: 100%;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 0.9375rem;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Noto Sans Hebrew', sans-serif;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-top: 0.5rem;
        }
        .btn-login:hover { background: var(--primary-dark); }
        .btn-login:active { transform: scale(0.98); }
        .login-error {
            background: rgba(230,57,70,0.12);
            border: 1px solid rgba(230,57,70,0.3);
            border-radius: 8px;
            color: #e63946;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-logo">
        <div class="crown"><svg viewBox="0 0 100 100" width="64" height="64" aria-hidden="true"><defs><linearGradient id="lcg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFD966"/><stop offset="0.5" stop-color="#D4A017"/><stop offset="1" stop-color="#9A7414"/></linearGradient></defs><path fill="url(#lcg)" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/><rect x="18" y="72" width="64" height="9" rx="2" fill="url(#lcg)"/><circle cx="14" cy="32" r="5" fill="#FFD966"/><circle cx="50" cy="20" r="5.5" fill="#FFD966"/><circle cx="86" cy="32" r="5" fill="#FFD966"/></svg></div>
        <h1><?= htmlspecialchars(APP_NAME) ?></h1>
        <p>הטיול מתחיל כאן</p>
    </div>
    <div class="login-card">
        <?php if ($error): ?>
            <div class="login-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <label for="username">שם</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="איך קוראים לך?"
                autocomplete="off"
                autofocus
                required
            >
            <label for="password">סיסמה</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="סיסמה"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                required
            >
            <button type="submit" class="btn-login">כניסה</button>
        </form>
    </div>
</div>
</body>
</html>
