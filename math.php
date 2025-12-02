<?php
/**
 * =====================================================
 * BİRLEŞİK MATEMATİK VİDEO SİSTEMİ (math.php)
 * Tüm sınıf seviyelerini tek dosyada yöneten sistem
 * Önceki 5_math.php, 6_math.php, 7_math.php, 8_math.php dosyalarının yerini alır
 *
 * Notlar:
 * - Bu dosyada; yetkilendirme, filtre okuma, veri hazırlama ve arayüz bölümleri
 *   başlıklar altında ayrılmıştır. Başlangıç seviyesine uygun açıklamalar
 *   eklenmiştir. Uygulama davranışı değiştirilmemiştir.
 * =====================================================
 */

// Oturum ve temel ayarları başlat
session_start();
require_once 'config.php';
require_once 'classes/MathVideoManager.php';

// =====================================================
// KULLANICI YETKİLENDİRME VE TEMEL DEĞİŞKENLER
// =====================================================

$role = $_SESSION['role'] ?? 'guest';           // Kullanıcı rolü (student, teacher, admin, guest)
$userId = $_SESSION['user_id'] ?? null;         // Kullanıcı ID'si
$userName = $_SESSION['username'] ?? 'Misafir'; // Kullanıcı adı

// Sınıf seviyesi kontrolü - URL'den grade parametresi al
$grade = $_GET['grade'] ?? '5';                 // Varsayılan 5. sınıf
$validGrades = ['5', '6', '7', '8'];            // Geçerli sınıf seviyeleri

// Geçersiz sınıf seviyesi kontrolü
if (!in_array($grade, $validGrades)) {
    $grade = '5'; // Geçersizse 5. sınıfa yönlendir
}

// =====================================================
// FİLTRE PARAMETRELERİNİ AL VE DOĞRULA
// =====================================================

$filters = [
    'q' => trim($_GET['q'] ?? ''),                    // Genel arama terimi
    'unit_id' => isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0, // Ünite ID'si
    'topic' => trim($_GET['topic'] ?? ''),            // Konu filtresi
    'status' => ($role === 'admin') ? ($_GET['status'] ?? '') : '' // Admin için durum filtresi
];

// =====================================================
// MATH VIDEO MANAGER'I BAŞLAT
// =====================================================

$mathManager = new MathVideoManager($conn, $userId, $role);

// =====================================================
// AJAX İSTEKLERİNİ İŞLE
// =====================================================

// AJAX istekleri için JSON response döndür
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'toggle_bookmark':
            // Video favorilere ekleme/çıkarma
            $videoId = (int)$_POST['video_id'];
            $notes = $_POST['notes'] ?? '';
            $result = $mathManager->toggleBookmark($videoId, $notes);
            echo json_encode($result);
            exit;
            
        case 'update_progress':
            // Video izleme ilerlemesi güncelleme
            $videoId = (int)$_POST['video_id'];
            $watchDuration = (int)$_POST['watch_duration'];
            $totalDuration = (int)$_POST['total_duration'];
            $result = $mathManager->updateVideoProgress($videoId, $watchDuration, $totalDuration);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'get_video_stats':
            // Video istatistiklerini getir
            $videoId = (int)$_POST['video_id'];
            $stats = $mathManager->getVideoStatistics($videoId);
            echo json_encode($stats);
            exit;
    }
}

// =====================================================
// SAYFA VERİLERİNİ HAZIRLA
// =====================================================

// Ünite listesini getir
$units = $mathManager->getUnitsForGrade($grade);

// Video listesini getir (filtreleme ile)
$videos = $mathManager->getVideosForGrade($grade, $filters);

// Öğretmen için kendi videolarını getir
$teacherVideos = [];
if ($role === 'teacher') {
    $teacherVideos = $mathManager->getTeacherVideos($grade);
}

// =====================================================
// SAYFA BAŞLIĞI VE META BİLGİLERİ
// =====================================================

$pageTitle = $grade . '. Sınıf Matematik';
$pageSubtitle = 'Matematik dersinde başarıya giden yol';

// 8. sınıf için özel LGS vurgusu
if ($grade === '8') {
    $pageTitle = '8. Sınıf Matematik (LGS)';
    $pageSubtitle = 'LGS sınavına hazırlık için özel içerikler';
}

