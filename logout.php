<?php
/**
 * Çıkış (logout.php)
 *
 * Amaç:
 * - Kullanıcı oturumunu güvenli şekilde sonlandırmak
 * - Admin çıkışlarını denetim (audit) amaçlı kaydetmek
 *
 * Not: Davranış değişmedi, yalnızca açıklama eklendi.
 */

// Oturumu başlat
session_start();

// Audit log için kullanıcı bilgilerini al (varsa)
$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
$email = $_SESSION['email'] ?? null;

// Admin çıkışları için audit log kaydı (isteğe bağlı, hata çıkışı engellemez)
if ($userId && $role === 'admin') {
    require_once 'config.php';
    require_once 'classes/AdminManager.php';
    
    try {
        $adminManager = new AdminManager($conn, $userId);
        // Çıkış olayını detaylarıyla logla
        $adminManager->logAuditAction('logout', [
            'user_id' => $userId,
            'email' => $email,
            'logout_time' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Audit log hatası çıkışı engellemez; sadece hata günlüğüne yazılır
        error_log('Logout audit log failed: ' . $e->getMessage());
    }
}

// Tüm oturum verilerini temizle (sunucu tarafı)
$_SESSION = [];

// Oturum çerezini sil (istemci tarafı) — varsa ve çerez kullanımı açıksa
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Oturumu tamamen sonlandır
session_destroy();

// Giriş sayfasına yönlendir
header('Location: index.php');
exit;
?>
