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
        header('Location: sponsors.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $tier = $_POST['tier'] ?? 'bronze';

        if (empty($name)) {
            $_SESSION['flash_message'] = 'Nama sponsor wajib diisi!';
            $_SESSION['flash_type'] = 'error';
        } else {
            $logoPath = null;
            if (!empty($_FILES['logo']['tmp_name'])) {
                $errors = validateUpload($_FILES['logo']);
                if (empty($errors)) {
                    $filename = secureFileName($_FILES['logo']['name'], 'sponsor_');
                    move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename);
                    $logoPath = 'assets/uploads/' . $filename;
                }
            }

            $stmt = $pdo->prepare("INSERT INTO sponsors (season_id, name, logo, website, tier) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$seasonId, $name, $logoPath, $website, $tier]);

            logActivity($pdo, 'sponsor_create', "Sponsor '$name' ditambahkan", $_SESSION['admin_id']);
            $_SESSION['flash_message'] = "Sponsor '$name' berhasil ditambahkan!";
            $_SESSION['flash_type'] = 'success';
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM sponsors WHERE id = ?");
        $stmt->execute([$id]);

        logActivity($pdo, 'sponsor_delete', "Sponsor ID $id dihapus", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Sponsor berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: sponsors.php');
    exit();
}

$sponsors = $pdo->query("SELECT * FROM sponsors ORDER BY FIELD(tier, 'platinum', 'gold', 'silver', 'bronze'), name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Sponsors - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-bar">
            <h1>🤝 Manage Sponsors</h1>
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
                <h3>➕ Tambah Sponsor</h3>
                <form method="POST" enctype="multipart/form-data" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label>Nama Sponsor</label>
                        <input type="text" name="name" required placeholder="Nama sponsor">
                    </div>

                    <div class="form-group">
                        <label>Logo</label>
                        <input type="file" name="logo" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label>Website</label>
                        <input type="url" name="website" placeholder="https://...">
                    </div>

                    <div class="form-group">
                        <label>Tier</label>
                        <select name="tier">
                            <option value="platinum">Platinum</option>
                            <option value="gold">Gold</option>
                            <option value="silver">Silver</option>
                            <option value="bronze" selected>Bronze</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">➕ Tambah Sponsor</button>
                </form>
            </div>

            <div class="card">
                <h3>📋 Daftar Sponsors</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Nama</th>
                            <th>Tier</th>
                            <th>Website</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sponsors as $s): ?>
                        <tr>
                            <td>
                                <?php if ($s['logo']): ?>
                                <img src="../<?= e($s['logo']) ?>" alt="" class="thumb">
                                <?php else: ?>
                                <div class="no-image">🏢</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= e($s['name']) ?></strong></td>
                            <td><span class="badge badge-tier-<?= e($s['tier']) ?>"><?= e($s['tier']) ?></span></td>
                            <td><a href="<?= e($s['website']) ?>" target="_blank"><?= e($s['website'] ?: '-') ?></a></td>
                            <td>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Yakin hapus sponsor ini?')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button type="submit" class="btn-small btn-danger">🗑️</button>
                                </form>
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
