<?php
/**
 * Emergency Password Reset for Admin
 * Delete this file after use!
 */
require_once '../core/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || strlen($new_password) < 8) {
        $error = "Password minimal 8 karakter!";
    } elseif ($new_password !== $confirm) {
        $error = "Password tidak cocok!";
    } else {
        $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("UPDATE admin SET password_hash = ? WHERE username = 'admin'");
        $stmt->execute([$hash]);

        $success = "Password berhasil diubah! Silakan login dengan password baru.";
        logActivity($pdo, 'password_reset', "Admin password direset via emergency script", 1);
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Reset Password Admin</title></head>
<body style="font-family:Arial;max-width:400px;margin:50px auto;padding:20px;">
    <h2>🔐 Emergency Password Reset</h2>
    <p style="color:red;font-size:12px;">⚠️ Hapus file ini setelah digunakan!</p>

    <?php if(isset($error)): ?><p style="color:red;"><?= e($error) ?></p><?php endif; ?>
    <?php if(isset($success)): ?>
        <p style="color:green;"><?= e($success) ?></p>
        <p><a href="login.php">Login sekarang</a></p>
    <?php else: ?>
    <form method="POST">
        <p><input type="password" name="new_password" placeholder="Password Baru" required style="width:100%;padding:10px;margin:5px 0;"></p>
        <p><input type="password" name="confirm_password" placeholder="Konfirmasi Password" required style="width:100%;padding:10px;margin:5px 0;"></p>
        <p><button type="submit" style="width:100%;padding:12px;background:#FFD404;border:none;cursor:pointer;font-weight:bold;">UBAH PASSWORD</button></p>
    </form>
    <?php endif; ?>
</body>
</html>
