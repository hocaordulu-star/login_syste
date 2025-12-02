<?php
/**
 * =====================================================
 * MESSAGES TABLOSU TAMİR ARACI
 * Eksik sütunları otomatik ekler
 * Kullanım: http://localhost/login_system/fix_messages_table.php
 * =====================================================
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Messages Tablosu Tamir</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 900px;
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
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
            margin: 15px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4285f4;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            font-weight: 600;
        }
        .btn:hover {
            background: #357ae8;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .step {
            background: #e8f0fe;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 3px solid #4285f4;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Messages Tablosu Tamir Aracı</h1>";

// Veritabanı bağlantısı kontrolü
if ($conn->connect_error) {
    echo "<div class='error'><strong>❌ Bağlantı Hatası:</strong><br>" . htmlspecialchars($conn->connect_error) . "</div>";
    exit;
}

echo "<div class='success'>✅ Veritabanına bağlanıldı</div>";

// Tablo var mı kontrol et
$tableExists = $conn->query("SHOW TABLES LIKE 'messages'");
if (!$tableExists || $tableExists->num_rows == 0) {
    echo "<div class='error'>
            <strong>❌ Hata:</strong> Messages tablosu bulunamadı!<br><br>
            Önce tabloyu oluşturmalısınız: <a href='create_messages_table.php' class='btn'>Tablo Oluştur</a>
          </div>";
    exit;
}

echo "<div class='info'>📋 Messages tablosu bulundu, yapı kontrol ediliyor...</div>";

// Mevcut sütunları al
$describe = $conn->query("DESCRIBE messages");
$existingColumns = [];
while ($row = $describe->fetch_assoc()) {
    $existingColumns[$row['Field']] = $row;
}

echo "<h3>📊 Mevcut Tablo Yapısı</h3>";
echo "<table>";
echo "<thead><tr><th>Sütun Adı</th><th>Tip</th><th>Null</th><th>Default</th></tr></thead>";
echo "<tbody>";
foreach ($existingColumns as $col => $info) {
    echo "<tr>
            <td><code>" . htmlspecialchars($col) . "</code></td>
            <td>" . htmlspecialchars($info['Type']) . "</td>
            <td>" . htmlspecialchars($info['Null']) . "</td>
            <td>" . htmlspecialchars($info['Default'] ?? 'NULL') . "</td>
          </tr>";
}
echo "</tbody></table>";

// Gerekli sütunlar ve tanımları
$requiredColumns = [
    'id' => "INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY",
    'sender_id' => "INT(11) NOT NULL",
    'receiver_id' => "INT(11) NOT NULL",
    'subject' => "VARCHAR(255) NOT NULL",
    'message' => "TEXT NOT NULL",
    'is_read' => "TINYINT(1) NOT NULL DEFAULT 0",
    'is_deleted' => "TINYINT(1) NOT NULL DEFAULT 0",
    'created_at' => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
    'read_at' => "TIMESTAMP NULL DEFAULT NULL"
];

// Eksik sütunları tespit et
$missingColumns = [];
foreach ($requiredColumns as $colName => $colDef) {
    if (!isset($existingColumns[$colName])) {
        $missingColumns[$colName] = $colDef;
    }
}

// Tamir işlemi
if (empty($missingColumns)) {
    echo "<div class='success'>
            <strong>✅ Mükemmel!</strong><br>
            Tablo yapısı tamamen doğru. Tüm gerekli sütunlar mevcut.
          </div>";
    echo "<a href='inbox.php' class='btn'>Gelen Kutusuna Git</a>";
} else {
    echo "<div class='warning'>
            <strong>⚠️ Eksik Sütunlar Bulundu:</strong><br>
            Aşağıdaki sütunlar eklenecek:
            <ul>";
    foreach ($missingColumns as $colName => $colDef) {
        echo "<li><code>" . htmlspecialchars($colName) . "</code></li>";
    }
    echo "</ul></div>";
    
    echo "<h3>🔨 Tamir İşlemi Başlatılıyor...</h3>";
    
    $allSuccess = true;
    foreach ($missingColumns as $colName => $colDef) {
        echo "<div class='step'>📝 Sütun ekleniyor: <code>$colName</code></div>";
        
        $alterSql = "ALTER TABLE `messages` ADD COLUMN `$colName` $colDef";
        
        if ($conn->query($alterSql) === TRUE) {
            echo "<div class='success'>✅ <code>$colName</code> sütunu başarıyla eklendi</div>";
        } else {
            echo "<div class='error'>❌ <code>$colName</code> eklenirken hata: " . htmlspecialchars($conn->error) . "</div>";
            $allSuccess = false;
        }
    }
    
    if ($allSuccess) {
        echo "<div class='success'>
                <strong>🎉 Tebrikler!</strong><br>
                Tüm eksik sütunlar başarıyla eklendi. Tablo artık kullanıma hazır!
              </div>";
        
        // İndeksleri ekle
        echo "<h3>📊 İndeksler Ekleniyor...</h3>";
        
        $indexes = [
            "sender_id" => "ALTER TABLE `messages` ADD INDEX `sender_id` (`sender_id`)",
            "receiver_id" => "ALTER TABLE `messages` ADD INDEX `receiver_id` (`receiver_id`)",
            "is_read" => "ALTER TABLE `messages` ADD INDEX `is_read` (`is_read`)",
            "is_deleted" => "ALTER TABLE `messages` ADD INDEX `is_deleted` (`is_deleted`)"
        ];
        
        foreach ($indexes as $indexName => $indexSql) {
            // Önce indeks var mı kontrol et
            $checkIndex = $conn->query("SHOW INDEX FROM messages WHERE Key_name = '$indexName'");
            if ($checkIndex && $checkIndex->num_rows == 0) {
                if ($conn->query($indexSql) === TRUE) {
                    echo "<div class='success'>✅ İndeks eklendi: <code>$indexName</code></div>";
                }
            }
        }
        
        echo "<hr style='margin: 30px 0;'>";
        echo "<h3>✅ Tamir Tamamlandı!</h3>";
        echo "<a href='inbox.php' class='btn'>🎯 Gelen Kutusuna Git</a>";
        echo "<a href='compose.php' class='btn'>✉️ Mesaj Gönder</a>";
        
    } else {
        echo "<div class='error'>
                <strong>❌ Bazı sütunlar eklenemedi</strong><br>
                Lütfen phpMyAdmin'den manuel olarak eklemeyi deneyin.
              </div>";
    }
}

// Güncellenmiş tablo yapısını göster
echo "<hr style='margin: 30px 0;'>";
echo "<h3>🔄 Güncel Tablo Yapısı</h3>";

$describe = $conn->query("DESCRIBE messages");
echo "<table>";
echo "<thead><tr><th>Sütun Adı</th><th>Tip</th><th>Null</th><th>Default</th></tr></thead>";
echo "<tbody>";
while ($row = $describe->fetch_assoc()) {
    $isNew = isset($missingColumns[$row['Field']]);
    $style = $isNew ? "background: #d4edda; font-weight: 600;" : "";
    echo "<tr style='$style'>
            <td><code>" . htmlspecialchars($row['Field']) . "</code>" . ($isNew ? " 🆕" : "") . "</td>
            <td>" . htmlspecialchars($row['Type']) . "</td>
            <td>" . htmlspecialchars($row['Null']) . "</td>
            <td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>
          </tr>";
}
echo "</tbody></table>";

$conn->close();

echo "
        <div class='warning' style='margin-top: 30px;'>
            <strong>🔒 Güvenlik:</strong><br>
            Tamir tamamlandıktan sonra bu dosyayı silin: <code>fix_messages_table.php</code>
        </div>
    </div>
</body>
</html>";
?>
