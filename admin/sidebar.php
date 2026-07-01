<?php
/**
 * Admin Sidebar - Shared Component
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>🔥 MPL ADMIN</h2>
        <p>Control Panel v2.0</p>
    </div>
    <ul class="nav-menu">
        <li><a href="index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>"><span class="icon">📊</span> Dashboard</a></li>
        <li><a href="seasons.php" class="<?= $currentPage === 'seasons' ? 'active' : '' ?>"><span class="icon">🏆</span> Seasons</a></li>
        <li><a href="teams.php" class="<?= $currentPage === 'teams' ? 'active' : '' ?>"><span class="icon">👥</span> Teams</a></li>
        <li><a href="players.php" class="<?= $currentPage === 'players' ? 'active' : '' ?>"><span class="icon">🎮</span> Players</a></li>
        <li><a href="matches.php" class="<?= $currentPage === 'matches' ? 'active' : '' ?>"><span class="icon">⚔️</span> Matches</a></li>
        <li><a href="days.php" class="<?= $currentPage === 'days' ? 'active' : '' ?>"><span class="icon">📅</span> Days</a></li>
        <li><a href="posters.php" class="<?= $currentPage === 'posters' ? 'active' : '' ?>"><span class="icon">🖼️</span> Posters</a></li>
        <li><a href="sponsors.php" class="<?= $currentPage === 'sponsors' ? 'active' : '' ?>"><span class="icon">🤝</span> Sponsors</a></li>
        <li><a href="users.php" class="<?= $currentPage === 'users' ? 'active' : '' ?>"><span class="icon">👤</span> Users</a></li>
        <li><a href="voting.php" class="<?= $currentPage === 'voting' ? 'active' : '' ?>"><span class="icon">🗳️</span> Voting Stats</a></li>
        <li><a href="shop.php" class="<?= $currentPage === 'shop' ? 'active' : '' ?>"><span class="icon">🛒</span> Shop & Gacha</a></li>
        <li><a href="settings.php" class="<?= $currentPage === 'settings' ? 'active' : '' ?>"><span class="icon">⚙️</span> Settings</a></li>
    </ul>
</aside>
