<?php
/**
 * ÖĞRENCİ PANELİ - Temiz ve Modern Tasarım
 * Responsive, mobil uyumlu, koyu/açık tema destekli
 */

session_start();
include 'config.php';
require_once 'classes/StudentManager.php';

// Kullanıcı öğrenci değilse index.php'ye yönlendir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$studentManager = new StudentManager($conn, $_SESSION['user_id']);

// AJAX Actions Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_available_videos':
            $grade = $_POST['grade'] ?? null;
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $videos = $studentManager->getAvailableVideos($grade, 'math', $page, 20);
            echo json_encode(['success' => true, 'videos' => $videos['videos'], 'pagination' => $videos]);
            exit;
            
        case 'update_video_progress':
            $videoId = (int)$_POST['video_id'];
            $watchDuration = (int)$_POST['watch_duration'];
            $totalDuration = (int)$_POST['total_duration'];
            $result = $studentManager->updateVideoProgress($videoId, $watchDuration, $totalDuration);
            echo json_encode($result);
            exit;
            
        case 'toggle_bookmark':
            $videoId = (int)$_POST['video_id'];
            $notes = $_POST['notes'] ?? '';
            $result = $studentManager->toggleBookmark($videoId, $notes);
            echo json_encode($result);
            exit;
            
        case 'get_bookmarked_videos':
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $result = $studentManager->getBookmarkedVideos($page, 20);
            echo json_encode($result);
            exit;
            
        case 'get_recent_activity':
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
            $result = $studentManager->getRecentActivity($limit);
            echo json_encode($result);
            exit;
            
        case 'check_updates':
            echo json_encode([
                'success' => true,
                'unread_messages' => 0,
                'notifications' => []
            ]);
            exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
    exit;
}

// Sayfa ilk yükleme verileri
$recentVideos = $studentManager->getAvailableVideos(null, 'math', 1, 5);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Paneli - Eğitim Sistemi</title>
    <script>
        // Force dark theme ASAP
        (function(){
            try { localStorage.setItem('theme','dark'); } catch (e) {}
            var root = document.documentElement;
            root.setAttribute('data-theme', 'dark');
        })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/tokens.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/student-styles.css" rel="stylesheet">
</head>
<body>
    <!-- Ortak navbar -->
  <?php include 'navbar.php'; ?>

