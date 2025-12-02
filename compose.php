<?php
/**
 * =====================================================
 * YENİ MESAJ OLUŞTUR (compose.php)
 * Amaç: Kullanıcıların birbirlerine mesaj göndermesi
 * Özellikler:
 * - Öğrenci → Öğretmen mesaj gönderebilir
 * - Öğretmen ↔ Öğrenci mesajlaşabilir
 * - Admin → Herkese mesaj gönderebilir
 * Güvenlik: Giriş kontrolü, SQL injection koruması, XSS koruması
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
$errors = [];
$success = false;

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validasyon
    if ($receiverId <= 0) {
        $errors[] = 'Lütfen bir alıcı seçin.';
    }
    if (empty($subject)) {
        $errors[] = 'Konu alanı boş bırakılamaz.';
    }
    if (empty($message)) {
        $errors[] = 'Mesaj içeriği boş bırakılamaz.';
    }
    
    // Alıcının geçerli bir kullanıcı olduğunu kontrol et
    if ($receiverId > 0) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND status = 'approved'");
        $checkStmt->bind_param('i', $receiverId);
        $checkStmt->execute();
        $receiverExists = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if (!$receiverExists) {
            $errors[] = 'Seçilen kullanıcı bulunamadı veya aktif değil.';
        }
    }
    
    // Hata yoksa mesajı kaydet
    if (empty($errors)) {
        $insertStmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $insertStmt->bind_param('iiss', $userId, $receiverId, $subject, $message);
        
        if ($insertStmt->execute()) {
            $success = true;
            // Formu temizle
            $_POST = [];
        } else {
            $errors[] = 'Mesaj gönderilemedi. Lütfen tekrar deneyin.';
        }
        $insertStmt->close();
    }
}

// Alıcı listesini hazırla (role göre)
$receivers = [];

if ($userRole === 'admin') {
    // Admin herkese mesaj gönderebilir
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, role 
        FROM users 
        WHERE id != ? AND status = 'approved'
        ORDER BY role, first_name, last_name
    ");
    $stmt->bind_param('i', $userId);
} elseif ($userRole === 'teacher') {
    // Öğretmen öğrencilere ve diğer öğretmenlere mesaj gönderebilir
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, role 
        FROM users 
        WHERE id != ? AND status = 'approved' AND role IN ('student', 'teacher')
        ORDER BY role, first_name, last_name
    ");
    $stmt->bind_param('i', $userId);
} elseif ($userRole === 'student') {
    // Öğrenci sadece öğretmenlere mesaj gönderebilir
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, role 
        FROM users 
        WHERE role = 'teacher' AND status = 'approved'
        ORDER BY first_name, last_name
    ");
}

if (isset($stmt)) {
    $stmt->execute();
    $receivers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Mesaj - Mesajlaşma Sistemi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/messaging.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="messaging-container">
        <div class="messaging-header">
            <h1><i class="fas fa-pen"></i> Yeni Mesaj</h1>
            <a href="inbox.php" class="btn btn-secondary">
                <i class="fas fa-inbox"></i> Gelen Kutusu
            </a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Başarılı!</strong>
                    <p>Mesajınız başarıyla gönderildi.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Hata!</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="compose-form-container">
            <form method="POST" class="compose-form">
                <div class="form-group">
                    <label for="receiver_id">
                        <i class="fas fa-user"></i> Alıcı *
                    </label>
                    <select name="receiver_id" id="receiver_id" required class="form-control">
                        <option value="">Alıcı seçin...</option>
                        <?php
                        $currentGroup = '';
                        foreach ($receivers as $receiver):
                            // Role göre gruplandırma
                            $roleLabel = [
                                'admin' => 'Yöneticiler',
                                'teacher' => 'Öğretmenler',
                                'student' => 'Öğrenciler'
                            ][$receiver['role']] ?? 'Diğer';
                            
                            if ($currentGroup !== $roleLabel) {
                                if ($currentGroup !== '') echo '</optgroup>';
                                echo '<optgroup label="' . htmlspecialchars($roleLabel) . '">';
                                $currentGroup = $roleLabel;
                            }
                        ?>
                            <option value="<?= $receiver['id'] ?>" <?= (isset($_POST['receiver_id']) && $_POST['receiver_id'] == $receiver['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($receiver['first_name'] . ' ' . $receiver['last_name']) ?> 
                                (<?= htmlspecialchars($receiver['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                        <?php if ($currentGroup !== ''): ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                    <small class="form-hint">
                        <?php if ($userRole === 'student'): ?>
                            Sadece öğretmenlere mesaj gönderebilirsiniz.
                        <?php elseif ($userRole === 'teacher'): ?>
                            Öğrenciler ve öğretmenlerle mesajlaşabilirsiniz.
                        <?php else: ?>
                            Tüm kullanıcılara mesaj gönderebilirsiniz.
                        <?php endif; ?>
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="subject">
                        <i class="fas fa-tag"></i> Konu *
                    </label>
                    <input 
                        type="text" 
                        name="subject" 
                        id="subject" 
                        required 
                        maxlength="255"
                        class="form-control"
                        placeholder="Mesaj konusunu girin..."
                        value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="message">
                        <i class="fas fa-comment"></i> Mesaj *
                    </label>
                    <textarea 
                        name="message" 
                        id="message" 
                        required 
                        rows="10"
                        class="form-control"
                        placeholder="Mesajınızı buraya yazın..."
                    ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    <small class="form-hint">
                        <span id="charCount">0</span> karakter
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Gönder
                    </button>
                    <a href="inbox.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> İptal
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Karakter sayacı
        const messageTextarea = document.getElementById('message');
        const charCount = document.getElementById('charCount');
        
        function updateCharCount() {
            charCount.textContent = messageTextarea.value.length;
        }
        
        messageTextarea.addEventListener('input', updateCharCount);
        updateCharCount();
        
        // Form validasyonu
        document.querySelector('.compose-form').addEventListener('submit', function(e) {
            const receiverId = document.getElementById('receiver_id').value;
            const subject = document.getElementById('subject').value.trim();
            const message = messageTextarea.value.trim();
            
            if (!receiverId || !subject || !message) {
                e.preventDefault();
                alert('Lütfen tüm gerekli alanları doldurun.');
                return false;
            }
        });
    </script>
</body>
</html>
