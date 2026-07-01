<?php
require_once '../core/config.php';
requireAdmin();

$csrfToken = generateCsrfToken();

// Get stats
$season = getCurrentSeason($pdo);
$seasonId = $season['id'] ?? 0;

// Count teams
$teamCount = $pdo->query("SELECT COUNT(*) FROM teams WHERE season_id = $seasonId")->fetchColumn();

// Count players
$playerCount = $pdo->query("SELECT COUNT(*) FROM players p JOIN teams t ON p.team_id = t.id WHERE t.season_id = $seasonId")->fetchColumn();

// Count matches
$matchCount = $pdo->query("SELECT COUNT(*) FROM matches WHERE season_id = $seasonId")->fetchColumn();

// Count users
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Recent activity
$activities = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Teams list
$teams = $pdo->prepare("SELECT * FROM teams WHERE season_id = ? ORDER BY name");
$teams->execute([$seasonId]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?= e(SITE_NAME) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0f0f1a;
            color: #e0e0e0;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .sidebar {
            position: fixed;
            left: 0; top: 0;
            width: 260px; height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255,212,4,0.1);
            padding: 25px 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-header {
            padding: 0 25px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-header h2 {
            font-size: 20px;
            background: linear-gradient(135deg, #FFD404, #ff8c00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }
        .sidebar-header p {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-top: 5px;
        }
        .nav-menu {
            list-style: none;
            padding: 0 15px;
        }
        .nav-menu li {
            margin-bottom: 5px;
        }
        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(255,212,4,0.1);
            color: #FFD404;
        }
        .nav-menu .icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header-bar h1 {
            font-size: 26px;
            font-weight: 700;
        }
        .header-bar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-info span {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
        .btn-logout {
            background: rgba(255,71,87,0.15);
            color: #ff4757;
            border: 1px solid rgba(255,71,87,0.3);
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-logout:hover {
            background: rgba(255,71,87,0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 3px;
            background: linear-gradient(90deg, #FFD404, #ff8c00);
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: 800;
            color: #FFD404;
            margin: 10px 0;
        }
        .stat-card .label {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        .card {
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 25px;
        }
        .card h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .activity-list {
            list-style: none;
        }
        .activity-list li {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-list li:last-child {
            border-bottom: none;
        }
        .activity-list .time {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: rgba(46,204,113,0.15); color: #2ecc71; }
        .badge-warning { background: rgba(255,212,4,0.15); color: #FFD404; }
        .badge-danger { background: rgba(255,71,87,0.15); color: #ff4757; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .quick-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: #e0e0e0;
            text-decoration: none;
            transition: all 0.3s;
        }
        .quick-btn:hover {
            background: rgba(255,212,4,0.1);
            border-color: rgba(255,212,4,0.3);
            transform: translateY(-3px);
        }
        .quick-btn .icon {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }
        .quick-btn span {
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>🔥 MPL ADMIN</h2>
            <p>Control Panel v2.0</p>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
            <li><a href="seasons.php"><span class="icon">🏆</span> Seasons</a></li>
            <li><a href="teams.php"><span class="icon">👥</span> Teams</a></li>
            <li><a href="players.php"><span class="icon">🎮</span> Players</a></li>
            <li><a href="matches.php"><span class="icon">⚔️</span> Matches</a></li>
            <li><a href="posters.php"><span class="icon">🖼️</span> Posters</a></li>
            <li><a href="sponsors.php"><span class="icon">🤝</span> Sponsors</a></li>
            <li><a href="users.php"><span class="icon">👤</span> Users</a></li>
            <li><a href="voting.php"><span class="icon">🗳️</span> Voting Stats</a></li>
            <li><a href="shop.php"><span class="icon">🛒</span> Shop & Gacha</a></li>
            <li><a href="settings.php"><span class="icon">⚙️</span> Settings</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header-bar">
            <h1>Dashboard Overview</h1>
            <div class="user-info">
                <span>👤 <?= e($_SESSION['admin_name']) ?> (<?= e($_SESSION['admin_role']) ?>)</span>
                <a href="logout.php" class="btn-logout">🚪 Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">🏆 Season Aktif</div>
                <div class="number"><?= e($season['name'] ?? 'None') ?></div>
            </div>
            <div class="stat-card">
                <div class="label">👥 Total Tim</div>
                <div class="number"><?= (int)$teamCount ?></div>
            </div>
            <div class="stat-card">
                <div class="label">🎮 Total Player</div>
                <div class="number"><?= (int)$playerCount ?></div>
            </div>
            <div class="stat-card">
                <div class="label">👤 Total User</div>
                <div class="number"><?= (int)$userCount ?></div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <h3>📋 Aktivitas Terbaru</h3>
                <ul class="activity-list">
                    <?php foreach ($activities as $act): ?>
                    <li>
                        <span>
                            <?php 
                            $badge = match($act['type']) {
                                'login_success' => '<span class="badge badge-success">Login</span>',
                                'login_failed' => '<span class="badge badge-danger">Gagal</span>',
                                'logout' => '<span class="badge badge-warning">Logout</span>',
                                default => '<span class="badge badge-warning">Info</span>'
                            };
                            echo $badge . ' ' . e($act['description']);
                            ?>
                        </span>
                        <span class="time"><?= e(date('H:i', strtotime($act['created_at']))) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card">
                <h3>⚡ Quick Actions</h3>
                <div class="quick-actions">
                    <a href="teams.php?action=add" class="quick-btn">
                        <span class="icon">➕</span>
                        <span>Tambah Tim</span>
                    </a>
                    <a href="matches.php?action=add" class="quick-btn">
                        <span class="icon">📅</span>
                        <span>Buat Jadwal</span>
                    </a>
                    <a href="posters.php?action=upload" class="quick-btn">
                        <span class="icon">🖼️</span>
                        <span>Upload Poster</span>
                    </a>
                    <a href="settings.php" class="quick-btn">
                        <span class="icon">🔧</span>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
