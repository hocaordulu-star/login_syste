<?php
/**
 * =====================================================
 * GELEN KUTUSU (inbox.php)
 * Amaç: Kullanıcının aldığı mesajları görüntüleme ve yönetme
 * Özellikler:
 * - Okunmamış mesajları kalın gösterme
 * - Admin tüm mesajları görebilir
 * - Sayfa açıldığında okunmamış mesajlar otomatik okundu yapılır
 * - AJAX ile mesaj silme
 * Güvenlik: Giriş kontrolü, SQL injection koruması
 * =====================================================
 */

session_start();
require_once 'config.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Admin tüm mesajları görebilir, diğerleri sadece kendilerine gelen mesajları
if ($userRole === 'admin') {
    // Admin için tüm mesajlar
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM messages 
        WHERE is_deleted = 0
    ");
    $countStmt->execute();
    $totalResult = $countStmt->get_result()->fetch_assoc();
    $totalMessages = $totalResult['total'];
    $countStmt->close();
    
    $stmt = $conn->prepare("
        SELECT m.*, 
               sender.first_name as sender_first, sender.last_name as sender_last, sender.email as sender_email,
               receiver.first_name as receiver_first, receiver.last_name as receiver_last, receiver.email as receiver_email
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        JOIN users receiver ON m.receiver_id = receiver.id
        WHERE m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $perPage, $offset);
} else {
    // Normal kullanıcılar için sadece kendilerine gelen mesajlar
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM messages 
        WHERE receiver_id = ? AND is_deleted = 0
    ");
    $countStmt->bind_param('i', $userId);
    $countStmt->execute();
    $totalResult = $countStmt->get_result()->fetch_assoc();
    $totalMessages = $totalResult['total'];
    $countStmt->close();
    
    $stmt = $conn->prepare("
        SELECT m.*, 
               sender.first_name as sender_first, sender.last_name as sender_last, sender.email as sender_email
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        WHERE m.receiver_id = ? AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('iii', $userId, $perPage, $offset);
}

$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sayfa sayısı hesaplama
$totalPages = ceil($totalMessages / $perPage);

// Okunmamış mesajları okundu olarak işaretle (sadece kullanıcının kendi mesajları için)
if ($userRole !== 'admin') {
    $updateStmt = $conn->prepare("
        UPDATE messages 
        SET is_read = 1, read_at = NOW() 
        WHERE receiver_id = ? AND is_read = 0 AND is_deleted = 0
    ");
    $updateStmt->bind_param('i', $userId);
    $updateStmt->execute();
    $updateStmt->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelen Kutusu - Mesajlaşma Sistemi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/messaging.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="messaging-container">
        <div class="messaging-header">
            <h1><i class="fas fa-inbox"></i> Gelen Kutusu</h1>
            <a href="compose.php" class="btn btn-primary">
                <i class="fas fa-pen"></i> Yeni Mesaj
            </a>
        </div>
        
        <?php if ($totalMessages > 0): ?>
            <div class="messages-stats">
                <p>Toplam <?= $totalMessages ?> mesaj</p>
            </div>
            
            <div class="messages-table-container">
                <table class="messages-table">
                    <thead>
                        <tr>
                            <?php if ($userRole === 'admin'): ?>
                                <th>Gönderen</th>
                                <th>Alıcı</th>
                            <?php else: ?>
                                <th>Gönderen</th>
                            <?php endif; ?>
                            <th>Konu</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="message-row <?= $msg['is_read'] == 0 ? 'unread' : '' ?>" data-message-id="<?= $msg['id'] ?>">
                                <?php if ($userRole === 'admin'): ?>
                                    <td>
                                        <div class="user-info">
                                            <strong><?= htmlspecialchars($msg['sender_first'] . ' ' . $msg['sender_last']) ?></strong>
                                            <span class="user-email"><?= htmlspecialchars($msg['sender_email']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <strong><?= htmlspecialchars($msg['receiver_first'] . ' ' . $msg['receiver_last']) ?></strong>
                                            <span class="user-email"><?= htmlspecialchars($msg['receiver_email']) ?></span>
                                        </div>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <div class="user-info">
                                            <strong><?= htmlspecialchars($msg['sender_first'] . ' ' . $msg['sender_last']) ?></strong>
                                            <span class="user-email"><?= htmlspecialchars($msg['sender_email']) ?></span>
                                        </div>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <div class="message-subject">
                                        <?= htmlspecialchars($msg['subject']) ?>
                                    </div>
                                    <div class="message-preview">
                                        <?= htmlspecialchars(mb_substr($msg['message'], 0, 80)) ?><?= mb_strlen($msg['message']) > 80 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="message-date">
                                        <?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($msg['is_read'] == 0): ?>
                                        <span class="badge badge-unread">Okunmadı</span>
                                    <?php else: ?>
                                        <span class="badge badge-read">Okundu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="message-actions">
                                        <button class="btn-icon btn-view" onclick="viewMessage(<?= $msg['id'] ?>)" title="Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon btn-delete" onclick="deleteMessage(<?= $msg['id'] ?>)" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Önceki
                        </a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        Sayfa <?= $page ?> / <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="page-link">
                            Sonraki <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h2>Hiç mesajınız yok</h2>
                <p>Henüz hiç mesaj almadınız.</p>
                <a href="compose.php" class="btn btn-primary">
                    <i class="fas fa-pen"></i> Yeni Mesaj Gönder
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Mesaj Görüntüleme Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalSubject"></h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="message-meta">
                    <p><strong>Gönderen:</strong> <span id="modalSender"></span></p>
                    <?php if ($userRole === 'admin'): ?>
                        <p><strong>Alıcı:</strong> <span id="modalReceiver"></span></p>
                    <?php endif; ?>
                    <p><strong>Tarih:</strong> <span id="modalDate"></span></p>
                </div>
                <div class="message-content" id="modalMessage"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Kapat</button>
            </div>
        </div>
    </div>
    
    <script>
        // Mesaj görüntüleme
        function viewMessage(messageId) {
            // Satırdan bilgileri al
            const row = document.querySelector(`tr[data-message-id="${messageId}"]`);
            if (!row) return;
            
            const subject = row.querySelector('.message-subject').textContent;
            const sender = row.querySelector('.user-info strong').textContent;
            const email = row.querySelector('.user-email').textContent;
            const date = row.querySelector('.message-date').textContent;
            
            // Modal'ı doldur
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalSender').textContent = sender + ' (' + email + ')';
            document.getElementById('modalDate').textContent = date;
            
            <?php if ($userRole === 'admin'): ?>
                const receiverInfo = row.querySelectorAll('.user-info')[1];
                const receiverName = receiverInfo.querySelector('strong').textContent;
                const receiverEmail = receiverInfo.querySelector('.user-email').textContent;
                document.getElementById('modalReceiver').textContent = receiverName + ' (' + receiverEmail + ')';
            <?php endif; ?>
            
            // Mesaj içeriğini AJAX ile al
            fetch('message_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_message&message_id=' + messageId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalMessage').innerHTML = escapeHtml(data.message).replace(/\n/g, '<br>');
                    openModal();
                } else {
                    alert('Mesaj yüklenemedi: ' + (data.message || 'Bilinmeyen hata'));
                }
            })
            .catch(error => {
                alert('Bir hata oluştu: ' + error.message);
            });
        }
        
        // Mesaj silme (AJAX - sayfa yenilenmez)
        function deleteMessage(messageId) {
            if (!confirm('Bu mesajı silmek istediğinize emin misiniz?')) {
                return;
            }
            
            fetch('message_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_message&message_id=' + messageId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Satırı DOM'dan kaldır
                    const row = document.querySelector(`tr[data-message-id="${messageId}"]`);
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transition = 'opacity 0.3s';
                        setTimeout(() => {
                            row.remove();
                            
                            // Eğer hiç mesaj kalmadıysa sayfayı yenile
                            const remainingRows = document.querySelectorAll('.message-row');
                            if (remainingRows.length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                    
                    // Bildirim göster
                    showNotification('Mesaj başarıyla silindi', 'success');
                } else {
                    alert('Mesaj silinemedi: ' + (data.message || 'Bilinmeyen hata'));
                }
            })
            .catch(error => {
                alert('Bir hata oluştu: ' + error.message);
            });
        }
        
        // Modal açma/kapama
        function openModal() {
            document.getElementById('messageModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            const modal = document.getElementById('messageModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // ESC tuşu ile modal kapatma
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
        // HTML kaçış fonksiyonu
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Bildirim gösterme
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = 'notification notification-' + type;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
