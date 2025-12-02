<?php
/**
 * =====================================================
 * MESSAGES TABLOSU OTOMATİK KURULUM
 * Bu dosyayı tarayıcıda çalıştırarak messages tablosunu oluşturabilirsiniz
 * Kullanım: http://localhost/login_system/create_messages_table.php
 * =====================================================
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Messages Tablosu Kurulum</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4285f4;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4285f4;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #357ae8;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📬 Messages Tablosu Kurulum</h1>";

// Veritabanı bağlantısını kontrol et
if ($conn->connect_error) {
    echo "<div class='error'>
            <strong>❌ Veritabanı Bağlantı Hatası!</strong><br>
            Hata: " . htmlspecialchars($conn->connect_error) . "<br><br>
            <strong>Çözüm:</strong>
            <ul>
                <li>XAMPP Control Panel'de MySQL'in çalıştığından emin olun</li>
                <li><code>config.php</code> dosyasındaki veritabanı bilgilerini kontrol edin</li>
            </ul>
          </div>";
    exit;
}

echo "<div class='success'>✅ Veritabanı bağlantısı başarılı!</div>";

// Tablo zaten var mı kontrol et
$checkTable = $conn->query("SHOW TABLES LIKE 'messages'");
if ($checkTable && $checkTable->num_rows > 0) {
    echo "<div class='info'>
            <strong>ℹ️ Bilgi:</strong> <code>messages</code> tablosu zaten mevcut!<br><br>
            Tablo yapısını kontrol ediliyor...
          </div>";
    
    // Tablo yapısını kontrol et
    $describe = $conn->query("DESCRIBE messages");
    $columns = [];
    while ($row = $describe->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $requiredColumns = ['id', 'sender_id', 'receiver_id', 'subject', 'message', 'is_read', 'is_deleted', 'created_at', 'read_at'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "<div class='success'>
                <strong>✅ Tablo yapısı doğru!</strong><br>
                Tüm gerekli sütunlar mevcut. Mesajlaşma sistemi kullanıma hazır.
              </div>";
    } else {
        echo "<div class='error'>
                <strong>⚠️ Eksik Sütunlar:</strong><br>
                " . implode(', ', $missingColumns) . "<br><br>
                Tabloyu silip yeniden oluşturmak için bu sayfayı yenileyip <strong>Evet</strong> butonuna tıklayın.
              </div>";
    }
    
    echo "<br><strong>Mevcut Sütunlar:</strong><br>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><code>" . htmlspecialchars($col) . "</code></li>";
    }
    echo "</ul>";
    
} else {
    // Tablo yok, oluştur
    echo "<div class='info'>📝 Tablo oluşturuluyor...</div>";
    
    $sql = "CREATE TABLE `messages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `sender_id` int(11) NOT NULL COMMENT 'Mesajı gönderen kullanıcı ID',
      `receiver_id` int(11) NOT NULL COMMENT 'Mesajı alan kullanıcı ID',
      `subject` varchar(255) NOT NULL COMMENT 'Mesaj konusu',
      `message` text NOT NULL COMMENT 'Mesaj içeriği',
      `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: okunmamış, 1: okundu',
      `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: aktif, 1: silinmiş',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Mesaj gönderim tarihi',
      `read_at` timestamp NULL DEFAULT NULL COMMENT 'Mesajın okunma tarihi',
      PRIMARY KEY (`id`),
      KEY `sender_id` (`sender_id`),
      KEY `receiver_id` (`receiver_id`),
      KEY `is_read` (`is_read`),
      KEY `is_deleted` (`is_deleted`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Kullanıcılar arası mesajlaşma tablosu'";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>
                <strong>✅ Başarılı!</strong><br>
                <code>messages</code> tablosu başarıyla oluşturuldu!<br><br>
                <strong>Tablo Özellikleri:</strong>
                <ul>
                    <li>✅ Soft delete (is_deleted)</li>
                    <li>✅ Okundu/okunmadı takibi (is_read)</li>
                    <li>✅ Timestamp kayıtları (created_at, read_at)</li>
                    <li>✅ İndekslenmiş sütunlar (performans)</li>
                </ul>
              </div>";
        
        // Test mesajı ekle (opsiyonel)
        $testInsert = $conn->query("INSERT INTO messages (sender_id, receiver_id, subject, message) 
                                     VALUES (1, 1, 'Hoş Geldiniz!', 'Mesajlaşma sistemi başarıyla kuruldu ve çalışıyor! 🎉')");
        
        if ($testInsert) {
            echo "<div class='info'>
                    ℹ️ Test mesajı oluşturuldu! Gelen kutunuzda görebilirsiniz.
                  </div>";
        }
        
    } else {
        echo "<div class='error'>
                <strong>❌ Hata!</strong><br>
                Tablo oluşturulamadı: " . htmlspecialchars($conn->error) . "<br><br>
                <strong>Olası Çözümler:</strong>
                <ul>
                    <li>phpMyAdmin'den manuel olarak oluşturmayı deneyin</li>
                    <li><code>sql/messages_table_simple.sql</code> dosyasını import edin</li>
                    <li>MySQL kullanıcısının CREATE TABLE yetkisi olduğundan emin olun</li>
                </ul>
              </div>";
    }
}

$conn->close();

echo "
        <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
        <h3>📋 Sonraki Adımlar</h3>
        <ol>
            <li>Tarayıcıda <a href='inbox.php' class='btn'>Gelen Kutusuna Git</a></li>
            <li>Navbar'da <strong>Mesajlar</strong> linkine tıklayın</li>
            <li><strong>Yeni Mesaj</strong> butonu ile test mesajı gönderin</li>
        </ol>
        
        <div style='margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;'>
            <strong>⚠️ Güvenlik Notu:</strong><br>
            Kurulum tamamlandıktan sonra bu dosyayı (<code>create_messages_table.php</code>) silin veya yeniden adlandırın.
        </div>
    </div>
</body>
</html>";
?>
