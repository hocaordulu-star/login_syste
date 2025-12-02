<?php
/**
 * =====================================================
 * ÖĞRETMEN PANELİ (teacher_panel.php)
 * Amaç: Öğretmenin dashboard istatistikleri, video yönetimi, yükleme,
 *       analytics ve bildirimler gibi işlemleri yapabildiği panel.
 * Güvenlik: Giriş ve rol kontrolü (yalnızca 'teacher'). AJAX istekleri için
 *           JSON yanıt standardı uygulanır, fatal hatalar JSON olarak yüzeye çıkarılır.
 * Not: Bu dosyada yalnızca açıklayıcı yorumlar eklendi; davranış değişikliği yapılmadı.
 * =====================================================
 */

session_start();
include 'config.php';
require_once 'classes/TeacherManager.php';

// Kimlik ve rol doğrulama
// Açıklama: Öğretmen olmayan kullanıcılar için POST isteğine JSON hata döndürülür,
//           diğer durumlarda login sayfasına yönlendirilir.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim. Lütfen tekrar giriş yapın.']);
        exit;
    }
    header("Location: index.php");
    exit();
}

$teacherManager = new TeacherManager($conn, $_SESSION['user_id']);

// AJAX yanıtlarının temiz JSON olmasını sağla (HTML uyarıları/çıktıları kapatılır)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    @ini_set('display_errors', '0');
    @ini_set('html_errors', '0');
    if (function_exists('ob_get_level') && ob_get_level() === 0) {
        ob_start();
    }
}

// Yalnızca POST istekleri için: Fatal hataları JSON olarak yüzeye çıkaran kapanış fonksiyonu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !function_exists('ajax_fatal_handler')) {
    function ajax_fatal_handler() {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            // Try to clear any output buffer
            if (function_exists('ob_get_level')) {
                while (ob_get_level() > 0) { @ob_end_clean(); }
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Beklenmeyen sunucu hatası oluştu.',
                'debug' => 'Fatal: ' . ($e['message'] ?? '') . ' in ' . ($e['file'] ?? '') . ':' . ($e['line'] ?? '')
            ]);
        }
    }
    register_shutdown_function('ajax_fatal_handler');
}

if (!function_exists('send_json')) {
    // Yardımcı: JSON çıktısı gönderen fonksiyon (buffer içeriğini debug olarak ekler)
    function send_json($data) {
        // Capture and attach any stray buffered output for debugging
        $debug = '';
        if (function_exists('ob_get_contents')) {
            $debug = trim((string)@ob_get_contents());
        }
        if (function_exists('ob_end_clean')) {
            @ob_end_clean();
        }
        header('Content-Type: application/json');
        if ($debug !== '') {
            // Attach as debug; frontend may ignore or log this
            if (is_array($data)) {
                $data['debug'] = $debug;
            }
        }
        echo json_encode($data);
        exit;
    }
}

// AJAX Actions Handler
// Açıklama: Hem form-encoded hem de JSON gövdeyi destekler. 'action' değerine göre
//           TeacherManager metodları çağrılır ve sonuç JSON olarak döndürülür.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Support both form-encoded and JSON bodies
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $isJson = stripos($contentType, 'application/json') !== false;
    $rawInput = $isJson ? file_get_contents('php://input') : '';
    $jsonInput = $isJson ? json_decode($rawInput, true) : null;
    if ($isJson && json_last_error() !== JSON_ERROR_NONE) {
        send_json(['success' => false, 'message' => 'Geçersiz JSON gövdesi']);
    }
    $action = $_POST['action'] ?? ($jsonInput['action'] ?? '');
    
    switch ($action) {
        case 'get_dashboard_stats':
            // Dashboard kartları için istatistikleri getir
            $stats = $teacherManager->getDashboardStats();
            send_json(['success' => true, 'stats' => $stats]);
            
        case 'get_videos':
            // Filtrelere göre videoları listele (pagination dahil)
            $filters = [];
            if (!empty($_POST['status'])) $filters['status'] = $_POST['status'];
            if (!empty($_POST['grade'])) $filters['grade'] = $_POST['grade'];
            if (!empty($_POST['search'])) $filters['search'] = $_POST['search'];
            if (!empty($_POST['date_from'])) $filters['date_from'] = $_POST['date_from'];
            if (!empty($_POST['date_to'])) $filters['date_to'] = $_POST['date_to'];
            
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $videos = $teacherManager->getVideos($filters, $page, 20);
            echo json_encode(['success' => true, 'videos' => $videos, 'pagination' => $videos]);
            exit;
            
        case 'upload_video':
            // Video yükleme (dosya veya YouTube URL ile)
            $videoData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'grade' => $_POST['grade'] ?? '',
                'unit_id' => $_POST['unit_id'] ?? 0,
                'topic' => $_POST['topic'] ?? '',
                'youtube_url' => $_POST['youtube_url'] ?? ''
            ];
            
            $fileData = $_FILES['video'] ?? [];
            try {
                $result = $teacherManager->uploadVideo($videoData, $fileData);
                send_json($result);
            } catch (Throwable $ex) {
                send_json(['success' => false, 'message' => 'Sunucu hatası oluştu.', 'debug' => $ex->getMessage()]);
            }
            
        case 'bulk_operation':
            // Çoklu video işlemleri (edit talebi, silme vb.)
            $input = is_array($jsonInput) ? $jsonInput : [];
            $videoIds = isset($input['video_ids']) && is_array($input['video_ids']) ? array_map('intval', $input['video_ids']) : [];
            $operation = $input['operation'] ?? '';
            if (!$videoIds || !$operation) {
                send_json(['success' => false, 'message' => 'Geçersiz parametreler']);
            }
            $result = $teacherManager->bulkVideoOperation($videoIds, $operation);
            send_json($result);
            
        case 'get_notifications':
            // Bildirimleri getir (gerçek zamanlı değil, periyodik kontrol)
            $notifications = $teacherManager->getNotifications();
            send_json(['success' => true, 'notifications' => $notifications]);
    }
    
    send_json(['success' => false, 'message' => 'Geçersiz işlem']);
}