<div class="student-container">
    <div class="student-layout">
            <!-- Sidebar -->
            <aside id="studentSidebar" class="student-sidebar sidebar" aria-label="Yan menü" aria-hidden="true">
                <div class="sidebar-header">
                    <div class="sidebar-brand">
                        <div class="brand-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                        <div class="brand-text">
                            <h2>Öğrenci</h2>
                            <p>Panel</p>
                        </div>
                </div>
            </div>
            
                <nav class="sidebar-nav">
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="#" class="nav-link active" data-section="dashboard">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="videos">
                            <i class="fas fa-video"></i>
                            <span>Videolar</span>
                        </a>
                    </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="assignments">
                            <i class="fas fa-tasks"></i>
                            <span>Ödevlerim</span>
                        </a>
                    </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="progress">
                            <i class="fas fa-chart-line"></i>
                            <span>İlerleme</span>
                        </a>
                    </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="bookmarks">
                            <i class="fas fa-bookmark"></i>
                            <span>Favorilerim</span>
                        </a>
                    </li>
                        <li class="nav-item">
                            <a href="inbox.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span>Mesajlar</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

            <!-- Overlay for mobile sidebar -->
            <div id="sidebarOverlay" class="sidebar-overlay" hidden></div>

            <!-- Main Content -->
            <main class="student-main">
            <!-- Header -->
                <header class="main-header">
                    <div class="header-left">
                        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menüyü aç/kapat">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 id="pageTitle">Dashboard</h1>
                </div>
                    <div class="header-right">
                        <div class="search-box">
                            <input type="text" id="globalSearch" placeholder="Ara...">
                        <i class="fas fa-search"></i>
                    </div>
                        
                        <div class="user-menu">
                            <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['first_name'] ?? 'Ö', 0, 1)) ?>
                        </div>
                            <span class="user-name"><?= htmlspecialchars($_SESSION['first_name'] ?? 'Öğrenci') ?></span>
                    </div>
                </div>
                </header>

            <!-- Content Area -->
                <div class="content-area">
                    <!-- Dashboard Section -->
                    <section id="dashboardSection" class="content-section active">
                        <div class="section-header">
                            <h2>Dashboard</h2>
                            <p>Öğrenme ilerlemenizi takip edin</p>
                        </div>

                        <!-- Stats Grid -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">
                            <i class="fas fa-video"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>İzlenen Videolar</h3>
                                    <div class="stat-number">0</div>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>Bu hafta +5</span>
                                    </div>
                        </div>
                    </div>
                    
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>Tamamlanan</h3>
                                    <div class="stat-number">0</div>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>Bu hafta +3</span>
                                    </div>
                            </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-bookmark"></i>
                            </div>
                                <div class="stat-content">
                                    <h3>Favoriler</h3>
                                    <div class="stat-number">0</div>
                                    <div class="stat-change">
                                        <i class="fas fa-minus"></i>
                                        <span>Değişiklik yok</span>
                        </div>
                    </div>
                                </div>
                                
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                                    </div>
                                <div class="stat-content">
                                    <h3>Toplam Süre</h3>
                                    <div class="stat-number">0s</div>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>Bu hafta +2s</span>
                                                </div>
                                                    </div>
                                                    </div>
                                                    </div>

                        <!-- Recent Activity -->
                        <div class="activity-section">
                            <h3>Son Aktiviteler</h3>
                            <div class="activity-list">
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-video"></i>
                                                        </div>
                                    <div class="activity-content">
                                        <p>Matematik videosu izlendi</p>
                                        <span class="activity-time">2 saat önce</span>
                                                </div>
                                            </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-bookmark"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>Video favorilere eklendi</p>
                                        <span class="activity-time">1 gün önce</span>
                                        </div>
                                </div>
                                </div>
                                </div>
                            </section>

                    <!-- Videos Section -->
                    <section id="videosSection" class="content-section">
                    <div class="section-header">
                            <h2>Matematik Videoları</h2>
                        <div class="section-actions">
                                <select class="form-select" id="gradeSelect">
                                    <option value="5">5. Sınıf</option>
                                    <option value="6">6. Sınıf</option>
                                    <option value="7">7. Sınıf</option>
                                    <option value="8">8. Sınıf</option>
                            </select>
                        </div>
                    </div>

                        <!-- Video Grid -->
                        <div class="video-grid" id="videoGrid">
                            <div class="loading-state">
                            <div class="spinner"></div>
                                <p>Videolar yükleniyor...</p>
                        </div>
                    </div>
                    </section>

                    <!-- Assignments Section -->
                    <section id="assignmentsSection" class="content-section">
                    <div class="section-header">
                            <h2>Ödevlerim</h2>
                        </div>
                        <div class="empty-state">
                            <i class="fas fa-tasks"></i>
                            <h3>Henüz ödev yok</h3>
                            <p>Öğretmenleriniz ödev verdiğinde burada görünecek</p>
                    </div>
                    </section>

                    <!-- Progress Section -->
                    <section id="progressSection" class="content-section">
                        <div class="section-header">
                            <h2>İlerleme Raporum</h2>
                        </div>
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <h3>İlerleme verisi yok</h3>
                            <p>Video izlemeye başladığınızda ilerleme raporunuz burada görünecek</p>
                    </div>
                    </section>

                    <!-- Bookmarks Section -->
                    <section id="bookmarksSection" class="content-section">
                    <div class="section-header">
                            <h2>Favori Videolarım</h2>
                        </div>
                        <div class="empty-state">
                            <i class="fas fa-bookmark"></i>
                            <h3>Henüz favori video yok</h3>
                            <p>Beğendiğiniz videoları favorilere ekleyin</p>
                    </div>
                    </section>
                        </div>
            </main>
                    </div>
                </div>

    <!-- Scripts -->
    <script src="assets/js/ui.js"></script>
<script src="assets/js/student-interactions.js"></script>
</body>
</html>
