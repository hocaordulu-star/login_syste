<?php
/**
 * =====================================================
 * MESAJ İŞLEMLERİ (message_actions.php)
 * Amaç: AJAX istekleri için mesaj işlemlerini yönetir
 * Özellikler:
 * - Mesaj silme (soft delete)
 * - Mesaj içeriği getirme
 * - Okundu olarak işaretleme
 * Güvenlik: Giriş kontrolü, SQL injection koruması, yetki kontrolü
 * =====================================================
 */

session_start();
require_once 'config.php';

// JSON yanıt için header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';

// Action kontrolü
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'delete_message':
        // Mesaj silme (soft delete)
        $messageId = (int)($_POST['message_id'] ?? 0);
        
        if ($messageId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz mesaj ID.']);
            exit;
        }
        
        // Yetki kontrolü: Admin tüm mesajları silebilir, diğerleri sadece aldıkları mesajları
        if ($userRole === 'admin') {
            $stmt = $conn->prepare("
                UPDATE messages 
                SET is_deleted = 1 
                WHERE id = ?
            ");
            $stmt->bind_param('i', $messageId);
        } else {
            $stmt = $conn->prepare("
                UPDATE messages 
                SET is_deleted = 1 
                WHERE id = ? AND receiver_id = ?
            ");
            $stmt->bind_param('ii', $messageId, $userId);
        }
        
        $success = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        if ($success && $affectedRows > 0) {
            echo json_encode(['success' => true, 'message' => 'Mesaj başarıyla silindi.']);
        } elseif ($affectedRows === 0) {
            echo json_encode(['success' => false, 'message' => 'Mesaj bulunamadı veya silme yetkiniz yok.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mesaj silinirken bir hata oluştu.']);
        }
        break;
        
    case 'get_message':
        // Mesaj içeriğini getir
        $messageId = (int)($_POST['message_id'] ?? 0);
        
        if ($messageId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz mesaj ID.']);
            exit;
        }
        
        // Yetki kontrolü: Admin tüm mesajları görebilir, diğerleri sadece aldıkları mesajları
        if ($userRole === 'admin') {
            $stmt = $conn->prepare("
                SELECT message 
                FROM messages 
                WHERE id = ? AND is_deleted = 0
            ");
            $stmt->bind_param('i', $messageId);
        } else {
            $stmt = $conn->prepare("
                SELECT message 
                FROM messages 
                WHERE id = ? AND receiver_id = ? AND is_deleted = 0
            ");
            $stmt->bind_param('ii', $messageId, $userId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => $result['message']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mesaj bulunamadı veya erişim yetkiniz yok.']);
        }
        break;
        
    case 'mark_as_read':
        // Mesajı okundu olarak işaretle
        $messageId = (int)($_POST['message_id'] ?? 0);
        
        if ($messageId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz mesaj ID.']);
            exit;
        }
        
        // Sadece alıcı kendi mesajını okundu yapabilir
        $stmt = $conn->prepare("
            UPDATE messages 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND receiver_id = ? AND is_deleted = 0
        ");
        $stmt->bind_param('ii', $messageId, $userId);
        
        $success = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        if ($success && $affectedRows > 0) {
            echo json_encode(['success' => true, 'message' => 'Mesaj okundu olarak işaretlendi.']);
        } elseif ($affectedRows === 0) {
            echo json_encode(['success' => false, 'message' => 'Mesaj bulunamadı veya zaten okunmuş.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'İşlem sırasında bir hata oluştu.']);
        }
        break;
        
    case 'get_unread_count':
        // Okunmamış mesaj sayısını getir
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM messages 
            WHERE receiver_id = ? AND is_read = 0 AND is_deleted = 0
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'count' => (int)$result['count']
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
        break;
}
?>
