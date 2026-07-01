-- MPL Tournament Database Schema
-- Security Hardened | Complete Feature Set
-- Created: 2026-07-01

CREATE DATABASE IF NOT EXISTS mpl_tournament 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE mpl_tournament;

-- ============================================
-- CORE TABLES
-- ============================================

-- Admin Table
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('superadmin', 'admin') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME,
    login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seasons Table
CREATE TABLE seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    max_teams INT DEFAULT 16,
    status ENUM('upcoming', 'active', 'completed', 'cancelled') DEFAULT 'upcoming',
    voting_enabled TINYINT(1) DEFAULT 1,
    minigame_enabled TINYINT(1) DEFAULT 1,
    gacha_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_active (status, start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site Config Table
CREATE TABLE site_config (
    id INT PRIMARY KEY DEFAULT 1,
    season_id INT,
    site_name VARCHAR(100) DEFAULT 'MPL TOURNAMENT',
    tagline VARCHAR(255),
    youtube_stream_url VARCHAR(500),
    tiktok_url VARCHAR(255),
    instagram_url VARCHAR(255),
    facebook_url VARCHAR(255),
    discord_url VARCHAR(255),
    bracket_status ENUM('OFF', 'ON') DEFAULT 'OFF',
    maintenance_mode TINYINT(1) DEFAULT 0,
    vote_cost INT DEFAULT 100, -- Points per token vote
    daily_lives INT DEFAULT 3, -- Minigame lives per day
    gacha_cost INT DEFAULT 500, -- Points per gacha pull
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TOURNAMENT TABLES
-- ============================================

-- Teams Table
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    description TEXT,
    status ENUM('active', 'inactive', 'eliminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_season (season_id, slug),
    INDEX idx_season (season_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Players Table
CREATE TABLE players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    nickname VARCHAR(50),
    role ENUM('HEAD COACH', 'ANALYST', 'ROAMER', 'MIDLANE', 'EXP LANE', 'GOLD LANE', 'JUNGLER', 'SUBSTITUTE') NOT NULL,
    photo VARCHAR(255),
    jersey_number INT,
    status ENUM('active', 'inactive', 'benched') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_team (team_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Days Table (Match Days)
CREATE TABLE days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    day_number INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    match_date DATE NOT NULL,
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    INDEX idx_season_date (season_id, match_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matches Table
CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    day_id INT NOT NULL,
    match_number INT NOT NULL,
    team1_id INT NOT NULL,
    team2_id INT NOT NULL,
    match_time TIME NOT NULL,
    score_team1 INT DEFAULT 0,
    score_team2 INT DEFAULT 0,
    winner_id INT,
    status ENUM('upcoming', 'live', 'completed', 'cancelled') DEFAULT 'upcoming',
    stream_url VARCHAR(500),
    custom_stream_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE,
    FOREIGN KEY (team1_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (team2_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_id) REFERENCES teams(id) ON DELETE SET NULL,
    INDEX idx_day (day_id),
    INDEX idx_status (status),
    INDEX idx_season (season_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- POSTER & MEDIA TABLES
-- ============================================

-- Poster Slides Table
CREATE TABLE poster_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT,
    title VARCHAR(255),
    image VARCHAR(255) NOT NULL,
    link VARCHAR(500),
    order_position INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL,
    INDEX idx_active (is_active, order_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sponsors Table
CREATE TABLE sponsors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT,
    name VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    website VARCHAR(255),
    tier ENUM('platinum', 'gold', 'silver', 'bronze') DEFAULT 'bronze',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER TABLES
-- ============================================

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    display_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    border_id INT,
    tag_id INT,
    total_votes INT DEFAULT 0,
    total_points INT DEFAULT 0, -- Minigame points
    token_votes INT DEFAULT 0, -- Purchased vote tokens
    status ENUM('active', 'banned', 'inactive') DEFAULT 'active',
    last_login DATETIME,
    login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_points (total_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Sessions Table (for tracking)
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    is_valid TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VOTING TABLES
-- ============================================

-- Votes Table
CREATE TABLE votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    user_id INT NOT NULL,
    team_id INT NOT NULL, -- Which team they voted for
    vote_type ENUM('free', 'token') DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_match (user_id, match_id, vote_type),
    INDEX idx_match (match_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vote Stats (cached for performance)
CREATE TABLE vote_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    team1_votes INT DEFAULT 0,
    team2_votes INT DEFAULT 0,
    total_votes INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    UNIQUE KEY unique_match (match_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MINIGAME TABLES
-- ============================================

-- Minigame Config
CREATE TABLE minigames (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    max_lives_per_day INT DEFAULT 3,
    points_per_win INT DEFAULT 50,
    points_per_loss INT DEFAULT 10,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Minigame Scores
CREATE TABLE minigame_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    minigame_id INT NOT NULL,
    score INT DEFAULT 0,
    lives_used INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (minigame_id) REFERENCES minigames(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, played_at),
    INDEX idx_minigame (minigame_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily Lives Tracking
CREATE TABLE daily_lives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_date DATE NOT NULL,
    lives_remaining INT DEFAULT 3,
    lives_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, game_date),
    INDEX idx_date (game_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SHOP & GACHA TABLES
-- ============================================

-- Shop Items
CREATE TABLE shop_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('border', 'tag', 'token_vote', 'other') NOT NULL,
    image VARCHAR(255),
    price_points INT NOT NULL,
    stock INT DEFAULT -1, -- -1 = unlimited
    is_limited TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Inventory
CREATE TABLE user_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_equipped TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_item (user_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gacha Drops
CREATE TABLE gacha_drops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('border', 'tag', 'points', 'token') NOT NULL,
    image VARCHAR(255),
    rarity ENUM('common', 'rare', 'epic', 'legendary', 'mythic') DEFAULT 'common',
    drop_rate DECIMAL(5,2) NOT NULL, -- Percentage (e.g., 50.00 = 50%)
    points_value INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rarity (rarity),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gacha History
CREATE TABLE gacha_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    drop_id INT NOT NULL,
    cost_points INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (drop_id) REFERENCES gacha_drops(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LEADERBOARD TABLES
-- ============================================

-- Season Leaderboards
CREATE TABLE leaderboards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    user_id INT NOT NULL,
    total_votes INT DEFAULT 0,
    total_points INT DEFAULT 0,
    rank_position INT,
    border_equipped INT,
    tag_equipped INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_season_user (season_id, user_id),
    INDEX idx_rank (season_id, rank_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- POSTER GENERATOR TABLES
-- ============================================

-- Poster Templates
CREATE TABLE poster_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT,
    name VARCHAR(100) NOT NULL,
    template_image VARCHAR(255) NOT NULL,
    overlay_config JSON, -- Position configs for dynamic elements
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated Posters
CREATE TABLE generated_posters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    template_id INT NOT NULL,
    generated_image VARCHAR(255) NOT NULL,
    share_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES poster_templates(id) ON DELETE CASCADE,
    INDEX idx_match (match_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOGGING TABLES
-- ============================================

-- Activity Logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    user_id INT,
    admin_id INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BRACKET TABLES (For Future)
-- ============================================

-- Custom Bracket (if needed)
CREATE TABLE custom_brackets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    slot_name VARCHAR(50) NOT NULL,
    round VARCHAR(20) NOT NULL,
    team_id INT,
    score INT DEFAULT 0,
    position INT NOT NULL,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    INDEX idx_season (season_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert default admin (username: admin, password: AdminMPL2026!)
-- PASSWORD: AdminMPL2026! (GANTI SETELAH LOGIN!)
INSERT INTO admin (username, password_hash, name, email, role) VALUES 
('admin', '$2b$12$TLfxTkcxsF7MWM31r3Qqk.51Og8UeWeRW4JJhmObxW3DtkCqryXEm', 'Super Admin', 'admin@mpl.com', 'superadmin');

-- Insert default season
INSERT INTO seasons (name, description, max_teams, status) VALUES 
('Season 1', 'MPL Tournament Season Pertama', 16, 'active');

-- Insert default site config
INSERT INTO site_config (id, season_id, site_name, youtube_stream_url) VALUES 
(1, 1, 'MPL TOURNAMENT', 'https://www.youtube.com/@Puckryy');

-- Insert default minigames
INSERT INTO minigames (name, slug, description, max_lives_per_day, points_per_win, points_per_loss) VALUES 
('Tebak Angka', 'tebak-angka', 'Tebak angka 1-100 dalam 5 kali percobaan', 3, 50, 10),
('Clicker Rush', 'clicker-rush', 'Klik sebanyak mungkin dalam 10 detik', 3, 50, 10),
('Memory Match', 'memory-match', 'Cocokkan kartu yang sama', 3, 50, 10);

-- Insert default gacha drops
INSERT INTO gacha_drops (name, type, rarity, drop_rate, points_value) VALUES
('Points 100', 'points', 'common', 40.00, 100),
('Points 250', 'points', 'common', 25.00, 250),
('Points 500', 'points', 'rare', 15.00, 500),
('Token Vote x1', 'token', 'rare', 10.00, 0),
('Token Vote x3', 'token', 'epic', 5.00, 0),
('Border Bronze', 'border', 'epic', 3.00, 0),
('Border Silver', 'border', 'legendary', 1.50, 0),
('Border Gold', 'border', 'mythic', 0.50, 0);

-- Insert default shop items
INSERT INTO shop_items (name, description, type, price_points, stock) VALUES
('Token Vote', '1x Token Vote untuk voting tambahan', 'token_vote', 100, -1),
('Border Basic', 'Border profile dasar', 'border', 1000, -1),
('Tag Member', 'Tag eksklusif member', 'tag', 2000, -1);
