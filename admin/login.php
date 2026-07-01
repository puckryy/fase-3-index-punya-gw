<?php
require_once '../core/config.php';

// Already logged in?
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $error = 'Token keamanan tidak valid. Silakan refresh halaman.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Rate limiting - 5 attempts per 5 minutes (more reasonable)
        if (!checkRateLimit($ip, 5, 300)) {
            $error = 'Terlalu banyak percobaan login. Coba lagi dalam 5 menit.';
            logActivity($pdo, 'login_blocked', "IP $ip diblokir karena terlalu banyak percobaan", null, $ip);
        } else {
            if (empty($username) || empty($password)) {
                $error = 'Username dan password wajib diisi.';
            } else {
                // Fetch admin
                $stmt = $pdo->prepare("SELECT id, username, password_hash, name, role, status FROM admin WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    if ($admin['status'] !== 'active') {
                        $error = 'Akun admin tidak aktif.';
                        logActivity($pdo, 'login_inactive', "Admin {$admin['username']} mencoba login tapi status inactive", $admin['id'], $ip);
                    } else {
                        // Success! Reset rate limit and create session
                        resetRateLimit($ip);
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_name'] = $admin['name'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_login_time'] = time();

                        // Update last login
                        $stmt = $pdo->prepare("UPDATE admin SET last_login = NOW(), login_ip = ? WHERE id = ?");
                        $stmt->execute([$ip, $admin['id']]);

                        // Log
                        logActivity($pdo, 'login_success', "Admin {$admin['username']} berhasil login", $admin['id'], $ip);

                        header('Location: index.php');
                        exit();
                    }
                } else {
                    incrementRateLimit($ip);
                    $error = 'Username atau password salah.';
                    logActivity($pdo, 'login_failed', "Percobaan login gagal untuk username: $username", null, $ip);
                }
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login Admin - <?= e(SITE_NAME) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }
        .particles {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        .particle {
            position: absolute;
            width: 4px; height: 4px;
            background: rgba(255,212,4,0.6);
            border-radius: 50%;
            animation: float 15s infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-10vh) rotate(720deg); opacity: 0; }
        }
        .login-container {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #FFD404 0%, #ff8c00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .logo p {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #FFD404;
            background: rgba(255,255,255,0.12);
            box-shadow: 0 0 20px rgba(255,212,4,0.15);
        }
        input::placeholder {
            color: rgba(255,255,255,0.3);
        }
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FFD404 0%, #ff8c00 100%);
            color: #1a1a2e;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255,212,4,0.3);
        }
        .error-msg {
            background: rgba(255,71,87,0.15);
            border: 1px solid rgba(255,71,87,0.3);
            color: #ff4757;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-msg::before {
            content: "⚠️";
            font-size: 18px;
        }
        .security-badge {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .security-badge span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .security-badge .lock {
            color: #2ecc71;
        }
        .help-text {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }
        .help-text a {
            color: #FFD404;
            text-decoration: none;
        }
        .help-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>

    <div class="login-container">
        <div class="logo">
            <h1>🔒 PANEL ADMIN</h1>
            <p>MPL Tournament System</p>
        </div>

        <?php if ($error): ?>
        <div class="error-msg"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       autocomplete="off" autofocus placeholder="Masukkan username admin">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       autocomplete="new-password" placeholder="Masukkan password">
            </div>

            <button type="submit" class="btn-login">🔐 Masuk Sistem</button>
        </form>

        <div class="security-badge">
            <span>
                <span class="lock">🔒</span>
                Sistem Keamanan Tingkat Tinggi
            </span>
        </div>

        <div class="help-text">
            <a href="reset-password.php">🔐 Lupa password? Reset disini</a>
        </div>
    </div>

    <script>
        // Particle animation
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 30; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.left = Math.random() * 100 + '%';
            p.style.animationDelay = Math.random() * 15 + 's';
            p.style.animationDuration = (10 + Math.random() * 10) + 's';
            p.style.width = p.style.height = (2 + Math.random() * 4) + 'px';
            particlesContainer.appendChild(p);
        }
    </script>
</body>
</html>
