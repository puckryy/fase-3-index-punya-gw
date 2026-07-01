<?php
require_once '../core/config.php';
requireAdmin();

$csrfToken = generateCsrfToken();
$season = getCurrentSeason($pdo);
$seasonId = $season['id'] ?? 0;

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Token keamanan tidak valid!';
        $_SESSION['flash_type'] = 'error';
        header('Location: teams.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $_SESSION['flash_message'] = 'Nama tim wajib diisi!';
            $_SESSION['flash_type'] = 'error';
        } else {
            $logoPath = null;
            if (!empty($_FILES['logo']['tmp_name'])) {
                $errors = validateUpload($_FILES['logo']);
                if (empty($errors)) {
                    $filename = secureFileName($_FILES['logo']['name'], 'team_');
                    move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename);
                    $logoPath = 'assets/uploads/' . $filename;
                } else {
                    $_SESSION['flash_message'] = implode(', ', $errors);
                    $_SESSION['flash_type'] = 'error';
                    header('Location: teams.php');
                    exit();
                }
            }

            $stmt = $pdo->prepare("INSERT INTO teams (season_id, name, slug, logo, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$seasonId, $name, $slug, $logoPath, $description]);

            logActivity($pdo, 'team_create', "Tim '$name' dibuat", $_SESSION['admin_id']);
            $_SESSION['flash_message'] = "Tim '$name' berhasil dibuat!";
            $_SESSION['flash_type'] = 'success';
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT logo FROM teams WHERE id = ?");
        $stmt->execute([$id]);
        $team = $stmt->fetch();

        if ($team && $team['logo']) {
            @unlink('../' . $team['logo']);
        }

        $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->execute([$id]);

        logActivity($pdo, 'team_delete', "Tim ID $id dihapus", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Tim berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: teams.php');
    exit();
}

$teams = $pdo->prepare("SELECT * FROM teams WHERE season_id = ? ORDER BY name");
$teams->execute([$seasonId]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Teams - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-bar">
            <h1>👥 Manage Teams</h1>
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
                <h3>➕ Tambah Tim</h3>
                <form method="POST" enctype="multipart/form-data" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label>Nama Tim</label>
                        <input type="text" name="name" required placeholder="Contoh: EVOS Legends">
                    </div>

                    <div class="form-group">
                        <label>Logo Tim</label>
                        <input type="file" name="logo" accept="image/*">
                        <small>Max 5MB, format: JPG, PNG, GIF, WEBP</small>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" rows="3" placeholder="Deskripsi tim..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary">➕ Tambah Tim</button>
                </form>
            </div>

            <div class="card">
                <h3>📋 Daftar Tim (Season: <?= e($season['name'] ?? 'None') ?>)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $teams->fetch()): ?>
                        <tr>
                            <td>
                                <?php if ($t['logo']): ?>
                                <img src="../<?= e($t['logo']) ?>" alt="" class="thumb">
                                <?php else: ?>
                                <div class="no-image">🚫</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= e($t['name']) ?></strong></td>
                            <td><?= e($t['slug']) ?></td>
                            <td><span class="badge badge-<?= e($t['status']) ?>"><?= e($t['status']) ?></span></td>
                            <td>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Yakin hapus tim ini? Semua player dan data terkait akan ikut terhapus!')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                    <button type="submit" class="btn-small btn-danger">🗑️ Hapus</button>
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