// =====================================================
// PERFORMANS METRİKLERİ (Geliştirme amaçlı)
// =====================================================

$pageLoadStart = microtime(true);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?> - EğitimPlus</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($pageSubtitle) ?>. Matematik videoları, testler ve interaktif içerikler." />
    <meta name="keywords" content="matematik, <?= $grade ?>. sınıf, video dersler, online eğitim, test, quiz" />
    
    <!-- Force dark theme ASAP to avoid flash of light theme -->
    <script>
      (function(){
        try { localStorage.setItem('theme','dark'); } catch (e) {}
        var root = document.documentElement;
        root.setAttribute('data-theme','dark');
      })();
    </script>

    <!-- External CSS Libraries -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Theme tokens and shared components -->
    <link href="assets/css/tokens.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">

    <!-- Custom CSS - Ortak stil dosyası -->
    <link href="assets/css/math-styles.css" rel="stylesheet">
    
    <!-- Progressive Web App Support -->
    <meta name="theme-color" content="#667eea">
    <link rel="manifest" href="manifest.json">
    
</head>

<body>
    <!-- Navigation Bar -->
    <?php include 'navbar.php'; ?>
    
    <!-- Main Container -->
    <div class="container">
        
        <!-- =====================================================
             SAYFA BAŞLIĞI VE SINIF SEÇİCİ
             ===================================================== -->
        <div class="page-header">
            <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
            
            <!-- 8. sınıf için LGS badge -->
            <?php if ($grade === '8'): ?>
                <div class="lgs-badge">
                    <i class="fas fa-star"></i> LGS Hazırlık
                </div>
            <?php endif; ?>
            
            <!-- Sınıf Seçici -->
            <div class="grade-selector">
                <label for="grade-select">Sınıf Seçin:</label>
                <select id="grade-select" onchange="changeGrade(this.value)">
                    <?php foreach ($validGrades as $g): ?>
                        <option value="<?= $g ?>" <?= $g === $grade ? 'selected' : '' ?>>
                            <?= $g ?>. Sınıf
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Öğretmen için video yükleme butonu -->
            <?php if ($role === 'teacher'): ?>
                <a href="teacher_panel.php" class="upload-button">
                    <i class="fas fa-plus"></i>
                    Video Yükle
                </a>
            <?php endif; ?>
        </div>
        
        <!-- =====================================================
             FİLTRELEME TOOLBAR'I
             ===================================================== -->
        <form class="toolbar" method="GET" id="filter-form">
            <input type="hidden" name="grade" value="<?= htmlspecialchars($grade) ?>" />
            
            <!-- Genel arama -->
            <div class="filter-group">
                <label for="search-input">Ara:</label>
                <input type="text" 
                       id="search-input"
                       name="q" 
                       value="<?= htmlspecialchars($filters['q']) ?>" 
                       placeholder="Başlık, konu veya açıklama ara..." />
            </div>
            
            <!-- Ünite seçici -->
            <div class="filter-group">
                <label for="unit-select">Ünite:</label>
                <select name="unit_id" id="unit-select">
                    <option value="0">Tüm Üniteler</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= (int)$unit['id'] ?>" 
                                <?= ($filters['unit_id'] === (int)$unit['id']) ? 'selected' : '' ?>>
                            <?= (int)$unit['unit_order'] ?>. <?= htmlspecialchars($unit['unit_name']) ?>
                            (<?= $unit['video_count'] ?> video)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Konu filtresi -->
            <div class="filter-group">
                <label for="topic-input">Konu:</label>
                <input type="text" 
                       id="topic-input"
                       name="topic" 
                       value="<?= htmlspecialchars($filters['topic']) ?>" 
                       placeholder="Belirli bir konu ara..." />
            </div>
            
            <!-- Admin için durum filtresi -->
            <?php if ($role === 'admin'): ?>
                <div class="filter-group">
                    <label for="status-select">Durum:</label>
                    <select name="status" id="status-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>
                            Bekleyen
                        </option>
                        <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>
                            Onaylı
                        </option>
                        <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>
                            Reddedilen
                        </option>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Filtre uygula butonu -->
            <button class="filter-button" type="submit">
                <i class="fas fa-filter"></i>
                Filtrele
            </button>
            
            <!-- Filtreleri temizle butonu -->
            <button type="button" class="clear-filters-button" onclick="clearFilters()">
                <i class="fas fa-times"></i>
                Temizle
            </button>
        </form>
        
        <!-- =====================================================
             ÖĞRETMEN İÇİN SEKME SİSTEMİ
             ===================================================== -->
        <?php if ($role === 'teacher'): ?>
            <div class="tabs">
                <button class="tab active" onclick="showTab('explore')">
                    <i class="fas fa-compass"></i> Keşfet
                </button>
                <button class="tab" onclick="showTab('my-videos')">
                    <i class="fas fa-video"></i> 
                    Videolarım (<?= count($teacherVideos) ?>)
                </button>
                <button class="tab" onclick="showTab('statistics')">
                    <i class="fas fa-chart-bar"></i> İstatistikler
                </button>
            </div>
        <?php endif; ?>
        
        <!-- =====================================================
             ANA VİDEO LİSTESİ - ÜNİTE ÜNİTE GRUPLANDIRMA
             ===================================================== -->
        <div id="explore-content" class="tab-content active">
            
            <!-- Sonuç sayısı ve sıralama bilgisi -->
            <div class="results-info">
                <span class="result-count">
                    <i class="fas fa-video"></i>
                    <?= count($videos) ?> video bulundu
                </span>
                
                <?php if (!empty($filters['q']) || !empty($filters['topic']) || $filters['unit_id'] > 0): ?>
                    <span class="active-filters">
                        <i class="fas fa-filter"></i>
                        Filtre aktif
                        <button onclick="clearFilters()" class="clear-filter-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Video listesi - Ünite bazlı gruplama -->
            <div class="video-sections">
                <?php 
                // Videoları ünite ID'sine göre grupla
                $videosByUnit = [];
                foreach ($videos as $video) {
                    $unitId = (int)($video['unit_id'] ?? 0);
                    if (!isset($videosByUnit[$unitId])) {
                        $videosByUnit[$unitId] = [];
                    }
                    $videosByUnit[$unitId][] = $video;
                }

                // Mevcut ünite ID'leri seti
                $knownUnitIds = array_map(function($u){ return (int)$u['id']; }, $units);
                $knownUnitIds = array_flip($knownUnitIds); // hızlı lookup

                // Her ünite için video listesini göster
                foreach ($units as $unit): 
                    $unitId = (int)$unit['id'];
                    $unitVideos = $videosByUnit[$unitId] ?? [];
                ?>
                    <section class="unit-section" data-unit-id="<?= $unitId ?>">
                        <div class="unit-header">
                            <h3 class="unit-title">
                                <i class="fas fa-book"></i>
                                <?= (int)$unit['unit_order'] ?>. <?= htmlspecialchars($unit['unit_name']) ?>
                                <span class="video-count-badge"><?= count($unitVideos) ?> video</span>
                            </h3>
                            
                            <?php if (!empty($unit['description'])): ?>
                                <p class="unit-description"><?= htmlspecialchars($unit['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="video-grid">
                            <?php if (!empty($unitVideos)): ?>
                                <?php foreach ($unitVideos as $video): ?>
                                    <div class="video-card" data-video-id="<?= (int)$video['id'] ?>">
                                        
                                        <!-- Video Thumbnail (Playback video_view.php sayfasında) -->
                                        <div class="video-player">
                                            <?php 
                                                $yt = $video['youtube_url'] ?? '';
                                                $ytId = null;
                                                if ($yt) {
                                                    if (preg_match('~[?&]v=([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                    elseif (preg_match('~youtu\.be/([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                    elseif (preg_match('~/shorts/([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                    elseif (preg_match('~/embed/([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                    elseif (preg_match('~youtube\.com/(?:v|embed)/([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                }
                                                $thumbUrl = $ytId ? ('https://i.ytimg.com/vi/' . $ytId . '/hqdefault.jpg') : 'photos/images.jpg';
                                            ?>
                                            <a class="video-thumb-link" href="video_view.php?id=<?= (int)$video['id'] ?>" title="Videoyu aç" style="position:relative;display:block;">
                                                <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="<?= htmlspecialchars($video['title']) ?>" style="width:100%;height:auto;border-radius:8px;display:block;"/>
                                                <div class="play-overlay" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;">
                                                    <span style="background:rgba(0,0,0,0.45);border-radius:50%;width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
                                                        <i class="fas fa-play" style="font-size:22px;"></i>
                                                    </span>
                                                </div>
                                            </a>
                                            
                                            <!-- Video üzerindeki kontroller -->
                                            <div class="video-controls">
                                                <!-- Bookmark butonu -->
                                                <button class="bookmark-btn <?= $video['is_bookmarked'] ? 'bookmarked' : '' ?>" 
                                                        onclick="toggleBookmark(<?= $video['id'] ?>)"
                                                        title="Favorilere ekle/çıkar">
                                                    <i class="fas fa-bookmark"></i>
                                                </button>
                                                
                                                <!-- İlerleme göstergesi -->
                                                <?php if ($video['user_progress']): ?>
                                                    <div class="progress-indicator" 
                                                         title="İlerleme: %<?= number_format($video['user_progress']['completion_percentage'], 0) ?>">
                                                        <div class="progress-bar" 
                                                             style="width: <?= $video['user_progress']['completion_percentage'] ?>%">
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Video İçerik Bilgileri -->
                                        <div class="video-content">
                                            <h4 class="video-title">
                                                <?= htmlspecialchars($video['title']) ?>
                                            </h4>
                                            
                                            <div class="video-meta">
                                                <!-- Konu etiketi -->
                                                <div class="video-topic">
                                                    <i class="fas fa-tag"></i> 
                                                    <?= htmlspecialchars($video['topic'] ?? 'Konu belirtilmemiş') ?>
                                                </div>
                                                
                                                <!-- Video istatistikleri -->
                                                <div class="video-stats">
                                                    <?php if ($video['view_count'] > 0): ?>
                                                        <span class="stat-item">
                                                            <i class="fas fa-eye"></i> 
                                                            <?= number_format($video['view_count']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($video['completion_rate'] > 0): ?>
                                                        <span class="stat-item">
                                                            <i class="fas fa-check-circle"></i> 
                                                            %<?= number_format($video['completion_rate'], 0) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Durum ve aksiyonlar -->
                                            <div class="video-actions">
                                                <!-- Durum badge'i -->
                                                <span class="status-badge status-<?= $video['status'] ?>">
                                                    <?= ucfirst($video['status']) ?>
                                                </span>
                                                
                                                <!-- Quiz varsa göster -->
                                                <?php if ($video['quiz_available']): ?>
                                                    <button class="quiz-btn" onclick="openQuiz(<?= $video['id'] ?>)">
                                                        <i class="fas fa-question-circle"></i> Test Çöz
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Not alma butonu -->
                                                <button class="notes-btn" onclick="openNotes(<?= $video['id'] ?>)">
                                                    <i class="fas fa-sticky-note"></i> Not Al
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Boş durum mesajı -->
                                <div class="empty-state">
                                    <i class="fas fa-video-slash"></i>
                                    <h3>Bu ünite için video bulunamadı</h3>
                                    <p>
                                        <?php if (!empty($filters['q']) || !empty($filters['topic'])): ?>
                                            Arama kriterlerinizi değiştirmeyi deneyin
                                        <?php else: ?>
                                            Öğretmenler video yükledikçe burada görünecek
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <?php 
                // Üniteye bağlı olmayan/bilinmeyen ünite videolarını topla
                $otherVideos = [];
                foreach ($videosByUnit as $uid => $list) {
                    $uidInt = (int)$uid;
                    if ($uidInt === 0 || !isset($knownUnitIds[$uidInt])) {
                        foreach ($list as $v) { $otherVideos[] = $v; }
                    }
                }
                if (!empty($otherVideos)):
                ?>
                    <section class="unit-section" data-unit-id="0">
                        <div class="unit-header">
                            <h3 class="unit-title">
                                <i class="fas fa-book"></i>
                                Genel Videolar
                                <span class="video-count-badge"><?= count($otherVideos) ?> video</span>
                            </h3>
                        </div>
                        <div class="video-grid">
                            <?php foreach ($otherVideos as $video): ?>
                                <div class="video-card" data-video-id="<?= (int)$video['id'] ?>">
                                    <div class="video-player">
                                        <?php 
                                            $yt = $video['youtube_url'] ?? '';
                                            $ytId = null;
                                            if ($yt) {
                                                if (preg_match('~[?&]v=([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                elseif (preg_match('~youtu\.be/([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                elseif (preg_match('~/shorts/([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                elseif (preg_match('~/embed/([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                                elseif (preg_match('~youtube\.com/(?:v|embed)/([a-zA-Z0-9_-]{6,})~', $yt, $m)) { $ytId = $m[1]; }
                                            }
                                            $thumbUrl = $ytId ? ('https://i.ytimg.com/vi/' . $ytId . '/hqdefault.jpg') : 'photos/images.jpg';
                                        ?>
                                        <a class="video-thumb-link" href="video_view.php?id=<?= (int)$video['id'] ?>" title="Videoyu aç" style="position:relative;display:block;">
                                            <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="<?= htmlspecialchars($video['title']) ?>" style="width:100%;height:auto;border-radius:8px;display:block;"/>
                                            <div class="play-overlay" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;">
                                                <span style="background:rgba(0,0,0,0.45);border-radius:50%;width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
                                                    <i class="fas fa-play" style="font-size:22px;"></i>
                                                </span>
                                            </div>
                                        </a>
                                        <div class="video-controls">
                                            <button class="bookmark-btn <?= $video['is_bookmarked'] ? 'bookmarked' : '' ?>" 
                                                    onclick="toggleBookmark(<?= $video['id'] ?>)"
                                                    title="Favorilere ekle/çıkar">
                                                <i class="fas fa-bookmark"></i>
                                            </button>
                                            <?php if (!empty($video['user_progress'])): ?>
                                                <div class="progress-indicator" 
                                                     title="İlerleme: %<?= number_format($video['user_progress']['completion_percentage'], 0) ?>">
                                                    <div class="progress-bar" 
                                                         style="width: <?= $video['user_progress']['completion_percentage'] ?>%"></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="video-content">
                                        <h4 class="video-title"><?= htmlspecialchars($video['title']) ?></h4>
                                        <div class="video-meta">
                                            <div class="video-topic"><i class="fas fa-tag"></i> <?= htmlspecialchars($video['topic'] ?? 'Konu belirtilmemiş') ?></div>
                                            <div class="video-stats">
                                                <?php if (!empty($video['view_count'])): ?>
                                                    <span class="stat-item"><i class="fas fa-eye"></i> <?= number_format((int)$video['view_count']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($video['completion_rate'])): ?>
                                                    <span class="stat-item"><i class="fas fa-check-circle"></i> %<?= number_format((float)$video['completion_rate'], 0) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="video-actions">
                                            <span class="status-badge status-<?= $video['status'] ?>"><?= ucfirst($video['status']) ?></span>
                                            <?php if (!empty($video['quiz_available'])): ?>
                                                <button class="quiz-btn" onclick="openQuiz(<?= $video['id'] ?>)"><i class="fas fa-question-circle"></i> Test Çöz</button>
                                            <?php endif; ?>
                                            <button class="notes-btn" onclick="openNotes(<?= $video['id'] ?>)"><i class="fas fa-sticky-note"></i> Not Al</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- =====================================================
             ÖĞRETMEN VİDEOLARI SEKMESİ
             ===================================================== -->
        <?php if ($role === 'teacher'): ?>
            <div id="my-videos-content" class="tab-content">
                <div class="teacher-videos-header">
                    <h3>
                        <i class="fas fa-user-graduate"></i>
                        Videolarım
                    </h3>
                    <p>Yüklediğiniz videoların durumu ve performansı</p>
                </div>
                
                <div class="video-grid">
                    <?php if (!empty($teacherVideos)): ?>
                        <?php foreach ($teacherVideos as $video): ?>
                            <div class="video-card teacher-video">
                                <div class="video-header">
                                    <h4 class="video-title"><?= htmlspecialchars($video['title']) ?></h4>
                                    <span class="status-badge status-<?= $video['status'] ?>">
                                        <?= ucfirst($video['status']) ?>
                                    </span>
                                </div>
                                
                                <div class="video-info">
                                    <div class="info-row">
                                        <i class="fas fa-book"></i> 
                                        <?= htmlspecialchars($video['unit'] ?? 'Ünite belirtilmemiş') ?>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-tag"></i> 
                                        <?= htmlspecialchars($video['topic'] ?? 'Konu belirtilmemiş') ?>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-calendar"></i> 
                                        <?= date('d.m.Y', strtotime($video['created_at'])) ?>
                                    </div>
                                </div>
                                
                                <?php if ($video['total_views'] > 0): ?>
                                    <div class="video-performance">
                                        <div class="perf-item">
                                            <span class="perf-label">Görüntülenme:</span>
                                            <span class="perf-value"><?= number_format($video['total_views']) ?></span>
                                        </div>
                                        <div class="perf-item">
                                            <span class="perf-label">Tamamlanma:</span>
                                            <span class="perf-value">%<?= number_format($video['completion_rate'], 0) ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($video['youtube_url']): ?>
                                    <div class="video-player small">
                                        <iframe src="<?= htmlspecialchars($video['youtube_url']) ?>" 
                                                frameborder="0" allowfullscreen loading="lazy">
                                        </iframe>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-video-slash"></i>
                            <h3>Henüz video yüklememişsiniz</h3>
                            <p>İlk videonuzu yüklemek için "Video Yükle" butonuna tıklayın</p>
                            <a href="teacher_panel.php" class="upload-button">
                                <i class="fas fa-plus"></i>
                                Video Yükle
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- =====================================================
         MODAL VE POPUP'LAR
         ===================================================== -->
    
    <!-- Quiz Modal -->
    <div id="quiz-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Video Testi</h3>
                <button class="close-btn" onclick="closeModal('quiz-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="quiz-content">
                <!-- Quiz içeriği buraya yüklenecek -->
            </div>
        </div>
    </div>
    
    <!-- Not Alma Modal -->
    <div id="notes-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Video Notları</h3>
                <button class="close-btn" onclick="closeModal('notes-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="notes-content">
                <!-- Not alma arayüzü buraya yüklenecek -->
            </div>
        </div>
    </div>
    
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner">
        <div class="spinner"></div>
        <p>Yükleniyor...</p>
    </div>
    
    <!-- =====================================================
         JAVASCRIPT - İNTERAKTİF ÖZELLİKLER
         ===================================================== -->
    <script src="assets/js/math-interactions.js"></script>
    
    <script>
        // Sayfa yükleme performansını ölç
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log('Sayfa yükleme süresi:', Math.round(loadTime), 'ms');
            
            // Geliştirme modunda performans bilgilerini göster
            <?php if (isset($_GET['debug'])): ?>
                const phpLoadTime = <?= round((microtime(true) - $pageLoadStart) * 1000) ?>;
                console.log('PHP işleme süresi:', phpLoadTime, 'ms');
                console.log('Toplam video sayısı:', <?= count($videos) ?>);
                console.log('Cache kullanım durumu: Aktif');
            <?php endif; ?>
        });
        
        // Sınıf değiştirme fonksiyonu
        function changeGrade(newGrade) {
            const url = new URL(window.location);
            url.searchParams.set('grade', newGrade);
            window.location.href = url.toString();
        }
        
        // Filtreleri temizleme fonksiyonu
        function clearFilters() {
            const url = new URL(window.location);
            url.searchParams.delete('q');
            url.searchParams.delete('unit_id');
            url.searchParams.delete('topic');
            url.searchParams.delete('status');
            window.location.href = url.toString();
        }
    </script>
    
    <!-- Performance monitoring (sadece geliştirme ortamında) -->
    <?php if (isset($_GET['debug'])): ?>
        <div class="debug-info">
            <h4>Debug Bilgileri</h4>
            <p>PHP İşleme Süresi: <?= round((microtime(true) - $pageLoadStart) * 1000) ?> ms</p>
            <p>Toplam Video: <?= count($videos) ?></p>
            <p>Toplam Ünite: <?= count($units) ?></p>
            <p>Aktif Filtreler: <?= json_encode(array_filter($filters)) ?></p>
        </div>
    <?php endif; ?>
    <script src="assets/js/ui.js"></script>
</body>
</html>
