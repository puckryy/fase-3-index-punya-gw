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
        header('Location: players.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $teamId = (int)($_POST['team_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $role = $_POST['role'] ?? 'JUNGLER';
        $jerseyNumber = (int)($_POST['jersey_number'] ?? 0);

        if (empty($name) || $teamId === 0) {
            $_SESSION['flash_message'] = 'Nama player dan tim wajib diisi!';
            $_SESSION['flash_type'] = 'error';
        } else {
            $photoPath = null;
            if (!empty($_FILES['photo']['tmp_name'])) {
                $errors = validateUpload($_FILES['photo']);
                if (empty($errors)) {
                    $filename = secureFileName($_FILES['photo']['name'], 'player_');
                    move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $filename);
                    $photoPath = 'assets/uploads/' . $filename;
                } else {
                    $_SESSION['flash_message'] = implode(', ', $errors);
                    $_SESSION['flash_type'] = 'error';
                    header('Location: players.php');
                    exit();
                }
            }

            $stmt = $pdo->prepare("INSERT INTO players (team_id, name, nickname, role, photo, jersey_number) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$teamId, $name, $nickname, $role, $photoPath, $jerseyNumber]);

            logActivity($pdo, 'player_create', "Player '$name' dibuat", $_SESSION['admin_id']);
            $_SESSION['flash_message'] = "Player '$name' berhasil ditambahkan!";
            $_SESSION['flash_type'] = 'success';
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT photo FROM players WHERE id = ?");
        $stmt->execute([$id]);
        $player = $stmt->fetch();

        if ($player && $player['photo']) {
            @unlink('../' . $player['photo']);
        }

        $stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
        $stmt->execute([$id]);

        logActivity($pdo, 'player_delete', "Player ID $id dihapus", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Player berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: players.php');
    exit();
}

// Get teams for dropdown
$teams = $pdo->prepare("SELECT * FROM teams WHERE season_id = ? ORDER BY name");
$teams->execute([$seasonId]);
$teamList = $teams->fetchAll();

// Get players with team info
$players = $pdo->prepare("SELECT p.*, t.name as team_name FROM players p JOIN teams t ON p.team_id = t.id WHERE t.season_id = ? ORDER BY t.name, p.name");
$players->execute([$seasonId]);

$roles = ['HEAD COACH', 'ANALYST', 'ROAMER', 'MIDLANE', 'EXP LANE', 'GOLD LANE', 'JUNGLER', 'SUBSTITUTE'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Players - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-bar">
            <h1>🎮 Manage Players</h1>
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
                <h3>➕ Tambah Player</h3>
                <form method="POST" enctype="multipart/form-data" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label>Tim</label>
                        <select name="team_id" required>
                            <option value="">-- Pilih Tim --</option>
                            <?php foreach ($teamList as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" required placeholder="Nama player">
                        </div>
                        <div class="form-group">
                            <label>Nickname</label>
                            <input type="text" name="nickname" placeholder="Nickname in-game">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" required>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= e($r) ?>"><?= e($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nomor Jersey</label>
                            <input type="number" name="jersey_number" min="1" max="99" placeholder="1-99">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Foto Player</label>
                        <input type="file" name="photo" accept="image/*">
                        <small>Max 5MB, format: JPG, PNG, GIF, WEBP</small>
                    </div>

                    <button type="submit" class="btn-primary">➕ Tambah Player</button>
                </form>
            </div>

            <div class="card">
                <h3>📋 Daftar Players</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nama</th>
                            <th>Tim</th>
                            <th>Role</th>
                            <th>Jersey</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $players->fetch()): ?>
                        <tr>
                            <td>
                                <?php if ($p['photo']): ?>
                                <img src="../<?= e($p['photo']) ?>" alt="" class="thumb">
                                <?php else: ?>
                                <div class="no-image">👤</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($p['name']) ?></strong><br>
                                <small><?= e($p['nickname']) ?></small>
                            </td>
                            <td><?= e($p['team_name']) ?></td>
                            <td><span class="badge badge-role"><?= e($p['role']) ?></span></td>
                            <td>#<?= (int)$p['jersey_number'] ?></td>
                            <td>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Yakin hapus player ini?')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
