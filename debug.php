<?php
/**
 * Debug Helper - InfinityFree hosting için sistem durumu kontrolü
 * Bu dosyayı tarayıcıda açarak sistemin durumunu kontrol edebilirsiniz
 */

session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Durumu - Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Sistem Durumu Kontrolü</h1>
        
        <h2>1. PHP Bilgileri</h2>
        <div class="info">
            <strong>PHP Sürümü:</strong> <?= PHP_VERSION ?><br>
            <strong>Server:</strong> <?= $_SERVER['HTTP_HOST'] ?? 'Bilinmiyor' ?><br>
            <strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor' ?><br>
            <strong>Script Name:</strong> <?= $_SERVER['SCRIPT_NAME'] ?? 'Bilinmiyor' ?>
        </div>

        <h2>2. Hosting Ortamı Tespiti</h2>
        <?php
        $isInfinityFree = strpos($_SERVER['HTTP_HOST'] ?? '', 'infinityfreeapp.com') !== false || 
                          strpos($_SERVER['HTTP_HOST'] ?? '', 'epizy.com') !== false ||
                          strpos($_SERVER['HTTP_HOST'] ?? '', 'rf.gd') !== false;
        ?>
        <div class="<?= $isInfinityFree ? 'warning' : 'info' ?>">
            <strong>Hosting Ortamı:</strong> <?= $isInfinityFree ? 'InfinityFree' : 'Localhost/Diğer' ?>
        </div>

        <h2>3. Veritabanı Bağlantısı</h2>
        <?php
        try {
            require_once __DIR__ . '/config.php';
            echo '<div class="success">✅ Veritabanı bağlantısı başarılı!</div>';
            
            // Test sorgusu
            $result = $conn->query("SELECT COUNT(*) as user_count FROM users");
            if ($result) {
                $row = $result->fetch_assoc();
                echo '<div class="info">👥 Toplam kullanıcı sayısı: ' . $row['user_count'] . '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ Veritabanı bağlantı hatası: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <h2>4. Session Durumu</h2>
        <?php
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo '<div class="success">✅ Session aktif</div>';
            if (isset($_SESSION['user_id'])) {
                echo '<div class="info">👤 Giriş yapılmış kullanıcı ID: ' . $_SESSION['user_id'] . '</div>';
                echo '<div class="info">🎭 Rol: ' . ($_SESSION['role'] ?? 'Bilinmiyor') . '</div>';
            } else {
                echo '<div class="warning">⚠️ Kullanıcı girişi yapılmamış</div>';
            }
        } else {
            echo '<div class="error">❌ Session aktif değil</div>';
        }
        ?>

        <h2>5. Dosya Varlığı Kontrolü</h2>
        <?php
        $files = [
            'chat.php' => 'Chat API',
            'classes/ChatManager.php' => 'Chat Manager Class',
            'assets/js/chat-interactions.js' => 'Chat JavaScript',
            'assets/css/main-styles.css' => 'Ana CSS'
        ];
        
        foreach ($files as $file => $desc) {
            if (file_exists(__DIR__ . '/' . $file)) {
                echo '<div class="success">✅ ' . $desc . ' (' . $file . ')</div>';
            } else {
                echo '<div class="error">❌ ' . $desc . ' (' . $file . ') - Dosya bulunamadı!</div>';
            }
        }
        ?>

        <h2>6. JavaScript Dosya Yolu Kontrolü</h2>
        <?php
        function cache_bust_debug($path) {
            $full = __DIR__ . '/' . ltrim($path, '/');
            $v = file_exists($full) ? filemtime($full) : time();
            return $path . '?v=' . $v;
        }
        
        $jsPath = 'assets/js/chat-interactions.js';
        $fullJsPath = __DIR__ . '/' . $jsPath;
        $jsUrl = cache_bust_debug($jsPath);
        
        echo '<div class="info">';
        echo '<strong>JavaScript Dosya Bilgileri:</strong><br>';
        echo 'Dosya yolu: ' . htmlspecialchars($jsPath) . '<br>';
        echo 'Tam yol: ' . htmlspecialchars($fullJsPath) . '<br>';
        echo 'URL: ' . htmlspecialchars($jsUrl) . '<br>';
        echo 'Dosya var mı: ' . (file_exists($fullJsPath) ? '✅ Evet' : '❌ Hayır') . '<br>';
        if (file_exists($fullJsPath)) {
            echo 'Dosya boyutu: ' . filesize($fullJsPath) . ' bytes<br>';
            echo 'Son değişiklik: ' . date('Y-m-d H:i:s', filemtime($fullJsPath)) . '<br>';
        }
        echo '</div>';
        
        echo '<p><a href="' . $jsUrl . '" target="_blank">JavaScript dosyasını doğrudan aç</a></p>';
        ?>

        <h2>7. Chat API Test</h2>
        <?php
        if (isset($_SESSION['user_id'])) {
            echo '<div class="info">🧪 Chat API testleri için giriş yapılmış durumda</div>';
            echo '<p><a href="chat.php?action=get_unread_count" target="_blank">Okunmamış mesaj sayısını test et</a></p>';
            echo '<p><a href="chat.php?action=get_conversations" target="_blank">Konuşmaları test et</a></p>';
        } else {
            echo '<div class="warning">⚠️ Chat API testleri için önce giriş yapın</div>';
            echo '<p><a href="index.php">Giriş sayfasına git</a></p>';
        }
        ?>

        <h2>8. JavaScript Console Testi</h2>
        <div class="info">
            Tarayıcınızın Developer Tools (F12) konsolunu açın ve aşağıdaki mesajları kontrol edin:
        </div>
        
        <div id="js-test-results" style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">
            <strong>JavaScript Test Sonuçları:</strong>
            <div id="js-load-status">Test ediliyor...</div>
        </div>
        
        <h2>8. Server Bilgileri</h2>
        <pre><?php
        $serverInfo = [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A',
            'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'N/A',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'HTTPS' => $_SERVER['HTTPS'] ?? 'N/A',
            'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'N/A'
        ];
        print_r($serverInfo);
        ?></pre>

        <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 4px;">
            <h3>📝 Sonraki Adımlar:</h3>
            <ol>
                <li>Eğer veritabanı bağlantısı başarısızsa, <code>config.php</code> dosyasındaki InfinityFree bilgilerini güncelleyin</li>
                <li>Session sorunu varsa, tarayıcı çerezlerini temizleyin</li>
                <li>Chat API testlerinde hata varsa, browser konsolunu kontrol edin</li>
                <li>Dosya eksikse, tüm dosyaların hosting'e yüklendiğinden emin olun</li>
            </ol>
        </div>
    </div>

    <!-- Chat JavaScript dosyasını yükle -->
    <?php
    function cache_bust($path) {
        $full = __DIR__ . '/' . ltrim($path, '/');
        $v = file_exists($full) ? filemtime($full) : time();
        return $path . '?v=' . $v;
    }
    ?>
    <script>
        window.CURRENT_USER_ID = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null' ?>;
    </script>
    <!-- Test basit JavaScript yükleme -->
    <script src="test-js.js"></script>
    <script src="<?= cache_bust('assets/js/chat-interactions.js') ?>"></script>
    <script>
        console.log('🔧 Debug sayfası yüklendi');
        console.log('📊 Hosting ortamı:', <?= json_encode($isInfinityFree ? 'InfinityFree' : 'Localhost') ?>);
        console.log('👤 Session durumu:', <?= json_encode(isset($_SESSION['user_id']) ? 'Giriş yapılmış' : 'Giriş yapılmamış') ?>);
        
        const statusDiv = document.getElementById('js-load-status');
        
        // JavaScript dosyalarının yüklenip yüklenmediğini test et
        function checkChatJS() {
            console.log('🔍 JavaScript kontrolleri...');
            console.log('window.TestJS:', typeof window.TestJS);
            console.log('window.ChatUI:', typeof window.ChatUI);
            
            let status = '';
            
            // Test JS kontrolü
            if (typeof window.TestJS !== 'undefined') {
                console.log('✅ Test JavaScript yüklendi');
                status += '✅ Test JS: Başarılı<br>';
            } else {
                console.log('❌ Test JavaScript yüklenemedi');
                status += '❌ Test JS: Başarısız<br>';
            }
            
            // Chat JS kontrolü
            if (typeof window.ChatUI !== 'undefined') {
                console.log('✅ Chat JavaScript başarıyla yüklendi');
                console.log('🔧 ChatUI fonksiyonları:', Object.keys(window.ChatUI));
                status += '✅ Chat JS: Başarılı<br>📋 Fonksiyonlar: ' + Object.keys(window.ChatUI).join(', ');
                if (statusDiv) statusDiv.style.color = 'green';
            } else {
                console.log('❌ Chat JavaScript yüklenemedi');
                console.log('🔍 Chat ile ilgili window nesneleri:', Object.keys(window).filter(k => k.toLowerCase().includes('chat')));
                status += '❌ Chat JS: Başarısız - F12 Network sekmesini kontrol edin';
                if (statusDiv) statusDiv.style.color = 'red';
            }
            
            if (statusDiv) statusDiv.innerHTML = status;
        }
        
        // Birden fazla zamanlama ile test et
        setTimeout(checkChatJS, 100);
        setTimeout(checkChatJS, 500);
        setTimeout(checkChatJS, 1000);
        
        // Script yükleme hatalarını yakala
        window.addEventListener('error', function(e) {
            if (e.filename && e.filename.includes('chat-interactions.js')) {
                console.error('🚨 Chat JavaScript yükleme hatası:', e.message);
                if (statusDiv) {
                    statusDiv.innerHTML = '🚨 JavaScript yükleme hatası: ' + e.message;
                    statusDiv.style.color = 'red';
                }
            }
        });
    </script>
</body>
</html>
