<?php 
include 'core/config.php'; 
date_default_timezone_set('Asia/Jakarta');

$tanggal_hari_ini = date('Y-m-d');

// ENGINE SINKRONISASI HARI INI
$cek_day_stmt = $pdo->prepare("SELECT * FROM day WHERE tanggal = ? LIMIT 1");
$cek_day_stmt->execute([$tanggal_hari_ini]);
$current_day_data = $cek_day_stmt->fetch();

if (!$current_day_data) {
    $default_day_stmt = $pdo->query("SELECT * FROM day ORDER BY tanggal ASC LIMIT 1");
    $current_day_data = $default_day_stmt->fetch();
}
$default_day_id = $current_day_data['id'] ?? 0;

// FIX: Array bulan mapping benar sesuai index 1-12
$bulan_indo = array("", "JANUARI", "FEBRUARI", "MARET", "APRIL", "MEI", "JUNI", "JULI", "AGUSTUS", "SEPTEMBER", "OKTOBER", "NOVEMBER", "DESEMBER");
$bulan_indo_singkat = array("", "JAN", "FEB", "MAR", "APR", "MEI", "JUN", "JUL", "AGU", "SEP", "OKT", "NOV", "DES");

$page = $_GET['page'] ?? 'home'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Situs Resmi Tournament Pro Engine</title>
    <link rel="stylesheet" href="assets/css/style.css?v=21.9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* SUNTIKAN REVOLUSI WARNA NAVBAR (ANTI TEKS HILANG) */
        
        /* 1. Top Medsos Bar ganti jadi Kuning Emas */
        .top-social-bar {
            background-color: #FFD404 !important;
        }
        .top-social-bar .medsos-item a i {
            color: #111111 !important; /* Icon medsos jadi hitam biar kontras */
        }
        .top-social-bar .sekat-garis {
            background-color: rgba(0, 0, 0, 0.2) !important;
        }

        /* 2. Main Nav Bar ganti jadi Abu-Abu Gelap #383838 */
        .main-nav-bar {
            background-color: #383838 !important;
            border-bottom: 2px solid #FFD404;
        }
        .nav-logo {
            color: #FFD404 !important; /* Logo turnamen lu menyala kuning */
            font-weight: 900;
        }

        /* 3. Pengaturan Teks & Garis Vertikal */
        .nav-menu li a {
            position: relative;
            display: flex;
            align-items: center;
            padding-left: 18px !important;
            padding-right: 14px !important;
            color: #FFFFFF !important; /* Default teks warna PUTIH bersih */
            transition: color 0.2s ease !important;
            font-weight: 600;
        }

        /* Struktur Puzzle Garis Vertikal */
        .nav-menu li a::before {
            content: '';
            position: absolute;
            left: 0;
            width: 4px; /* Tebal garis vertikal */
            height: 70%; /* Tinggi garis proposional di sebelah teks */
            background-color: #FFD404 !important;
            transform: scaleY(0);
            transform-origin: center;
            opacity: 0;
            transition: transform 0.25s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.25s ease !important;
        }

        /* State Aktif: Teks menguning dan Garis Vertikal memanjang tegak */
        .nav-menu li a.v-line-active {
            color: #FFD404 !important;
            font-weight: 800 !important;
        }
        .nav-menu li a.v-line-active::before {
            transform: scaleY(1) !important;
            opacity: 1 !important;
            box-shadow: 0 0 8px rgba(255, 212, 4, 0.5) !important;
        }

        .disable-smooth-scroll {
            scroll-behavior: auto !important;
        }
    </style>
