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
        header('Location: posters.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $title = trim($_POST['title'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $order = (int)($_POST['order_position'] ?? 0);

        if (empty($_FILES['poster']['tmp_name'])) {
            $_SESSION['flash_message'] = 'File poster wajib diupload!';
            $_SESSION['flash_type'] = 'error';
        } else {
            $errors = validateUpload($_FILES['poster']);
            if (!empty($errors)) {
                $_SESSION['flash_message'] = implode(', ', $errors);
                $_SESSION['flash_type'] = 'error';
            } else {
                $filename = secureFileName($_FILES['poster']['name'], 'poster_');
                move_uploaded_file($_FILES['poster']['tmp_name'], UPLOAD_DIR . $filename);
                $posterPath = 'assets/uploads/' . $filename;

                $stmt = $pdo->prepare("INSERT INTO poster_slides (season_id, title, image, link, order_position) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$seasonId, $title, $posterPath, $link, $order]);

                logActivity($pdo, 'poster_upload', "Poster '$title' diupload", $_SESSION['admin_id']);
                $_SESSION['flash_message'] = 'Poster berhasil diupload!';
                $_SESSION['flash_type'] = 'success';
            }
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image FROM poster_slides WHERE id = ?");
        $stmt->execute([$id]);
        $poster = $stmt->fetch();

        if ($poster && $poster['image']) {
            @unlink('../' . $poster['image']);
        }

        $stmt = $pdo->prepare("DELETE FROM poster_slides WHERE id = ?");
        $stmt->execute([$id]);

        logActivity($pdo, 'poster_delete', "Poster ID $id dihapus", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Poster berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: posters.php');
    exit();
}

$posters = $pdo->query("SELECT * FROM poster_slides ORDER BY order_position, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Posters - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-bar">
            <h1>🖼️ Manage Posters</h1>
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
                <h3>➕ Upload Poster</h3>
                <form method="POST" enctype="multipart/form-data" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="upload">

                    <div class="form-group">
                        <label>File Poster</label>
                        <input type="file" name="poster" accept="image/*" required>
                        <small>Max 5MB, format: JPG, PNG, GIF, WEBP</small>
                    </div>

                    <div class="form-group">
                        <label>Judul (opsional)</label>
                        <input type="text" name="title" placeholder="Judul poster">
                    </div>

                    <div class="form-group">
                        <label>Link (opsional)</label>
                        <input type="url" name="link" placeholder="https://...">
                    </div>

                    <div class="form-group">
                        <label>Urutan</label>
                        <input type="number" name="order_position" value="0" min="0">
                    </div>

                    <button type="submit" class="btn-primary">📤 Upload Poster</button>
                </form>
            </div>

            <div class="card">
                <h3>📋 Daftar Posters</h3>
                <div class="poster-grid">
                    <?php foreach ($posters as $p): ?>
                    <div class="poster-item">
                        <img src="../<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>">
                        <div class="poster-info">
                            <h4><?= e($p['title'] ?: 'Poster #' . $p['id']) ?></h4>
                            <p>Order: <?= (int)$p['order_position'] ?></p>
                            <form method="POST" class="inline-form" onsubmit="return confirm('Yakin hapus poster ini?')">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn-small btn-danger">🗑️ Hapus</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
