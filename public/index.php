<?php
require_once 'core/config.php';

$season = getCurrentSeason($pdo);
$seasonId = $season['id'] ?? 0;

// Get site config
$config = $pdo->query("SELECT * FROM site_config WHERE id = 1")->fetch();

// Get poster slides
$posters = $pdo->query("SELECT * FROM poster_slides WHERE is_active = 1 ORDER BY order_position, id DESC LIMIT 10")->fetchAll();

// Get sponsors
$sponsors = $pdo->query("SELECT * FROM sponsors WHERE is_active = 1 ORDER BY FIELD(tier, 'platinum', 'gold', 'silver', 'bronze')")->fetchAll();

// Get upcoming matches (next 7 days)
$upcomingMatches = $pdo->prepare("
    SELECT m.*, 
        t1.name as team1_name, t1.logo as team1_logo,
        t2.name as team2_name, t2.logo as team2_logo,
        d.name as day_name, d.match_date
    FROM matches m
    JOIN teams t1 ON m.team1_id = t1.id
    JOIN teams t2 ON m.team2_id = t2.id
    JOIN days d ON m.day_id = d.id
    WHERE m.season_id = ? AND m.status IN ('upcoming', 'live')
    AND d.match_date >= CURDATE()
    ORDER BY d.match_date, m.match_time
    LIMIT 10
");
$upcomingMatches->execute([$seasonId]);

// Get today's matches
$todayMatches = $pdo->prepare("
    SELECT m.*, 
        t1.name as team1_name, t1.logo as team1_logo,
        t2.name as team2_name, t2.logo as team2_logo,
        d.name as day_name, d.match_date
    FROM matches m
    JOIN teams t1 ON m.team1_id = t1.id
    JOIN teams t2 ON m.team2_id = t2.id
    JOIN days d ON m.day_id = d.id
    WHERE m.season_id = ? AND d.match_date = CURDATE()
    ORDER BY m.match_time
");
$todayMatches->execute([$seasonId]);

// Get point table (teams sorted by wins)
$pointTable = $pdo->prepare("
    SELECT t.*, 
        COUNT(CASE WHEN m.winner_id = t.id THEN 1 END) as wins,
        COUNT(CASE WHEN m.winner_id IS NOT NULL AND m.winner_id != t.id THEN 1 END) as losses,
        COUNT(CASE WHEN m.status = 'completed' THEN 1 END) as total_matches
    FROM teams t
    LEFT JOIN matches m ON (t.id = m.team1_id OR t.id = m.team2_id) AND m.status = 'completed'
    WHERE t.season_id = ?
    GROUP BY t.id
    ORDER BY wins DESC, losses ASC
");
$pointTable->execute([$seasonId]);

// Get all days for schedule
$allDays = $pdo->prepare("
    SELECT d.*, COUNT(m.id) as match_count
    FROM days d
    LEFT JOIN matches m ON d.id = m.day_id
    WHERE d.season_id = ?
    GROUP BY d.id
    ORDER BY d.match_date
");
$allDays->execute([$seasonId]);

$streamUrl = $config['youtube_stream_url'] ?: DEFAULT_STREAM;
$bracketOn = $config['bracket_status'] === 'ON';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config['site_name'] ?: SITE_NAME) ?> - <?= e($season['name'] ?? '') ?></title>
    <link rel="stylesheet" href="assets/css/public.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <span class="logo-icon">🏆</span>
                <span class="logo-text">MPL<span class="highlight"> ID</span></span>
            </a>
            <ul class="nav-menu">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="#schedule">Schedule</a></li>
                <li><a href="#point-table">Point Table</a></li>
                <li><a href="#bracket">Bracket</a></li>
                <li><a href="#video">Video</a></li>
            </ul>
            <div class="nav-actions">
                <a href="user/login.php" class="btn-login">Login</a>
                <a href="user/register.php" class="btn-register">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Poster Slider -->
    <section class="hero">
        <div class="poster-slider" id="posterSlider">
            <?php foreach ($posters as $i => $poster): ?>
            <div class="poster-slide <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
                <img src="<?= e($poster['image']) ?>" alt="<?= e($poster['title']) ?>">
                <?php if ($poster['link']): ?>
                <a href="<?= e($poster['link']) ?>" class="poster-link" target="_blank"></a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <?php if (empty($posters)): ?>
            <div class="poster-slide active">
                <div class="poster-placeholder">
                    <h2>🔥 <?= e($season['name'] ?: 'MPL TOURNAMENT') ?></h2>
                    <p>Season <?= e($season['id'] ?: '1') ?> - Coming Soon</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="slider-controls">
                <button class="slider-btn prev" onclick="changeSlide(-1)">❮</button>
                <div class="slider-dots">
                    <?php foreach ($posters as $i => $_): ?>
                    <span class="dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></span>
                    <?php endforeach; ?>
                </div>
                <button class="slider-btn next" onclick="changeSlide(1)">❯</button>
            </div>
        </div>
    </section>

    <!-- Today's Matches -->
    <section class="section matches-today" id="schedule">
        <div class="container">
            <h2 class="section-title">
                <span class="title-icon">⚔️</span>
                TODAY'S MATCHES
                <span class="live-badge">🔴 LIVE</span>
            </h2>

            <div class="matches-grid">
                <?php 
                $hasToday = false;
                while ($match = $todayMatches->fetch()): 
                    $hasToday = true;
                ?>
                <div class="match-card <?= e($match['status']) ?>">
                    <div class="match-header">
                        <span class="match-day"><?= e($match['day_name']) ?></span>
                        <span class="match-status <?= e($match['status']) ?>"><?= e($match['status']) ?></span>
                    </div>
                    <div class="match-teams">
                        <div class="team">
                            <img src="<?= e($match['team1_logo'] ?: 'assets/images/team-placeholder.png') ?>" alt="" class="team-logo">
                            <span class="team-name"><?= e($match['team1_name']) ?></span>
                        </div>
                        <div class="match-vs">
                            <span class="vs-text">VS</span>
                            <?php if ($match['status'] === 'completed'): ?>
                            <span class="match-score"><?= (int)$match['score_team1'] ?> - <?= (int)$match['score_team2'] ?></span>
                            <?php else: ?>
                            <span class="match-time"><?= e(date('H:i', strtotime($match['match_time']))) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="team">
                            <img src="<?= e($match['team2_logo'] ?: 'assets/images/team-placeholder.png') ?>" alt="" class="team-logo">
                            <span class="team-name"><?= e($match['team2_name']) ?></span>
                        </div>
                    </div>
                    <div class="match-footer">
                        <span class="match-date"><?= e(date('d M Y', strtotime($match['match_date']))) ?></span>
                        <?php if ($match['custom_stream_url']): ?>
                        <a href="<?= e($match['custom_stream_url']) ?>" class="btn-watch" target="_blank">▶ Watch</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>

                <?php if (!$hasToday): ?>
                <div class="no-matches">
                    <p>📅 Tidak ada pertandingan hari ini</p>
                    <p class="sub">Jadwal akan muncul di sini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Full Schedule -->
    <section class="section schedule">
        <div class="container">
            <h2 class="section-title">
                <span class="title-icon">📅</span>
                FULL SCHEDULE
            </h2>
            <div class="schedule-timeline">
                <?php while ($day = $allDays->fetch()): ?>
                <div class="timeline-item">
                    <div class="timeline-date">
                        <span class="day"><?= e(date('d', strtotime($day['match_date']))) ?></span>
                        <span class="month"><?= e(date('M', strtotime($day['match_date']))) ?></span>
                    </div>
                    <div class="timeline-content">
                        <h4><?= e($day['name']) ?></h4>
                        <span class="match-count"><?= (int)$day['match_count'] ?> matches</span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Point Table -->
    <section class="section point-table" id="point-table">
        <div class="container">
            <h2 class="section-title">
                <span class="title-icon">📊</span>
                POINT TABLE
            </h2>
            <div class="table-container">
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Team</th>
                            <th>Played</th>
                            <th>W</th>
                            <th>L</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while ($team = $pointTable->fetch()): 
                            $points = ((int)$team['wins'] * 3) + ((int)$team['losses'] * 0);
                        ?>
                        <tr class="<?= $rank <= 3 ? 'top-rank rank-' . $rank : '' ?>">
                            <td class="rank"><?= $rank ?></td>
                            <td class="team-cell">
                                <img src="<?= e($team['logo'] ?: 'assets/images/team-placeholder.png') ?>" alt="" class="team-logo-small">
                                <span><?= e($team['name']) ?></span>
                            </td>
                            <td><?= (int)$team['total_matches'] ?></td>
                            <td class="wins"><?= (int)$team['wins'] ?></td>
                            <td class="losses"><?= (int)$team['losses'] ?></td>
                            <td class="points"><?= $points ?></td>
                        </tr>
                        <?php $rank++; endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Bracket Section -->
    <section class="section bracket" id="bracket">
        <div class="container">
            <h2 class="section-title">
                <span class="title-icon">🏆</span>
                TOURNAMENT BRACKET
            </h2>

            <?php if ($bracketOn): ?>
            <div class="bracket-container">
                <div class="bracket-placeholder">
                    <p>Bracket akan ditampilkan setelah group stage selesai</p>
                </div>
            </div>
            <?php else: ?>
            <div class="bracket-off">
                <div class="bracket-icon">🔒</div>
                <h3>Bracket Coming Soon</h3>
                <p>Bracket akan diaktifkan setelah group stage selesai</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Video Stream Section -->
    <section class="section video-stream" id="video">
        <div class="container">
            <h2 class="section-title">
                <span class="title-icon">▶️</span>
                LIVE STREAM
            </h2>
            <div class="stream-container">
                <div class="stream-wrapper">
                    <iframe 
                        src="https://www.youtube.com/embed/<?= e(getYoutubeId($streamUrl)) ?>?autoplay=0&rel=0" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="stream-info">
                    <h3>🔴 Live Now</h3>
                    <p><?= e($config['site_name'] ?: SITE_NAME) ?> - <?= e($season['name'] ?: '') ?></p>
                    <a href="<?= e($streamUrl) ?>" class="btn-watch-full" target="_blank">
                        <span>▶</span> Watch on YouTube
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Sponsors -->
    <section class="section sponsors">
        <div class="container">
            <h2 class="section-title">
                <span class="title-icon">🤝</span>
                OFFICIAL PARTNERS
            </h2>
            <div class="sponsors-grid">
                <?php foreach ($sponsors as $sponsor): ?>
                <div class="sponsor-card tier-<?= e($sponsor['tier']) ?>">
                    <img src="<?= e($sponsor['logo'] ?: 'assets/images/sponsor-placeholder.png') ?>" alt="<?= e($sponsor['name']) ?>">
                    <span class="sponsor-name"><?= e($sponsor['name']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3>🏆 MPL TOURNAMENT</h3>
                    <p>Turnamen Mobile Legends Professional League Indonesia</p>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <a href="index.php">Home</a>
                    <a href="#schedule">Schedule</a>
                    <a href="#point-table">Point Table</a>
                    <a href="admin/login.php">Admin Panel</a>
                </div>
                <div class="footer-social">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <?php if ($config['youtube_stream_url']): ?>
                        <a href="<?= e($config['youtube_stream_url']) ?>" target="_blank">📺 YouTube</a>
                        <?php endif; ?>
                        <?php if ($config['tiktok_url']): ?>
                        <a href="<?= e($config['tiktok_url']) ?>" target="_blank">🎵 TikTok</a>
                        <?php endif; ?>
                        <?php if ($config['instagram_url']): ?>
                        <a href="<?= e($config['instagram_url']) ?>" target="_blank">📸 Instagram</a>
                        <?php endif; ?>
                        <?php if ($config['facebook_url']): ?>
                        <a href="<?= e($config['facebook_url']) ?>" target="_blank">📘 Facebook</a>
                        <?php endif; ?>
                        <?php if ($config['discord_url']): ?>
                        <a href="<?= e($config['discord_url']) ?>" target="_blank">💬 Discord</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2026 <?= e($config['site_name'] ?: SITE_NAME) ?>. All rights reserved.</p>
                <p>Powered by 🔥 MPL Tournament System v2.0</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/public.js"></script>
</body>
</html>

<?php
function getYoutubeId($url) {
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\s]{11})/', $url, $match);
    return $match[1] ?? '';
}
?>