</head>
<body>

    <div class="navbar-wrapper">
        <div class="top-social-bar">
            <div class="medsos-item"><a href="#"><i class="fab fa-facebook-f"></i></a></div>
            <div class="sekat-garis"></div>
            <div class="medsos-item"><a href="#"><i class="fab fa-youtube"></i></a></div>
            <div class="sekat-garis"></div>
            <div class="medsos-item"><a href="#"><i class="fab fa-tiktok"></i></a></div>
            <div class="sekat-garis"></div>
            <div class="medsos-item"><a href="#"><i class="fab fa-instagram"></i></a></div>
            <div class="sekat-garis"></div>
            <div class="medsos-item"><a href="#"><i class="fab fa-discord"></i></a></div>
        </div>
        <nav class="main-nav-bar">
            <div class="nav-container-inside">
                <div class="nav-logo">MPLTOURNAMENT</div>
                <div class="nav-menu-wrapper">
                    <ul class="nav-menu" id="mainNavbarMenu">
                        <li><a href="index.php?page=home" id="navHomeLink">HOME</a></li>
                        <li><a href="index.php?page=home" id="navJadwalLink">JADWAL</a></li>
                        <li><a href="index.php?page=tim" id="navTimLink">TIM</a></li>
                        <li><a href="index.php?page=berita" id="navBeritaLink">BERITA</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <div id="scroll-top-anchor"></div>

    <?php if ($page === 'tim'): ?>
        <div class="container">
            <?php if(isset($_GET['tim_id'])): 
                $t_id = intval($_GET['tim_id']);
                $st = $pdo->prepare("SELECT * FROM tim WHERE id = ?"); $st->execute([$t_id]); $tim_info = $st->fetch();
            ?>
                <div class="roster-header-section">
                    <img src="assets/uploads/<?php echo htmlspecialchars($tim_info['logo_tim'] ?? 'default_logo.png', ENT_QUOTES, 'UTF-8'); ?>" style="width:70px; height:70px; object-fit:contain;"><br>
                    <h2 style="margin:5px 0 0 0; text-transform:uppercase; font-size:2.2rem; font-weight:800; color:#111;"><?php echo htmlspecialchars(($tim_info['nama_tim'] ?? 'TIM').' UNITED', ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p style="letter-spacing:2px; font-weight:bold; color:#777; margin:5px 0 0 0;">ROSTER SEASON 17</p>
                </div>
                
                <div class="roster-grid">
                    <?php
                    $sp = $pdo->prepare("SELECT * FROM player WHERE tim_id = ? ORDER BY id ASC"); $sp->execute([$t_id]);
                    while($p = $sp->fetch()){
                        $foto = !empty($p['foto_player']) ? $p['foto_player'] : 'default_avatar.png';
                        ?>
                        <div class="player-card-node">
                            <div class="player-photo-wrapper"><img src="assets/uploads/<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>"></div>
                            <div class="player-badge-name"><?php echo htmlspecialchars($p['nama_player'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="player-role-label"><?php echo htmlspecialchars($p['role_player'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    <?php } ?>
                </div>
                <p style="text-align:center; margin-top:40px;"><a href="index.php?page=tim" style="color:#111; font-weight:bold; text-decoration:none;"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Liga</a></p>
            <?php else: ?>
                <div style="text-align:center; margin-bottom:40px;"><h2 style="font-size:2rem; font-weight:800; text-transform:uppercase; color:#111; margin:0;">TIM PESERTA</h2></div>
                <div class="teams-flag-grid">
                    <?php $st = $pdo->query("SELECT * FROM tim ORDER BY nama_tim ASC"); while($t = $st->fetch()){ ?>
                        <div class="team-flag-card" onclick="window.location='index.php?page=tim&tim_id=<?php echo intval($t['id']); ?>'">
                            <div class="flag-header-bar"><?php echo htmlspecialchars($t['nama_tim'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <img src="assets/uploads/<?php echo htmlspecialchars($t['logo_tim'] ?? 'default_logo.png', ENT_QUOTES, 'UTF-8'); ?>" class="flag-logo-img">
                        </div>
                    <?php } ?>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div style="max-width: var(--max-width-global); margin: 20px auto 40px auto; overflow:hidden; border-radius:14px; position:relative; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background:#fff;">
            <div id="posterContainer" style="display:flex; transition:transform 0.5s ease-in-out; width:100%;">
                <?php
                $sp = $pdo->query("SELECT * FROM poster ORDER BY id ASC");
                if($sp->rowCount() == 0) {
                    echo "<div style='flex:0 0 100%; width:100%;'><img src='assets/images/default_poster.png' style='width:100%; display:block;'></div>";
                }
                while($p = $sp->fetch()){
                    echo "<div class='poster-slide' style='flex:0 0 100%; width:100%;'><img src='assets/uploads/".htmlspecialchars($p['gambar_poster'], ENT_QUOTES, 'UTF-8')."' style='width:100%; display:block;'></div>";
                }
                ?>
            </div>
            <button class="poster-nav-arrow poster-arrow-left" id="posterLeft" style="position:absolute; left:20px; top:50%; transform:translateY(-50%); background:rgba(0,0,0,0.6); color:white; border:none; width:40px; height:40px; border-radius:50%; cursor:pointer;"><i class="fas fa-chevron-left"></i></button>
            <button class="poster-nav-arrow poster-arrow-right" id="posterRight" style="position:absolute; right:20px; top:50%; transform:translateY(-50%); background:rgba(0,0,0,0.6); color:white; border:none; width:40px; height:40px; border-radius:50%; cursor:pointer;"><i class="fas fa-chevron-right"></i></button>
        </div>

        <?php
        $sc = $pdo->query("SELECT status_bracket, link_youtube FROM site_config WHERE id = 1"); 
        $cf = $sc->fetch(); 
        $status_bracket = $cf['status_bracket'] ?? 'OFF';
        $global_video_url = !empty($cf['link_youtube']) ? $cf['link_youtube'] : 'https://www.youtube.com/watch?v=3wjjF4kp2IY';
        
        // FIX: Regex typo double pipe || diperbaiki jadi |
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|user/.+/)|youtu\.be/)([^"&?/\s]{11})%i', $global_video_url, $match_id);
        $extracted_video_id = $match_id[1] ?? '3wjjF4kp2IY';
        ?>
        <div class="container" style="margin-bottom:40px;">
            <div class="playoffs-switch-container" id="switchContainerBox">
                <button class="switch-playoffs-btn active" id="btnShowPoin" onclick="switchPlayoffsView('poin', this)"><i class="fas fa-trophy"></i> Poin Kemenangan Terbanyak</button>
                <?php if ($status_bracket === 'ON'): ?>
                    <button class="switch-playoffs-btn" id="btnShowBracket" onclick="switchPlayoffsView('bracket', this)"><i class="fas fa-sitemap"></i> Playoffs Bracket</button>
                <?php endif; ?>
                <div class="switch-indicator-line" id="magicSwitchLine"></div>
            </div>

            <div id="playoffsPoinView">
                <div class="section-title-wrap">
                    <div class="yellow-bar"></div>
                    <div class="dynamic-title">POIN KEMENANGAN TERTINGGI TIM</div>
                </div>
                <div class="poin-table-wrapper">
                    <table class="poin-esports-table">
                        <thead>
                            <tr>
                                <th style="width: 100px; text-align:center;">RANK</th>
                                <th>TIM</th>
                                <th style="text-align: center; width: 150px;">WIN</th>
                                <th style="text-align: center; width: 150px;">LOSE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // FIX: Leaderboard jadi 1 query SQL efisien, hasil sama persis
                            $leaderboard = $pdo->query("
                                SELECT 
                                    t.id,
                                    t.nama_tim,
                                    t.logo_tim,
                                    t.status_tren,
                                    COALESCE(SUM(CASE WHEN j.tim_1_id = t.id THEN j.score_tim_1 ELSE j.score_tim_2 END), 0) as match_won,
                                    COALESCE(SUM(CASE WHEN j.tim_1_id = t.id THEN j.score_tim_2 ELSE j.score_tim_1 END), 0) as match_lost
                                FROM tim t
                                LEFT JOIN jadwal j ON (t.id = j.tim_1_id OR t.id = j.tim_2_id) AND j.status = 'selesai'
                                GROUP BY t.id
                                ORDER BY match_won DESC
                            ")->fetchAll();
                            
                            $rank = 1;
                            foreach ($leaderboard as $data) {
                                $icon_indicator = '<span class="rank-indicator indicator-equal">=</span>';
                                if ($data['status_tren'] === 'NAIK') { $icon_indicator = '<span class="rank-indicator indicator-up">▲</span>'; } 
                                else if ($data['status_tren'] === 'TURUN') { $icon_indicator = '<span class="rank-indicator indicator-down">▼</span>'; }
                                ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <div class="rank-container">
                                            <?php echo $icon_indicator; ?>
                                            <span class="rank-number">#<?php echo $rank++; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <img src="assets/uploads/<?php echo htmlspecialchars($data['logo_tim'] ?? 'default_logo.png', ENT_QUOTES, 'UTF-8'); ?>" class="leaderboard-logo-img">
                                            <span><?php echo htmlspecialchars($data['nama_tim'], ENT_QUOTES, 'UTF-8'); ?> UNITED</span>
                                        </div>
                                    </td>
                                    <td style="text-align: center; font-size:16px; color:#111; font-weight:bold;"><?php echo intval($data['match_won']); ?></td>
                                    <td style="text-align: center; font-size:16px; color:#ff4d4d; font-weight:bold;"><?php echo intval($data['match_lost']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($status_bracket === 'ON'): ?>
                <div id="playoffsBracketView" style="display: none;">
                    <div class="section-title-wrap">
                        <div class="yellow-bar"></div>
                        <div class="dynamic-title">PLAYOFFS BRACKET STANDINGS</div>
                    </div>
                    <div style="text-align:center; padding:30px; background:#f9f9f9; border-radius:12px; border:1px dashed #ccc; color:#777; font-weight:bold;">
                        Bagan Bracket Sedang Dalam Pembaruan.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div id="target-jadwal-scroll" style="scroll-margin-top: 170px; padding-top: 20px;">
            <div class="day-navigation-container">
                <button class="arrow-btn arrow-left" id="slideLeft"><i class="fas fa-chevron-left"></i></button>
                <div class="day-swiper-outer" id="swiperOuter">
                    <div class="day-swiper-wrapper" id="daySwiper">
                        <?php
                        $stmtDay = $pdo->query("SELECT * FROM day ORDER BY tanggal ASC");
                        $idx = 0; $act_idx = 0;
                        while($d = $stmtDay->fetch()) {
                            $activeClass = ($d['id'] == $default_day_id) ? 'active' : '';
                            if($d['id'] == $default_day_id) { $act_idx = $idx; }
                            $is_hari_ini_class = ($d['tanggal'] == $tanggal_hari_ini) ? 'is-hari-ini' : '';
                            $m_num = intval(date('m', strtotime($d['tanggal'])));
                            $d_num = date('d', strtotime($d['tanggal']));
                            ?>
                            <div class="day-item <?php echo $activeClass.' '.$is_hari_ini_class; ?>" data-index="<?php echo $idx; ?>" data-id="<?php echo intval($d['id']); ?>" data-tanggal="<?php echo htmlspecialchars($d_num.' '.$bulan_indo[$m_num].' '.date('Y',strtotime($d['tanggal'])), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="day-title"><?php echo htmlspecialchars($d['nama_day'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="day-date"><?php echo htmlspecialchars($d_num.' '.$bulan_indo_singkat[$m_num], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="penanda-garis"></div>
                            </div>
                            <?php $idx++; } ?>
                    </div>
                </div>
                <button class="arrow-btn arrow-right" id="slideRight"><i class="fas fa-chevron-right"></i></button>
            </div>

            <div class="container">
                <div class="section-title-wrap">
                    <div class="yellow-bar"></div>
                    <div class="dynamic-title" id="mainTimelineHeader">
                        JADWAL PERTANDINGAN HARI INI - <?php 
                        if (isset($current_day_data['tanggal'])) {
                            $m_num = intval(date('m', strtotime($current_day_data['tanggal'])));
                            echo htmlspecialchars(date('d', strtotime($current_day_data['tanggal'])) . ' ' . $bulan_indo[$m_num] . ' ' . date('Y', strtotime($current_day_data['tanggal'])), ENT_QUOTES, 'UTF-8');
                        } else { echo 'BELUM ADA DAY'; }
                        ?>
                    </div>
                </div>

                <div id="matchTimelineLoader">
                    <?php
                    $queryMatch = "SELECT j.*, t1.nama_tim as t1_nama, t1.logo_tim as t1_logo, t2.nama_tim as t2_nama, t2.logo_tim as t2_logo FROM jadwal j JOIN tim t1 ON j.tim_1_id = t1.id JOIN tim t2 ON j.tim_2_id = t2.id ORDER BY j.waktu_tanding ASC";
                    $stmtMatch = $pdo->query($queryMatch);
                    while($m = $stmtMatch->fetch()) {
                        $displayStyle = ($m['day_id'] == $default_day_id) ? '' : 'display:none;';
                        $fallback_url = (!empty($cf['link_youtube'])) ? $cf['link_youtube'] : 'https://www.youtube.com/@Pukryy';
                        $target_link = !empty($m['link_live']) ? $m['link_live'] : $fallback_url;
                        ?>
                        <div class="match-card single-match-node" data-day-group="<?php echo intval($m['day_id']); ?>" data-waktu="<?php echo date('Y-m-d\TH:i:s', strtotime($m['waktu_tanding'])); ?>" data-db-status="<?php echo htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo $displayStyle; ?>">
                            <div class="match-main-row">
                                <div class="match-info-left">MATCH <?php echo intval($m['nomor_match']); ?> - <?php echo date('H:i', strtotime($m['waktu_tanding'])); ?> WIB</div>
                                <div class="teams-vs-container">
                                    <div class="team-block t1"><span class="team-name-text"><?php echo htmlspecialchars($m['t1_nama'], ENT_QUOTES, 'UTF-8'); ?></span><img src="assets/uploads/<?php echo htmlspecialchars($m['t1_logo'] ?? 'default_logo.png', ENT_QUOTES, 'UTF-8'); ?>" class="team-logo"></div>
                                    <div class="vs-divider">
                                        VS
                                        <div class="vs-arrow-container">
                                            <i class="fas fa-chevron-down extend-arrow-icon"></i>
                                        </div>
                                    </div>
                                    <div class="team-block t2"><img src="assets/uploads/<?php echo htmlspecialchars($m['t2_logo'] ?? 'default_logo.png', ENT_QUOTES, 'UTF-8'); ?>" class="team-logo"><span class="team-name-text"><?php echo htmlspecialchars($m['t2_nama'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                                </div>
                                <div style="display:flex; align-items:center;">
                                    <a href="<?php echo htmlspecialchars($target_link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="status-indicator-badge upcoming-mode" onclick="event.stopPropagation();">
                                        <div class="dot-indicator"></div>
                                        <span class="badge-text-string">UPCOMING</span>
                                    </a>
                                </div>
                            </div>
                            <div class="match-extend-details">
                                <div class="score-center-board">
                                    <div class="score-title class-dynamic-score-title">SKOR SEMENTARA</div>
                                    <div class="score-numbers"><?php echo intval($m['score_tim_1']) . " : " . intval($m['score_tim_2']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="video-stream-section" id="esportsVideoSectionBox">
            <div class="video-ratio-wrapper">
                <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($extracted_video_id, ENT_QUOTES, 'UTF-8'); ?>?autoplay=1&mute=1&rel=0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>

        <div class="container" style="margin-top: 30px; padding-bottom: 120px; text-align: center;">
            <div style="display: inline-block; text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 1.4rem; font-weight: 800; color: #111111; text-transform: uppercase; letter-spacing: 1px; margin:0;">OFFICIAL TOURNAMENT PARTNERS</h2>
                <div style="width: 50px; height: 3px; background: var(--primary); margin: 8px auto 0 auto;"></div>
            </div>
            <div style="display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:50px; padding:20px 0;">
                <?php
                $ssp = $pdo->query("SELECT * FROM sponsor ORDER BY id ASC");
                if($ssp->rowCount() == 0) { echo "<p style='color:#bbb; font-style:italic; font-size:13px;'>Official sponsor slot available.</p>"; }
                while($spnsr = $ssp->fetch()){
                    ?>
                    <div style="transition:transform 0.2s; cursor:pointer;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="assets/uploads/<?php echo htmlspecialchars($spnsr['logo_sponsor'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($spnsr['nama_sponsor'], ENT_QUOTES, 'UTF-8'); ?>" style="max-height:50px; max-width:140px; object-fit:contain;">
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php endif; ?>

    <script>
        const currentPage = "<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>";
        let isScrollingSpyActive = true; 

        // DETEKTOR AKTIFASI LINK NAV (FORCE HIGHLIGHT & TRANSISI VERTIKAL)
        function ubahWarnaNavAktifMurni(targetId) {
            const semuaTombol = document.querySelectorAll('#mainNavbarMenu li a');
            semuaTombol.forEach(tombol => {
                tombol.classList.remove('v-line-active');
            });

            const tombolAktif = document.getElementById(targetId);
            if (tombolAktif) {
                tombolAktif.classList.add('v-line-active');
            }
        }

        const magicSwitchLine = document.getElementById('magicSwitchLine');
        function alignMagicSwitchLine() {
            const activeSwitch = document.querySelector('.switch-playoffs-btn.active');
            if(activeSwitch && magicSwitchLine) {
                magicSwitchLine.style.width = `${activeSwitch.offsetWidth}px`;
                magicSwitchLine.style.left = `${activeSwitch.offsetLeft}px`;
            }
        }

        function switchPlayoffsView(viewType, elementButton) {
            const bracketView = document.getElementById('playoffsBracketView');
            const poinView = document.getElementById('playoffsPoinView');
            const buttons = document.querySelectorAll('.switch-playoffs-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            elementButton.classList.add('active');
            
            if (viewType === 'bracket') {
                if(bracketView) bracketView.style.display = 'block'; 
                if(poinView) poinView.style.display = 'none';
            } else {
                if(bracketView) bracketView.style.display = 'none'; 
                if(poinView) poinView.style.display = 'block';
            }
            alignMagicSwitchLine();
        }

        const pContainer = document.getElementById('posterContainer');
        const pSlides = document.querySelectorAll('.poster-slide');
        let pIdx = 0;
        if(pContainer && pSlides.length > 1) {
            document.getElementById('posterRight').addEventListener('click', () => { pIdx = (pIdx + 1) % pSlides.length; pContainer.style.transform = `translateX(-${pIdx * 100}%)`; });
            document.getElementById('posterLeft').addEventListener('click', () => { pIdx = (pIdx - 1 + pSlides.length) % pSlides.length; pContainer.style.transform = `translateX(-${pIdx * 100}%)`; });
            setInterval(() => { pIdx = (pIdx + 1) % pSlides.length; pContainer.style.transform = `translateX(-${pIdx * 100}%)`; }, 6000);
        }

        // ENGINE MATEMATIS SMOOTH SCROLL DOWN (KEBAL GANGGUAN CSS)
        function pemicuSmoothScrollKeJadwal() {
            const targetElement = document.getElementById('target-jadwal-scroll');
            if (!targetElement) return;

            isScrollingSpyActive = false;
            const targetY = targetElement.getBoundingClientRect().top + window.pageYOffset - 140;
            const startY = window.pageYOffset;
            const distance = targetY - startY;
            const duration = 650; 
            let startTimestamp = null;

            function animationStep(timestamp) {
                if (!startTimestamp) startTimestamp = timestamp;
                const elapsed = timestamp - startTimestamp;
                const progress = Math.min(elapsed / duration, 1);
                
                const easeCubicOut = 1 - Math.pow(1 - progress, 3);
                window.scrollTo(0, startY + distance * easeCubicOut);

                if (elapsed < duration) {
                    requestAnimationFrame(animationStep);
                } else {
                    window.scrollTo(0, targetY);
                    isScrollingSpyActive = true;
                }
            }
            requestAnimationFrame(animationStep);
        }

        function initActiveState() {
            const shouldScroll = sessionStorage.getItem('shouldScrollToJadwal');

            if (currentPage === 'tim') {
                ubahWarnaNavAktifMurni('navTimLink');
            } else if (currentPage === 'berita') {
                ubahWarnaNavAktifMurni('navBeritaLink');
            } else if (currentPage === 'home' && shouldScroll !== 'true') {
                ubahWarnaNavAktifMurni('navHomeLink');
            }
        }

        // INTERCEPTOR ACTION
        document.getElementById('navJadwalLink').addEventListener('click', function(e) {
            if(currentPage === 'tim' || currentPage === 'berita') {
                e.preventDefault();
                sessionStorage.setItem('fromPageOriginated', currentPage);
                sessionStorage.setItem('shouldScrollToJadwal', 'true');
                
                ubahWarnaNavAktifMurni('navHomeLink');
                window.location.href = "index.php?page=home";
                return;
            }
            e.preventDefault(); 
            ubahWarnaNavAktifMurni('navJadwalLink');
            pemicuSmoothScrollKeJadwal();
        });

        document.getElementById('navHomeLink').addEventListener('click', function(e) {
            if(currentPage === 'home') {
                e.preventDefault();
                isScrollingSpyActive = false;
                ubahWarnaNavAktifMurni('navHomeLink');
                
                const startY = window.pageYOffset;
                const duration = 450;
                let startTimestamp = null;

                function stepTop(timestamp) {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const elapsed = timestamp - startTimestamp;
                    const progress = Math.min(elapsed / duration, 1);
                    const ease = 1 - Math.pow(1 - progress, 3);
                    window.scrollTo(0, startY * (1 - ease));
                    if (elapsed < duration) {
                        requestAnimationFrame(stepTop);
                    } else {
                        window.scrollTo(0, 0);
                        isScrollingSpyActive = true;
                    }
                }
                requestAnimationFrame(stepTop);
            }
        });

        // RADAR DETEKTOR SCROLL-SPY LIVE SINKRONISASI
        let lastScrollY = 0;
        let ticking = false;

        function spyRadarScrollMenu() {
            if (!isScrollingSpyActive || currentPage !== 'home') return;
            
            const targetJadwalSection = document.getElementById('target-jadwal-scroll');
            if (!targetJadwalSection) return;

            const posisiScrollLayar = window.scrollY + 220;
            const batasAtasJadwal = targetJadwalSection.offsetTop;
            const tinggiJadwalSection = targetJadwalSection.offsetHeight;
            
            if (posisiScrollLayar >= batasAtasJadwal && posisiScrollLayar <= (batasAtasJadwal + tinggiJadwalSection)) {
                ubahWarnaNavAktifMurni('navJadwalLink');
            } else {
                if (window.scrollY < batasAtasJadwal) {
                    ubahWarnaNavAktifMurni('navHomeLink');
                }
            }
        }

        window.addEventListener('scroll', () => {
            lastScrollY = window.scrollY;
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    spyRadarScrollMenu();
                    ticking = false;
                });
                ticking = true;
            }
        });

        const swiperOuter = document.getElementById('swiperOuter');
        const swiperWrapper = document.getElementById('daySwiper');
        const dayItems = document.querySelectorAll('.day-item');
        const mainTitle = document.getElementById('mainTimelineHeader');
        const matches = document.querySelectorAll('.single-match-node');
        
        let currentIndex = <?php echo intval($act_idx ?? 0); ?>;
        const totalItems = dayItems.length;

        function updateSliderPosition(centerElement = null) {
            if (centerElement && swiperOuter) {
                const outerWidth = swiperOuter.offsetWidth;
                const wrapperLeft = swiperWrapper.getBoundingClientRect().left;
                const elemLeft = centerElement.getBoundingClientRect().left;
                const elemWidth = centerElement.offsetWidth;
                
                const currentTransform = new WebKitCSSMatrix(window.getComputedStyle(swiperWrapper).transform).m41;
                const targetScroll = elemLeft - wrapperLeft - (outerWidth / 2) + (elemWidth / 2);
                
                let finalX = -targetScroll;
                const maxScroll = swiperWrapper.scrollWidth - outerWidth;
                
                if (finalX > 0) finalX = 0;
                if (finalX < -maxScroll) finalX = -maxScroll;
                
                swiperWrapper.style.transform = `translateX(${finalX}px)`;
            } else {
                let shift = currentIndex * 20;
                if(currentIndex > totalItems - 5) { shift = (totalItems - 5) * 20; }
                if(shift < 0) shift = 0;
                if(swiperWrapper && totalItems > 5) { swiperWrapper.style.transform = `translateX(-${shift}%)`; }
            }
        }

        if(swiperOuter) {
            document.getElementById('slideLeft').addEventListener('click', () => { if(currentIndex > 0){ currentIndex--; dayItems[currentIndex].click(); } });
            document.getElementById('slideRight').addEventListener('click', () => { if(currentIndex < totalItems - 1){ currentIndex++; dayItems[currentIndex].click(); } });

            let isDown = false; let startX; let scrollLeftShift;
            swiperOuter.addEventListener('mousedown', (e) => { isDown = true; startX = e.pageX; scrollLeftShift = currentIndex; });
            swiperOuter.addEventListener('mouseleave', () => isDown = false);
            swiperOuter.addEventListener('mouseup', () => isDown = false);
            swiperOuter.addEventListener('mousemove', (e) => {
                if(!isDown) return;
                const x = e.pageX; const walk = Math.round((startX - x) / 100);
                if(walk !== 0) {
                    let targetIdx = scrollLeftShift + walk;
                    if(targetIdx >= 0 && targetIdx <= totalItems - 1) { currentIndex = targetIdx; dayItems[currentIndex].click(); startX = e.pageX; }
                }
            });

            dayItems.forEach(item => {
                item.addEventListener('click', function() {
                    dayItems.forEach(i => i.classList.remove('active')); this.classList.add('active');
                    mainTitle.innerText = "JADWAL PERTANDINGAN HARI INI - " + this.dataset.tanggal;
                    currentIndex = parseInt(this.dataset.index); 
                    updateSliderPosition(item);
                    matches.forEach(m => { m.style.display = (m.dataset.dayGroup === this.dataset.id) ? '' : 'none'; });
                });
            });
        }

        function sinkronisasiRadarWaktuWIB() {
            const ClinicalTime = new Date().getTime();
            matches.forEach(card => {
                const timeTarget = new Date(card.dataset.waktu).getTime();
                const statusDb = card.dataset.dbStatus;
                const badge = card.querySelector('.status-indicator-badge');
                const badgeText = card.querySelector('.badge-text-string');
                const scoreT = card.querySelector('.class-dynamic-score-title');

                if (statusDb === 'selesai') {
                    badge.className = "status-indicator-badge replay-mode"; badgeText.innerText = "REPLAY";
                    if(scoreT) scoreT.innerText = "SKOR AKHIR"; return;
                }
                if (ClinicalTime >= timeTarget) {
                    badge.className = "status-indicator-badge live-mode"; badgeText.innerText = "LIVE";
                    if(scoreT) scoreT.innerText = "SKOR SEMENTARA";
                } else {
                    badge.className = "status-indicator-badge upcoming-mode"; badgeText.innerText = "UPCOMING";
                    if(scoreT) scoreT.innerText = "SKOR SEMENTARA";
                }
            });
        }

        window.addEventListener('resize', () => { alignMagicSwitchLine(); });
        
        window.addEventListener('DOMContentLoaded', () => {
            initActiveState();
            alignMagicSwitchLine();
            sinkronisasiRadarWaktuWIB();
            const initialActive = document.querySelector('.day-item.active');
            if(initialActive) { setTimeout(() => { updateSliderPosition(initialActive); }, 300); }
        });

        // LOCK ENGINE LINTAS SCROLL PASCA POSTER SELESAI DIMUAT
        window.addEventListener('load', () => {
            const shouldScroll = sessionStorage.getItem('shouldScrollToJadwal');
            
            if(currentPage === 'home' && shouldScroll === 'true') {
                isScrollingSpyActive = false;
                sessionStorage.removeItem('shouldScrollToJadwal');
                sessionStorage.removeItem('fromPageOriginated');
                
                ubahWarnaNavAktifMurni('navHomeLink');
                
                setTimeout(() => {
                    pemicuSmoothScrollKeJadwal();
                }, 250); 
            }
        });
    </script>
</body>
</html>