// Sayfa ilk yüklemesinde gösterilecek veriler (dashboard özetleri, son videolar)
$dashboardStats = $teacherManager->getDashboardStats();
$recentVideos = $teacherManager->getVideos([], 1, 5);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğretmen Paneli - Eğitim Sistemi</title>
    <script>
      // Force dark theme ASAP
      (function(){
        try { localStorage.setItem('theme','dark'); } catch(e) {}
        var root = document.documentElement;
        root.setAttribute('data-theme','dark');
      })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/tokens.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/student-styles.css" rel="stylesheet">
    <link href="assets/css/teacher-styles.css" rel="stylesheet">
    <style>
      /* Minimal extra styles to ensure mobile-first responsiveness */
      .tp-container{max-width:var(--container-lg,1100px);margin-inline:auto;padding:var(--space-6,1.5rem) var(--space-4,1rem);} 
      .tp-layout{display:grid;grid-template-columns:260px 1fr;gap:var(--space-6,1.5rem);} 
      @media(max-width:1024px){.tp-layout{grid-template-columns:1fr;} #appSidebar{position:fixed;inset-block:0;inset-inline-start:0;transform:translateX(-100%);transition:transform var(--dur,250ms) ease;z-index:var(--z-sidebar,1000);} #appSidebar.is-open{transform:translateX(0);} }
      .tp-panel{background:var(--panel-bg,#fff);border:1px solid var(--border,rgba(0,0,0,.08));border-radius:var(--radius-lg,1rem);box-shadow:var(--shadow,0 8px 24px rgba(0,0,0,.08));}
      .tp-header{display:flex;justify-content:space-between;align-items:center;gap:var(--space-4,1rem);padding:var(--space-4,1rem);} 
      .tp-actions{display:flex;gap:var(--space-2,.5rem);flex-wrap:wrap;align-items:center;}
      .tp-content{padding:var(--space-5,1.25rem);} 
      .tp-grid{display:grid;gap:var(--space-4,1rem);} 
      @media(min-width:768px){.tp-grid-2{grid-template-columns:repeat(2,minmax(0,1fr));}.tp-grid-3{grid-template-columns:repeat(3,minmax(0,1fr));}}
      
      /* Teacher navigation links styling */
      .teacher-nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: var(--text, #374151);
        text-decoration: none;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        font-weight: 500;
      }
      .teacher-nav-link i {
        width: 1.25rem;
        text-align: center;
        font-size: 1.1rem;
      }
      .teacher-nav-link:hover {
        background: var(--hover-bg, rgba(66, 133, 244, 0.1));
        color: var(--primary, #4285f4);
      }
      .teacher-nav-link.active {
        background: var(--primary, #4285f4);
        color: white;
      }
      .teacher-nav-link.active:hover {
        background: var(--primary-dark, #357ae8);
      }
    </style>
</head>
<body>
  <?php include 'navbar.php'; ?>
  <div class="student-container">
    <div class="student-layout">
      <!-- Sidebar -->
      <aside id="appSidebar" class="student-sidebar sidebar" aria-label="Yan menü" aria-hidden="true">
        <div class="sidebar-header" style="border-bottom:1px solid var(--border,rgba(0,0,0,.08));">
          <div class="sidebar-brand" style="display:flex;align-items:center;gap:.75rem;">
            <div class="brand-icon" style="inline-size:44px;block-size:44px;display:grid;place-items:center;border-radius:12px;background:linear-gradient(135deg,var(--primary,#667eea),var(--secondary,#764ba2));color:#fff;">
              <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="brand-text">
              <h2 style="margin:0;font-size:1.1rem;">Öğretmen</h2>
              <p style="margin:0;color:var(--text-muted,#6b7280);">Panel</p>
            </div>
          </div>
        </div>
        <nav class="teacher-nav" style="padding:1rem;">
          <ul class="teacher-nav-list" style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.25rem;">
            <li><a href="#" class="teacher-nav-link nav-link active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li><a href="#" class="teacher-nav-link nav-link" data-section="videos"><i class="fas fa-video"></i><span>Videolarım</span></a></li>
            <li><a href="#" class="teacher-nav-link nav-link" data-section="upload"><i class="fas fa-upload"></i><span>Video Yükle</span></a></li>
            <li><a href="video_edit_request.php" class="teacher-nav-link"><i class="fas fa-edit"></i><span>Talepler</span></a></li>
            <li><a href="inbox.php" class="teacher-nav-link"><i class="fas fa-envelope"></i><span>Mesajlar</span></a></li>
          </ul>
        </nav>
      </aside>

      <!-- Overlay for mobile sidebar -->
      <div id="sidebarOverlay" class="sidebar-overlay" hidden></div>

      <!-- Main -->
      <main class="student-main">
        <header class="main-header">
          <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menüyü aç/kapat"
                    data-sidebar-toggle data-target="#appSidebar" aria-controls="appSidebar" aria-expanded="false">
              <i class="fas fa-bars"></i>
            </button>
            <h1 id="pageTitle">Dashboard</h1>
          </div>
          <div class="header-right">
            <div class="search-box">
              <input type="text" id="teacherSearch" placeholder="Video ara...">
              <i class="fas fa-search"></i>
            </div>
            
            <div class="user-menu">
              <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['first_name'] ?? 'T', 0, 1)) ?>
              </div>
              <span class="user-name"><?= htmlspecialchars($_SESSION['first_name'] ?? 'Öğretmen') ?></span>
            </div>
          </div>
        </header>

        <section class="content-area">
          <!-- Dashboard -->
          <section id="dashboardSection" class="content-section">
            <div class="stats-grid">
              <div class="card">
                <h3 style="margin:0 0 .5rem 0;">Toplam Video</h3>
                <div style="font-size:2rem;font-weight:800;"><?= $dashboardStats['videos']['total_videos'] ?? 0 ?></div>
                <div class="meta"><i class="fas fa-arrow-up"></i> Bu hafta: <?= $dashboardStats['videos']['videos_this_week'] ?? 0 ?></div>
              </div>
              <div class="card">
                <h3 style="margin:0 0 .5rem 0;">Onaylanan</h3>
                <div style="font-size:2rem;font-weight:800;"><?= $dashboardStats['videos']['approved_videos'] ?? 0 ?></div>
                <div class="meta">Oran: <?= $dashboardStats['videos']['total_videos'] > 0 ? round(($dashboardStats['videos']['approved_videos'] / $dashboardStats['videos']['total_videos']) * 100) : 0 ?>%</div>
              </div>
              <div class="card">
                <h3 style="margin:0 0 .5rem 0;">Bekleyen</h3>
                <div style="font-size:2rem;font-weight:800;"><?= $dashboardStats['videos']['pending_videos'] ?? 0 ?></div>
                <div class="meta">Admin onayı bekleniyor</div>
              </div>
              <div class="card">
                <h3 style="margin:0 0 .5rem 0;">Toplam İzlenme</h3>
                <div style="font-size:2rem;font-weight:800;"><?= number_format($dashboardStats['engagement']['total_views'] ?? 0) ?></div>
                <div class="meta"><i class="fas fa-users"></i> <?= $dashboardStats['engagement']['unique_viewers'] ?? 0 ?> izleyici</div>
              </div>
            </div>

            <div class="card" style="margin-top:1rem;">
              <h2 style="margin:0 0 .75rem 0;">Son Aktiviteler</h2>
              <div id="recentActivity" class="loading"><div class="spinner"></div> Yükleniyor...</div>
            </div>
          </section>

          <!-- Videos -->
          <section id="videosSection" class="content-section" style="display:none;">
            <div class="tp-actions" style="margin-bottom:.75rem;justify-content:space-between;">
              <h2 style="margin:0;">Videolarım</h2>
              <button class="btn btn-secondary" onclick="teacherPanel.clearFilters()"><i class="fas fa-filter"></i> Filtreleri Temizle</button>
            </div>
            <div class="tp-grid tp-grid-3">
              <div class="card">
                <label class="filter-label">Durum</label>
                <select class="form-select" id="statusFilter"><option value="">Tümü</option><option value="pending">Bekleyen</option><option value="approved">Onaylanan</option><option value="rejected">Reddedilen</option></select>
              </div>
              <div class="card">
                <label class="filter-label">Sınıf</label>
                <select class="form-select" id="gradeFilter"><option value="">Tümü</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option></select>
              </div>
              <div class="card tp-grid tp-grid-2">
                <div>
                  <label class="filter-label">Başlangıç</label>
                  <input type="date" class="form-input" id="dateFromFilter">
                </div>
                <div>
                  <label class="filter-label">Bitiş</label>
                  <input type="date" class="form-input" id="dateToFilter">
                </div>
              </div>
            </div>

            <div class="table-container" style="margin-top:1rem;">
              <table class="table">
                <thead><tr><th><input type="checkbox" id="selectAllVideos"></th><th>Video</th><th>Sınıf</th><th>Ünite</th><th>Konu</th><th>Durum</th><th>İstatistik</th><th>İşlem</th></tr></thead>
                <tbody id="videosTableBody"><tr><td colspan="8" class="loading"><div class="spinner"></div> Videolar yükleniyor...</td></tr></tbody>
              </table>
            </div>
            <div id="videosPagination" class="pagination" style="margin-top:.75rem;"></div>
          </section>

          <!-- Upload -->
          <section id="uploadSection" class="content-section" style="display:none;">
            <h2 style="margin:0 0 .75rem 0;">Video Yükle</h2>
            <form id="videoUploadForm" enctype="multipart/form-data" class="tp-grid tp-grid-2">
              <div class="card">
                <label class="form-label"><i class="fas fa-heading"></i> Video Başlığı *</label>
                <input type="text" name="title" class="form-input" placeholder="Video başlığını girin" required>
              </div>
              <div class="card">
                <label class="form-label"><i class="fas fa-graduation-cap"></i> Sınıf *</label>
                <select name="grade" id="grade" class="form-select" required>
                  <option value="">Sınıf seçiniz</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option>
                </select>
              </div>
              <div class="card">
                <label class="form-label"><i class="fas fa-book"></i> Ünite *</label>
                <select name="unit_id" id="unit_id" class="form-select" required>
                  <option value="">Önce sınıf seçiniz</option>
                </select>
              </div>
              <div class="card">
                <label class="form-label"><i class="fas fa-tag"></i> Alt Konu</label>
                <input type="text" name="topic" class="form-input" placeholder="Örn: Doğal Sayılar ve İşlemler">
              </div>
              <div class="card" style="grid-column:1/-1;">
                <label class="form-label"><i class="fas fa-align-left"></i> Video Açıklaması</label>
                <textarea name="description" class="form-textarea" placeholder="Video hakkında detaylı açıklama..."></textarea>
              </div>
              <div class="card" style="grid-column:1/-1;">
                <label class="form-label"><i class="fas fa-video"></i> Video Dosyası *</label>
                <div class="file-upload">
                  <input type="file" name="video" id="videoFile" accept=".mp4,.mov,.webm" required>
                  <div class="file-upload-content">
                    <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="file-upload-text"><strong>Dosya seçin veya sürükleyip bırakın</strong><br><small>MP4, MOV, WEBM formatları desteklenir</small></div>
                  </div>
                </div>
              </div>
              <div class="card" style="grid-column:1/-1;">
                <label class="form-label"><i class="fab fa-youtube"></i> YouTube URL (Opsiyonel)</label>
                <input type="url" name="youtube_url" class="form-input" placeholder="https://www.youtube.com/watch?v=...">
              </div>
              <div style="grid-column:1/-1;display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary btn-lg" id="uploadButton"><i class="fas fa-upload"></i> Video Yükle</button>
              </div>
            </form>
          </section>
        </section>
      </main>
    </div>
  </div>

<?php
  $teacherJsPath = 'assets/js/teacher-interactions.js';
  $teacherJsVer = file_exists($teacherJsPath) ? filemtime($teacherJsPath) : time();
?>
<script src="assets/js/teacher-interactions.js?v=<?= $teacherJsVer ?>"></script>
<script src="assets/js/ui.js" defer></script>
<script>
  // Close sidebar after switching sections on mobile
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.teacher-nav-link[data-section]').forEach(function(link){
      link.addEventListener('click', function(){
        const sidebar = document.getElementById('appSidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        const toggleBtn = document.querySelector('[data-sidebar-toggle][aria-controls="#appSidebar"], [data-sidebar-toggle][data-target="#appSidebar"]');
        if (sidebar && sidebar.classList.contains('is-open')) {
          sidebar.classList.remove('is-open');
          sidebar.setAttribute('aria-hidden','true');
          if (overlay) overlay.classList.remove('is-show');
          document.body.classList.remove('no-scroll');
          if (toggleBtn) toggleBtn.setAttribute('aria-expanded','false');
        }
      });
    });
  });
</script>
</body>
</html>
