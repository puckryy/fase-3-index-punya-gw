<?php
require_once '../core/config.php';
requireAdmin();

$csrfToken = generateCsrfToken();
$season = getCurrentSeason($pdo);
$seasonId = $season['id'] ?? 0;

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Token keamanan tidak valid!';
        $_SESSION['flash_type'] = 'error';
        header('Location: days.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $dayNumber = (int)($_POST['day_number'] ?? 1);
        $name = trim($_POST['name'] ?? '');
        $matchDate = $_POST['match_date'] ?? '';

        if (empty($name) || empty($matchDate)) {
            $_SESSION['flash_message'] = 'Nama day dan tanggal wajib diisi!';
            $_SESSION['flash_type'] = 'error';
        } else {
            $stmt = $pdo->prepare("INSERT INTO days (season_id, day_number, name, match_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$seasonId, $dayNumber, $name, $matchDate]);

            logActivity($pdo, 'day_create', "Day '$name' dibuat", $_SESSION['admin_id']);
            $_SESSION['flash_message'] = "Day '$name' berhasil dibuat!";
            $_SESSION['flash_type'] = 'success';
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM days WHERE id = ?");
        $stmt->execute([$id]);

        logActivity($pdo, 'day_delete', "Day ID $id dihapus", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Day berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: days.php');
    exit();
}

$days = $pdo->prepare("SELECT * FROM days WHERE season_id = ? ORDER BY match_date");
$days->execute([$seasonId]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Days - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-bar">
            <h1>📅 Manage Days</h1>
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
                <h3>➕ Tambah Day</h3>
                <form method="POST" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nomor Day</label>
                            <input type="number" name="day_number" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Day</label>
                            <input type="text" name="name" required placeholder="Contoh: Day 1 - Group Stage">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Pertandingan</label>
                        <input type="date" name="match_date" required>
                    </div>

                    <button type="submit" class="btn-primary">➕ Tambah Day</button>
                </form>
            </div>

            <div class="card">
                <h3>📋 Daftar Days</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($d = $days->fetch()): ?>
                        <tr>
                            <td>Day <?= (int)$d['day_number'] ?></td>
                            <td><strong><?= e($d['name']) ?></strong></td>
                            <td><?= e(date('d M Y', strtotime($d['match_date']))) ?></td>
                            <td><span class="badge badge-<?= e($d['status']) ?>"><?= e($d['status']) ?></span></td>
                            <td>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Yakin hapus day ini? Semua match di hari ini akan ikut terhapus!')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <button type="submit" class="btn-small btn-danger">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
