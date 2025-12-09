<?php
require_once 'db.php';

$message = '';
$isError = false;
$tempPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/', $email)) {
        $message = 'Please enter the Gmail address you used when registering.';
        $isError = true;
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $message = 'Enter the 10-digit phone number tied to your account.';
        $isError = true;
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND phone = ?');
        $stmt->execute([$email, $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $tempPassword = strtoupper(bin2hex(random_bytes(3)));
            $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->execute([$hash, $user['id']]);
            $message = 'Use the temporary password below to log in, then change it right away.';
            $isError = false;
        } else {
            $message = 'No account matched that email and phone combination.';
            $isError = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="./style.css">
    <script src="assets/theme.js"></script>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            color: var(--text);
            font-family: Montserrat, sans-serif;
            padding: 20px;
        }
        .reset-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            padding: 30px;
        }
        .reset-card h1 {
            text-align: center;
            margin-bottom: 10px;
        }
        .reset-card p.subtitle {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.4em;
            color: var(--muted);
        }
        .notice {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 1.3em;
            border: 1px solid transparent;
        }
        .notice.error {
            background: rgba(239,68,68,0.12);
            color: #b91c1c;
            border-color: rgba(185,28,28,0.35);
        }
        .notice.success {
            background: rgba(34,197,94,0.12);
            color: #166534;
            border-color: rgba(22,101,52,0.35);
        }
        form label {
            display: block;
            font-size: 1.3em;
            margin-bottom: 6px;
            text-align: left;
            color: var(--text);
        }
        form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--input-border);
            border-radius: 6px;
            background: var(--input-bg);
            color: var(--text);
            font-size: 1.4em;
            margin-bottom: 16px;
        }
        form button {
            width: 100%;
            border: 1px solid var(--primary);
            padding: 12px;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font-size: 1.4em;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        form button:hover {
            background: var(--primary-700);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        }
        .temp-password {
            font-size: 1.5em;
            font-weight: 600;
            text-align: center;
            margin-bottom: 18px;
            letter-spacing: 0.1em;
        }
        .back-link {
            display: block;
            margin-top: 14px;
            text-align: center;
            color: var(--primary);
            text-decoration: none;
            font-size: 1.3em;
        }
        .back-link:hover {
            text-decoration: underline;
            color: var(--primary-700);
        }
        #themeToggle {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--primary);
            background: var(--card);
            color: var(--text);
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        #themeToggle:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <button id="themeToggle" type="button">Toggle Theme</button>
    <div class="reset-card">
        <h1>Forgot Password</h1>
        <p class="subtitle">Verify your details to get a temporary password.</p>

        <?php if ($message): ?>
            <div class="notice <?php echo $isError ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$isError && $tempPassword): ?>
            <div class="temp-password"><?php echo htmlspecialchars($tempPassword); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <label for="resetEmail">Email (Gmail only)</label>
            <input type="email" name="email" id="resetEmail" placeholder="you@gmail.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

            <label for="resetPhone">Phone Number</label>
            <input type="text" name="phone" id="resetPhone" placeholder="10-digit phone number" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">

            <button type="submit">Reset Password</button>
        </form>

        <a class="back-link" href="login.php">Back to Login</a>
    </div>
    <script>
      (function(){
        const root = document.documentElement;
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') { root.classList.add('theme-dark'); }
        const toggle = document.getElementById('themeToggle');
        toggle?.addEventListener('click', () => {
          const isDark = root.classList.toggle('theme-dark');
          localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
      })();
    </script>
</body>
</html>
