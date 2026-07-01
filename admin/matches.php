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
        header('Location: matches.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $dayId = (int)($_POST['day_id'] ?? 0);
        $matchNumber = (int)($_POST['match_number'] ?? 1);
        $team1Id = (int)($_POST['team1_id'] ?? 0);
        $team2Id = (int)($_POST['team2_id'] ?? 0);
        $matchTime = $_POST['match_time'] ?? '';
        $streamUrl = trim($_POST['stream_url'] ?? '');

        if ($dayId === 0 || $team1Id === 0 || $team2Id === 0 || empty($matchTime)) {
            $_SESSION['flash_message'] = 'Semua field wajib diisi!';
            $_SESSION['flash_type'] = 'error';
        } elseif ($team1Id === $team2Id) {
            $_SESSION['flash_message'] = 'Tim 1 dan Tim 2 tidak boleh sama!';
            $_SESSION['flash_type'] = 'error';
        } else {
            $stmt = $pdo->prepare("INSERT INTO matches (season_id, day_id, match_number, team1_id, team2_id, match_time, custom_stream_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'upcoming')");
            $stmt->execute([$seasonId, $dayId, $matchNumber, $team1Id, $team2Id, $matchTime, $streamUrl ?: null]);

            logActivity($pdo, 'match_create', "Match #$matchNumber dibuat", $_SESSION['admin_id']);
            $_SESSION['flash_message'] = "Match berhasil dibuat!";
            $_SESSION['flash_type'] = 'success';
        }
    }
    elseif ($action === 'update_score') {
        $id = (int)($_POST['id'] ?? 0);
        $score1 = (int)($_POST['score1'] ?? 0);
        $score2 = (int)($_POST['score2'] ?? 0);
        $status = $_POST['status'] ?? 'upcoming';
        $winnerId = null;

        if ($status === 'completed') {
            if ($score1 > $score2) {
                $winnerId = $pdo->prepare("SELECT team1_id FROM matches WHERE id = ?")->execute([$id]);
                $winnerId = $pdo->query("SELECT team1_id FROM matches WHERE id = $id")->fetchColumn();
            } elseif ($score2 > $score1) {
                $winnerId = $pdo->query("SELECT team2_id FROM matches WHERE id = $id")->fetchColumn();
            }
        }

        $stmt = $pdo->prepare("UPDATE matches SET score_team1 = ?, score_team2 = ?, status = ?, winner_id = ? WHERE id = ?");
        $stmt->execute([$score1, $score2, $status, $winnerId, $id]);

        logActivity($pdo, 'match_update', "Score match ID $id diupdate", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Score berhasil diupdate!';
        $_SESSION['flash_type'] = 'success';
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
        $stmt->execute([$id]);

        logActivity($pdo, 'match_delete', "Match ID $id dihapus", $_SESSION['admin_id']);
        $_SESSION['flash_message'] = 'Match berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: matches.php');
    exit();
}

// Get data for dropdowns
$days = $pdo->prepare("SELECT * FROM days WHERE season_id = ? ORDER BY match_date");
$days->execute([$seasonId]);

$teams = $pdo->prepare("SELECT * FROM teams WHERE season_id = ? ORDER BY name");
$teams->execute([$seasonId]);
$teamList = $teams->fetchAll();

// Get matches with team names
$matches = $pdo->prepare("
    SELECT m.*, 
        t1.name as team1_name, t1.logo as team1_logo,
        t2.name as team2_name, t2.logo as team2_logo,
        d.name as day_name, d.match_date
    FROM matches m
    JOIN teams t1 ON m.team1_id = t1.id
    JOIN teams t2 ON m.team2_id = t2.id
    JOIN days d ON m.day_id = d.id
    WHERE m.season_id = ?
    ORDER BY d.match_date, m.match_time
");
$matches->execute([$seasonId]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Matches - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-bar">
            <h1>⚔️ Manage Matches</h1>
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
                <h3>➕ Buat Match</h3>
                <form method="POST" class="form-styled">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Day</label>
                            <select name="day_id" required>
                                <option value="">-- Pilih Day --</option>
                                <?php foreach ($days->fetchAll() as $d): ?>
                                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?> (<?= e($d['match_date']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nomor Match</label>
                            <input type="number" name="match_number" min="1" value="1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Tim 1</label>
                            <select name="team1_id" required>
                                <option value="">-- Pilih Tim 1 --</option>
                                <?php foreach ($teamList as $t): ?>
                                <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tim 2</label>
                            <select name="team2_id" required>
                                <option value="">-- Pilih Tim 2 --</option>
                                <?php foreach ($teamList as $t): ?>
                                <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Jam Match</label>
                            <input type="time" name="match_time" required>
                        </div>
                        <div class="form-group">
                            <label>Custom Stream URL (opsional)</label>
                            <input type="url" name="stream_url" placeholder="https://youtube.com/live/...">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">➕ Buat Match</button>
                </form>
            </div>

            <div class="card">
                <h3>📋 Daftar Matches</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Match</th>
                            <th>Tim</th>
                            <th>Score</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($m = $matches->fetch()): ?>
                        <tr>
                            <td>#<?= (int)$m['match_number'] ?><br><small><?= e($m['day_name']) ?></small></td>
                            <td>
                                <div class="team-vs">
                                    <span><?= e($m['team1_name']) ?></span>
                                    <span class="vs">VS</span>
                                    <span><?= e($m['team2_name']) ?></span>
                                </div>
                            </td>
                            <td class="score-cell">
                                <form method="POST" class="inline-form score-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="update_score">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <input type="number" name="score1" value="<?= (int)$m['score_team1'] ?>" min="0" class="score-input">
                                    <span>:</span>
                                    <input type="number" name="score2" value="<?= (int)$m['score_team2'] ?>" min="0" class="score-input">
                                    <select name="status" class="status-select">
                                        <option value="upcoming" <?= $m['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                        <option value="live" <?= $m['status'] === 'live' ? 'selected' : '' ?>>Live</option>
                                        <option value="completed" <?= $m['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                    <button type="submit" class="btn-small btn-success">💾</button>
                                </form>
                            </td>
                            <td><?= e($m['match_time']) ?><br><small><?= e($m['match_date']) ?></small></td>
                            <td><span class="badge badge-<?= e($m['status']) ?>"><?= e($m['status']) ?></span></td>
                            <td>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Yakin hapus match ini?')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
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
