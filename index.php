<?php
/**
 * MPL Tournament App - Entry Point
 * Redirects to public frontend
 */

require_once 'core/config.php';

// Check maintenance mode
$config = $pdo->query("SELECT maintenance_mode FROM site_config WHERE id = 1")->fetch();
if ($config && $config['maintenance_mode']) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Maintenance - MPL Tournament</title>
        <style>
            body { background: #0a0a0f; color: #e0e0e0; font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; text-align: center; }
            h1 { font-size: 48px; color: #FFD404; }
            p { color: #888; }
        </style>
    </head>
    <body>
        <div>
            <h1>🔧 MAINTENANCE</h1>
            <p>Sistem sedang dalam perbaikan. Silakan kembali beberapa saat lagi.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Redirect to public
header('Location: public/index.php');
exit();
