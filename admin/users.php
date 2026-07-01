<?php
require_once '../core/config.php';
requireAdmin();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Coming Soon - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header-bar">
            <h1>🚧 Coming Soon</h1>
            <div class="user-info">
                <span>👤 <?= e($_SESSION['admin_name']) ?></span>
                <a href="logout.php" class="btn-logout">🚪 Logout</a>
            </div>
        </div>
        <div class="card" style="text-align:center;padding:60px;">
            <h2 style="font-size:48px;margin-bottom:20px;">🚧</h2>
            <h3>Fitur ini sedang dalam pengembangan</h3>
            <p style="color:rgba(255,255,255,0.5);margin-top:15px;">Akan tersedia di update berikutnya.</p>
            <a href="index.php" class="btn-primary" style="display:inline-block;margin-top:30px;text-decoration:none;">🏠 Kembali ke Dashboard</a>
        </div>
    </main>
</body>
</html>
