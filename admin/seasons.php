<?php
require_once '../core/config.php';
requireAdmin();

$csrfToken = generateCsrfToken();
$season = getCurrentSeason($pdo);
$seasonId = $season['id'] ?? 0;

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Token keamanan tidak valid!';
        $_SESSION['flash_type'] = 'error';
        header('Location: seasons.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $maxTeams = (int)($_POST['max_teams'] ?? 16);

        if (empty($name)) {
            $_SESSION['flash_message'] = 'Nama season wajib diisi!';
            $_SESSION['flash_type'] = 'error';
        } else {
            $stmt = $pdo->prepare("INSERT INTO seasons (name, description, start_date, end_date, max_teams, status) VALUES (?, ?, ?, ?, ?, 'upcoming')");
            $stmt->execute([$name, $description, $startDate ?: null, $endDate ?: null, $maxTeams]);

            logActivity($pdo, 'season_create', "Season '$name' dibuat", $_SESSION['admin_id']);
            $_SESSION['flash_message'] = "Season '$name' berhasil dibuat!";
            $_SESSION['flash_type'] = 'success';
        }
    }
    elseif ($action === 'activate') {
        $id = (int)($_POST['id'] ?? 0);
        // Deactivate all first
        $pdo->query("UPDATE seasons SET status = 'completed' WHERE status = 'active'");
        // Activate selected
        $stmt = $pdo->prepare("UPDATE seasons SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);

        logActivity($pdo, 'season_activate', "Season ID $id diaktifkan", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Season berhasil diaktifkan!';
        $_SESSION['flash_type'] = 'success';
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM seasons WHERE id = ? AND status != 'active'");
        $stmt->execute([$id]);

        logActivity($pdo, 'season_delete', "Season ID $id dihapus", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Season berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: seasons.php');
    exit();
}

// Get all seasons
$seasons = $pdo->query("SELECT * FROM seasons ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Seasons - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-bar">
            <h1>🏆 Manage Seasons</h1>
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
                <h3>➕ Buat Season Baru</h3>
                <form method="POST" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label>Nama Season</label>
                        <input type="text" name="name" required placeholder="Contoh: Season 3 - MSL ID">
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" rows="3" placeholder="Deskripsi season..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="start_date">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="end_date">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Max Tim</label>
                        <select name="max_teams">
                            <option value="8">8 Tim</option>
                            <option value="16" selected>16 Tim</option>
                            <option value="32">32 Tim</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">🚀 Buat Season</button>
                </form>
            </div>

            <div class="card">
                <h3>📋 Daftar Seasons</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Tim</th>
                            <th>Periode</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seasons as $s): ?>
                        <tr class="<?= $s['status'] === 'active' ? 'row-active' : '' ?>">
                            <td><?= (int)$s['id'] ?></td>
                            <td><strong><?= e($s['name']) ?></strong></td>
                            <td><span class="badge badge-<?= e($s['status']) ?>"><?= e($s['status']) ?></span></td>
                            <td><?= (int)$s['max_teams'] ?> tim</td>
                            <td><?= e($s['start_date'] ?: '-') ?> s/d <?= e($s['end_date'] ?: '-') ?></td>
                            <td>
                                <?php if ($s['status'] !== 'active'): ?>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button type="submit" class="btn-small btn-success">🟢 Aktifkan</button>
                                </form>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Yakin hapus season ini?')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button type="submit" class="btn-small btn-danger">🗑️</button>
                                </form>
                                <?php else: ?>
                                <span class="badge badge-active">✅ Aktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
