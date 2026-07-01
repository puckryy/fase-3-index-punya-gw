<?php
require_once '../core/config.php';
requireAdmin();

$csrfToken = generateCsrfToken();

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Get current config
$config = $pdo->query("SELECT * FROM site_config WHERE id = 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Token keamanan tidak valid!';
        $_SESSION['flash_type'] = 'error';
        header('Location: settings.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_site') {
        $siteName = trim($_POST['site_name'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $youtube = trim($_POST['youtube_stream_url'] ?? '');
        $tiktok = trim($_POST['tiktok_url'] ?? '');
        $ig = trim($_POST['instagram_url'] ?? '');
        $fb = trim($_POST['facebook_url'] ?? '');
        $discord = trim($_POST['discord_url'] ?? '');
        $bracket = $_POST['bracket_status'] ?? 'OFF';
        $maintenance = (int)($_POST['maintenance_mode'] ?? 0);

        $stmt = $pdo->prepare("UPDATE site_config SET 
            site_name = ?, tagline = ?, youtube_stream_url = ?, tiktok_url = ?, 
            instagram_url = ?, facebook_url = ?, discord_url = ?, 
            bracket_status = ?, maintenance_mode = ? WHERE id = 1");
        $stmt->execute([$siteName, $tagline, $youtube, $tiktok, $ig, $fb, $discord, $bracket, $maintenance]);

        logActivity($pdo, 'settings_update', "Site settings diupdate", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Settings berhasil diupdate!';
        $_SESSION['flash_type'] = 'success';
    }
    elseif ($action === 'update_economy') {
        $voteCost = (int)($_POST['vote_cost'] ?? 100);
        $dailyLives = (int)($_POST['daily_lives'] ?? 3);
        $gachaCost = (int)($_POST['gacha_cost'] ?? 500);

        $stmt = $pdo->prepare("UPDATE site_config SET vote_cost = ?, daily_lives = ?, gacha_cost = ? WHERE id = 1");
        $stmt->execute([$voteCost, $dailyLives, $gachaCost]);

        logActivity($pdo, 'economy_update', "Economy settings diupdate", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Economy settings berhasil diupdate!';
        $_SESSION['flash_type'] = 'success';
    }
    elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password_hash FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!password_verify($current, $admin['password_hash'])) {
            $_SESSION['flash_message'] = 'Password saat ini salah!';
            $_SESSION['flash_type'] = 'error';
        } elseif (strlen($new) < 8) {
            $_SESSION['flash_message'] = 'Password baru minimal 8 karakter!';
            $_SESSION['flash_type'] = 'error';
        } elseif ($new !== $confirm) {
            $_SESSION['flash_message'] = 'Password baru tidak cocok!';
            $_SESSION['flash_type'] = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("UPDATE admin SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['admin_id']]);

            logActivity($pdo, 'password_change', "Password admin diubah", $_SESSION['admin_id']);
            $_SESSION['flash_message'] = 'Password berhasil diubah!';
            $_SESSION['flash_type'] = 'success';
        }
    }

    header('Location: settings.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Settings - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-bar">
            <h1>⚙️ Settings</h1>
            <div class="user-info">
                <span>👤 <?= e($_SESSION['admin_name']) ?></span>
                <a href="logout.php" class="btn-logout">🚪 Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <h3>🌐 Site Settings</h3>
                <form method="POST" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_site">

                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?= e($config['site_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Tagline</label>
                        <input type="text" name="tagline" value="<?= e($config['tagline']) ?>" placeholder="Tagline website">
                    </div>

                    <div class="form-group">
                        <label>YouTube Stream URL</label>
                        <input type="url" name="youtube_stream_url" value="<?= e($config['youtube_stream_url']) ?>" placeholder="https://youtube.com/...">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>TikTok</label>
                            <input type="url" name="tiktok_url" value="<?= e($config['tiktok_url']) ?>" placeholder="https://tiktok.com/...">
                        </div>
                        <div class="form-group">
                            <label>Instagram</label>
                            <input type="url" name="instagram_url" value="<?= e($config['instagram_url']) ?>" placeholder="https://instagram.com/...">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Facebook</label>
                            <input type="url" name="facebook_url" value="<?= e($config['facebook_url']) ?>" placeholder="https://facebook.com/...">
                        </div>
                        <div class="form-group">
                            <label>Discord</label>
                            <input type="url" name="discord_url" value="<?= e($config['discord_url']) ?>" placeholder="https://discord.gg/...">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Bracket Status</label>
                            <select name="bracket_status">
                                <option value="OFF" <?= $config['bracket_status'] === 'OFF' ? 'selected' : '' ?>>OFF</option>
                                <option value="ON" <?= $config['bracket_status'] === 'ON' ? 'selected' : '' ?>>ON</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Maintenance Mode</label>
                            <select name="maintenance_mode">
                                <option value="0" <?= $config['maintenance_mode'] == 0 ? 'selected' : '' ?>>OFF</option>
                                <option value="1" <?= $config['maintenance_mode'] == 1 ? 'selected' : '' ?>>ON</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">💾 Save Settings</button>
                </form>
            </div>

            <div class="card">
                <h3>💰 Economy Settings</h3>
                <form method="POST" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_economy">

                    <div class="form-group">
                        <label>Harga Token Vote (Points)</label>
                        <input type="number" name="vote_cost" value="<?= (int)$config['vote_cost'] ?>" min="1" required>
                        <small>1 token vote = berapa points minigame</small>
                    </div>

                    <div class="form-group">
                        <label>Daily Lives (Minigame)</label>
                        <input type="number" name="daily_lives" value="<?= (int)$config['daily_lives'] ?>" min="1" max="10" required>
                        <small>Berapa kali user bisa main minigame per hari</small>
                    </div>

                    <div class="form-group">
                        <label>Harga Gacha (Points)</label>
                        <input type="number" name="gacha_cost" value="<?= (int)$config['gacha_cost'] ?>" min="1" required>
                        <small>1x pull gacha = berapa points</small>
                    </div>

                    <button type="submit" class="btn-primary">💾 Save Economy</button>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top: 25px;">
            <h3>🔐 Change Password</h3>
            <form method="POST" class="form-styled" style="max-width: 500px;">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label>Password Saat Ini</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" required minlength="8">
                    <small>Minimal 8 karakter</small>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" required minlength="8">
                </div>

                <button type="submit" class="btn-primary">🔐 Ubah Password</button>
            </form>
        </div>
    </main>
</body>
</html>
