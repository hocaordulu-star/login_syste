<?php
/**
 * Unified Admin Panel System
 * Modern, comprehensive admin dashboard
 *
 * Bu dosya admin kullanıcılarının eriştiği birleşik yönetim panelidir.
 * Başlıca sorumluluklar:
 * - Oturum ve rol kontrolü (sadece admin)
 * - AJAX/JSON tabanlı eylemler (kullanıcı ve video operasyonları)
 * - Bölüm bazlı sayfa içeriklerinin (dashboard/users/videos/…) yüklenmesi
 * Not: Bu yorumlar yalnızca açıklama amaçlıdır, uygulama davranışını değiştirmez.
 */
session_start();
require_once 'config.php';
require_once 'classes/AdminManager.php';

// Admin access control
// Güvenlik: Admin olmayan kullanıcıları ana sayfaya yönlendirir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];
$adminManager = new AdminManager($conn, $adminId);

// Get current section
$section = $_GET['section'] ?? 'dashboard';
$page = (int)($_GET['page'] ?? 1);

// Handle AJAX/API requests (supports both GET and POST with 'action')
// Açıklama: İstemciden ?action=... veya POST action alanı ile gelen istekler
// JSON olarak cevaplanır ve sayfa render edilmeden önce sonlandırılır.
if (isset($_REQUEST['action'])) {
    // Ensure clean JSON responses for AJAX endpoints
    // Disable error display to avoid corrupting JSON; log errors instead
    @ini_set('display_errors', '0');
    @error_reporting(E_ALL);
    // Clean any previous output buffers to prevent stray whitespace/text
    while (ob_get_level() > 0) { @ob_end_clean(); }

    // Register a shutdown handler to catch fatal errors and return JSON
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500);
            }
            echo json_encode([
                'success' => false,
                'message' => 'Sunucu hatası',
                'error' => 'Fatal: ' . $error['message']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    });

    header('Content-Type: application/json');
    try {
        $action = $_REQUEST['action'];
        switch ($action) {
            // Dashboard istatistiklerini döndürür (kullanıcı/video sayıları vs.)
            case 'get_dashboard_stats':
                echo json_encode(['success' => true, 'data' => $adminManager->getDashboardStats()]);
                break;

            // Tekil kullanıcı detayını ve role özgü profil bilgisini getirir
            case 'get_user':
                $userIdParam = (int)($_REQUEST['user_id'] ?? 0);
                if ($userIdParam <= 0) { echo json_encode(['success' => false, 'error' => 'Geçersiz kullanıcı']); break; }
                $stmt = $conn->prepare('SELECT id, first_name, last_name, email, phone, role, status, created_at, last_login FROM users WHERE id = ?');
                $stmt->bind_param('i', $userIdParam);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$user) { echo json_encode(['success' => false, 'error' => 'Kullanıcı bulunamadı']); break; }
                // Role-specific profile
                if ($user['role'] === 'student') {
                    $ps = $conn->prepare('SELECT school, grade FROM students WHERE user_id = ?');
                    $ps->bind_param('i', $userIdParam);
                    $ps->execute();
                    $user['profile'] = $ps->get_result()->fetch_assoc() ?: [];
                    $ps->close();
                } elseif ($user['role'] === 'teacher') {
                    $pt = $conn->prepare('SELECT school, department, experience_years FROM teachers WHERE user_id = ?');
                    $pt->bind_param('i', $userIdParam);
                    $pt->execute();
                    $user['profile'] = $pt->get_result()->fetch_assoc() ?: [];
                    $pt->close();
                } else {
                    $user['profile'] = [];
                }
                echo json_encode(['success' => true, 'user' => $user]);
                break;

            // Kullanıcı temel alanlarını günceller; role göre students/teachers tablolarına upsert yapar
            case 'update_user':
                $userIdParam = (int)($_POST['user_id'] ?? 0);
                if ($userIdParam <= 0) { echo json_encode(['success' => false, 'error' => 'Geçersiz kullanıcı']); break; }
                $first = trim($_POST['first_name'] ?? '');
                $last  = trim($_POST['last_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $role  = trim($_POST['role'] ?? '');
                $status= trim($_POST['status'] ?? '');
                if ($first === '' || $email === '' || $role === '') { echo json_encode(['success' => false, 'error' => 'Zorunlu alanlar eksik']); break; }
                // unique email check
                $chk = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
                $chk->bind_param('si', $email, $userIdParam);
                $chk->execute();
                $exists = $chk->get_result()->fetch_assoc();
                $chk->close();
                if ($exists) { echo json_encode(['success' => false, 'error' => 'E-posta kullanımda']); break; }
                // update users
                $up = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, status = ? WHERE id = ?');
                $up->bind_param('sssssi', $first, $last, $email, $phone, $status, $userIdParam);
                $ok1 = $up->execute();
                $up->close();
                // role-specific upsert
                if ($role === 'student') {
                    $school = trim($_POST['school'] ?? '');
                    $grade  = trim($_POST['grade'] ?? '');
                    $has = $conn->prepare('SELECT user_id FROM students WHERE user_id = ?');
                    $has->bind_param('i', $userIdParam);
                    $has->execute();
                    $row = $has->get_result()->fetch_assoc();
                    $has->close();
                    if ($row) {
                        $us = $conn->prepare('UPDATE students SET school = ?, grade = ? WHERE user_id = ?');
                        $schoolParam = ($school === '') ? null : $school;
                        $gradeParam = ($grade === '') ? null : $grade;
                        $us->bind_param('ssi', $schoolParam, $gradeParam, $userIdParam);
                        $us->execute();
                        $us->close();
                    } else {
                        $is = $conn->prepare('INSERT INTO students (user_id, school, grade) VALUES (?, ?, ?)');
                        $schoolParam = ($school === '') ? null : $school;
                        $gradeParam = ($grade === '') ? null : $grade;
                        $is->bind_param('iss', $userIdParam, $schoolParam, $gradeParam);
                        $is->execute();
                        $is->close();
                    }
                } elseif ($role === 'teacher') {
                    $school = trim($_POST['school'] ?? '');
                    $department = trim($_POST['department'] ?? '');
                    $experience_years = (int)($_POST['experience_years'] ?? 0);
                    $has = $conn->prepare('SELECT user_id FROM teachers WHERE user_id = ?');
                    $has->bind_param('i', $userIdParam);
                    $has->execute();
                    $row = $has->get_result()->fetch_assoc();
                    $has->close();
                    if ($row) {
                        $ut = $conn->prepare('UPDATE teachers SET school = ?, department = ?, experience_years = ? WHERE user_id = ?');
                        $schoolParam = ($school === '') ? null : $school;
                        $deptParam = ($department === '') ? null : $department;
                        $ut->bind_param('ssii', $schoolParam, $deptParam, $experience_years, $userIdParam);
                        $ut->execute();
                        $ut->close();
                    } else {
                        $it = $conn->prepare('INSERT INTO teachers (user_id, school, department, experience_years) VALUES (?, ?, ?, ?)');
                        $schoolParam = ($school === '') ? null : $school;
                        $deptParam = ($department === '') ? null : $department;
                        $it->bind_param('issi', $userIdParam, $schoolParam, $deptParam, $experience_years);
                        $it->execute();
                        $it->close();
                    }
                }
                echo json_encode(['success' => (bool)$ok1]);
                break;

            // approve/reject/delete gibi tekil kullanıcı işlemlerini eşler ve çalıştırır
            case 'user_action':
                // Map frontend actions to backend operations for single user
                $userId = (int)($_REQUEST['user_id'] ?? 0);
                $operation = $_REQUEST['operation'] ?? '';
                if (!$userId || !$operation) {
                    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
                    break;
                }
                $map = [
                    'approve' => 'activate',
                    'reject' => 'deactivate',
                    'delete' => 'delete',
                ];
                if (!isset($map[$operation])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid operation']);
                    break;
                }
                $success = $adminManager->bulkUserOperation([$userId], $map[$operation]);
                echo json_encode(['success' => (bool)$success]);
                break;

            // Çoklu kullanıcı işlemleri (toplu onay/red/silme vs.)
            case 'bulk_user_operation':
                $userIds = isset($_REQUEST['user_ids']) ? json_decode($_REQUEST['user_ids'], true) : [];
                $operation = $_REQUEST['operation'] ?? '';
                $data = isset($_REQUEST['data']) ? json_decode($_REQUEST['data'], true) : [];
                $result = $adminManager->bulkUserOperation($userIds, $operation, $data);
                echo json_encode(['success' => (bool)$result]);
                break;

            // Tekil video operasyonu (approve/reject/delete), gerekirse reason ile
            case 'video_action':
                $videoId = (int)($_REQUEST['video_id'] ?? 0);
                $operation = $_REQUEST['operation'] ?? '';
                $reason = $_REQUEST['reason'] ?? '';
                if (!$videoId || !$operation) {
                    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
                    break;
                }
                $data = [];
                if ($operation === 'reject') { $data['reason'] = $reason; }
                $result = $adminManager->bulkVideoOperation([$videoId], $operation, $data);
                echo json_encode(['success' => (bool)$result]);
                break;

            // Çoklu video operasyonu (toplu onay/red/silme vs.)
            case 'bulk_video_operation':
                $videoIds = isset($_REQUEST['video_ids']) ? json_decode($_REQUEST['video_ids'], true) : [];
                $operation = $_REQUEST['operation'] ?? '';
                $data = isset($_REQUEST['data']) ? json_decode($_REQUEST['data'], true) : [];
                $result = $adminManager->bulkVideoOperation($videoIds, $operation, $data);
                echo json_encode(['success' => (bool)$result]);
                break;

            // Öğretmen video düzenleme talebini onaylar
            case 'approve_video_request':
                $requestId = (int)($_REQUEST['request_id'] ?? 0);
                $adminNote = $_REQUEST['admin_note'] ?? '';
                if ($requestId <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Geçersiz talep ID']);
                    break;
                }
                // Validate request exists and is pending
                $chk = $conn->prepare("SELECT status FROM video_edit_requests WHERE id = ?");
                $chk->bind_param('i', $requestId);
                $chk->execute();
                $row = $chk->get_result()->fetch_assoc();
                $chk->close();
                if (!$row) { echo json_encode(['success' => false, 'message' => 'Talep bulunamadı']); break; }
                if (($row['status'] ?? '') !== 'pending') { echo json_encode(['success' => false, 'message' => 'Talep zaten sonuçlanmış']); break; }
                $result = $adminManager->approveVideoEditRequest($requestId, $adminNote);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Talep onaylandı']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'İşlem başarısız', 'error' => $adminManager->getLastError()]);
                }
                break;

            // Öğretmen video düzenleme talebini reddeder
            case 'reject_video_request':
                $requestId = (int)($_REQUEST['request_id'] ?? 0);
                $adminNote = $_REQUEST['admin_note'] ?? '';
                if ($requestId <= 0) { echo json_encode(['success' => false, 'message' => 'Geçersiz talep ID']); break; }
                $chk = $conn->prepare("SELECT status FROM video_edit_requests WHERE id = ?");
                $chk->bind_param('i', $requestId);
                $chk->execute();
                $row = $chk->get_result()->fetch_assoc();
                $chk->close();
                if (!$row) { echo json_encode(['success' => false, 'message' => 'Talep bulunamadı']); break; }
                if (($row['status'] ?? '') !== 'pending') { echo json_encode(['success' => false, 'message' => 'Talep zaten sonuçlanmış']); break; }
                $result = $adminManager->rejectVideoEditRequest($requestId, $adminNote);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Talep reddedildi']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'İşlem başarısız', 'error' => $adminManager->getLastError()]);
                }
                break;

            // Belirli bir video düzenleme talebinin detaylarını getirir (talep + mevcut video bilgisi)
            case 'get_video_request':
                $requestId = (int)($_REQUEST['request_id'] ?? 0);
                $stmt = $conn->prepare("
                    SELECT ver.*, v.title as current_title, v.description as current_description,
                           u.first_name, u.last_name, u.email
                    FROM video_edit_requests ver
                    JOIN videos v ON ver.video_id = v.id
                    JOIN users u ON ver.requester_id = u.id
                    WHERE ver.id = ?
                ");
                $stmt->bind_param('i', $requestId);
                $stmt->execute();
                $request = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                echo json_encode(['success' => (bool)$request, 'request' => $request]);
                break;

            // Placeholder: Kullanıcı aktivitesi grafik datası (gelecekte genişletilebilir)
            case 'get_user_activity':
                echo json_encode(['success' => true, 'data' => ['labels' => [], 'values' => []]]);
                break;
            // Placeholder: Video analitik grafik datası (gelecekte genişletilebilir)
            case 'get_video_analytics':
                echo json_encode(['success' => true, 'data' => ['labels' => [], 'views' => []]]);
                break;

            case 'remove_local_video':
                $videoId = (int)($_REQUEST['video_id'] ?? 0);
                if ($videoId <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Geçersiz video']);
                    break;
                }
                $res = $adminManager->removeLocalVideoFile($videoId);
                echo json_encode([
                    'success' => (bool)($res['success'] ?? false),
                    'message' => $res['message'] ?? '',
                    'deleted' => (bool)($res['deleted'] ?? false)
                ]);
                break;

            // Video YouTube URL güncellemesi: farklı URL biçimlerinden embed URL'ye normalize eder
            case 'update_video_youtube':
                $videoId = (int)($_REQUEST['video_id'] ?? 0);
                $youtubeUrl = trim($_REQUEST['youtube_url'] ?? '');
                if ($videoId <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Geçersiz video']);
                    break;
                }
                // Normalize to embed URL when provided
                // Açıklama: watch?v=, youtu.be/, shorts/, embed/ gibi yaygın kalıpları tespit eder.
                if ($youtubeUrl !== '') {
                    $id = null;
                    $u = $youtubeUrl;
                    // Common patterns: watch?v=, youtu.be/, shorts/, embed/
                    // 1) Query param v
                    if (preg_match('~[?&]v=([a-zA-Z0-9_-]{6,})~', $u, $m)) {
                        $id = $m[1];
                    }
                    // 2) youtu.be/<id>
                    if (!$id && preg_match('~youtu\.be/([a-zA-Z0-9_-]{6,})~', $u, $m)) {
                        $id = $m[1];
                    }
                    // 3) /shorts/<id>
                    if (!$id && preg_match('~/shorts/([a-zA-Z0-9_-]{6,})~', $u, $m)) {
                        $id = $m[1];
                    }
                    // 4) /embed/<id>
                    if (!$id && preg_match('~/embed/([a-zA-Z0-9_-]{6,})~', $u, $m)) {
                        $id = $m[1];
                    }
                    // 5) m.youtube.com or www.youtube.com/<id> fallback
                    if (!$id && preg_match('~youtube\.com/(?:v|embed)/([a-zA-Z0-9_-]{6,})~', $u, $m)) {
                        $id = $m[1];
                    }
                    if ($id) {
                        $youtubeUrl = 'https://www.youtube.com/embed/' . $id;
                    }
                }
                // Allow empty to clear
                $stmt = $conn->prepare("UPDATE videos SET youtube_url = ? WHERE id = ?");
                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'SQL hatası: ' . $conn->error]);
                    break;
                }
                $stmt->bind_param('si', $youtubeUrl, $videoId);
                $ok = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => (bool)$ok]);
                break;


            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get data based on section
// Açıklama: Sayfa URL'sindeki section parametresine göre liste verileri hazırlanır
$data = [];
$filters = [];

switch ($section) {
    case 'users':
        $filters = [
            'status' => $_GET['status'] ?? '',
            'role' => $_GET['role'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        $data = $adminManager->getUsers($filters, $page, 20);
        break;
        
    case 'videos':
        $videos = $adminManager->getVideos($_GET, $page, 20);
        break;
        
    case 'video_requests':
        $videoRequests = $adminManager->getPendingVideoEditRequests($page, 20);
        break;
        
    
        
    default:
        $data['dashboard_stats'] = $adminManager->getDashboardStats();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= ucfirst($section) ?> | Eğitim Sistemi</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-styles.css" rel="stylesheet">
    <link href="assets/css/tokens.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <meta name="theme-color" content="#667eea">
</head>
<body class="admin-panel" data-section="<?= htmlspecialchars($section) ?>">
    
    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner"></div>
        <p>Yükleniyor...</p>
    </div>
    
    <?php include 'navbar.php'; ?>
    
    <!-- Ana layout: sol sidebar + sağ ana içerik -->
    <div class="admin-layout">
        
        <!-- Sidebar: bölüm navigasyonu ve bekleyen talep rozeti -->
        <aside id="appSidebar" class="admin-sidebar sidebar" aria-label="Yan menü" aria-hidden="true">
            <div class="sidebar-header">
                <div class="admin-brand">
                    <div class="brand-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="brand-text">
                        <h3>Admin Panel</h3>
                        <span>Gelişmiş Yönetim</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item <?= $section === 'dashboard' ? 'active' : '' ?>">
                        <a href="?section=dashboard" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-item <?= $section === 'users' ? 'active' : '' ?>">
                        <a href="?section=users" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Kullanıcılar</span>
                        </a>
                    </li>
                    
                    <li class="nav-item <?= $section === 'videos' ? 'active' : '' ?>">
                        <a href="?section=videos" class="nav-link">
                            <i class="fas fa-video"></i>
                            <span>Videolar</span>
                        </a>
                    </li>
                    <li class="nav-item <?= $section === 'video_requests' ? 'active' : '' ?>">
                        <a href="?section=video_requests" class="nav-link">
                            <i class="fas fa-edit"></i>
                            <span>Video Talepleri</span>
                            <?php
                            $pendingCount = $adminManager->getPendingVideoEditRequests(1, 1)['total'] ?? 0;
                            if ($pendingCount > 0): ?>
                                <span class="badge"><?= $pendingCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="inbox.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span>Mesajlar</span>
                            <?php
                            $msgStmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0 AND is_deleted = 0");
                            $msgStmt->bind_param('i', $adminId);
                            $msgStmt->execute();
                            $msgCount = $msgStmt->get_result()->fetch_assoc()['count'] ?? 0;
                            $msgStmt->close();
                            if ($msgCount > 0): ?>
                                <span class="badge"><?= $msgCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item <?= $section === 'system' ? 'active' : '' ?>">
                        <a href="?section=system" class="nav-link">
                            <i class="fas fa-cogs"></i>
                            <span>Sistem</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Ana içerik alanı: üst header + içerik blokları -->
        <main class="admin-main">
            
            <header class="admin-header">
                <div class="header-left">
                    <h1 class="page-title">
                        <?php
                        $titles = [
                            'dashboard' => 'Dashboard',
                            'users' => 'Kullanıcı Yönetimi',
                            'videos' => 'Video Yönetimi',
                            'video_requests' => 'Video Düzenleme Talepleri',
                            'chat' => 'Sohbet',
                            'system' => 'Sistem Yönetimi'
                        ];
                        echo $titles[$section] ?? 'Admin Panel';
                        ?>
                    </h1>
                </div>
                
                <div class="header-right">
                    <button
                        class="btn-icon"
                        aria-label="Menüyü aç/kapat"
                        aria-controls="appSidebar"
                        aria-expanded="false"
                        data-sidebar-toggle
                        data-target="#appSidebar"
                        title="Menü">
                        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                          <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </button>
                    <div class="search-box">
                        <input type="text" placeholder="Ara..." id="globalSearch">
                        <i class="fas fa-search"></i>
                    </div>
                    
                    
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['first_name'] ?? 'Admin') ?></span>
                    </div>
                </div>
            </header>
            
            <div class="admin-content">
                
                <?php if ($section === 'dashboard'): ?>
                    <!-- Dashboard: hızlı istatistik kartları ve kısayol aksiyonları -->
                    <div class="dashboard-grid">
                        
                        <div class="stats-grid">
                            <div class="stat-card users">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalUsers"><?= $data['dashboard_stats']['users']['total_users'] ?? 0 ?></h3>
                                    <p>Toplam Kullanıcı</p>
                                </div>
                            </div>
                            
                            <div class="stat-card videos">
                                <div class="stat-icon">
                                    <i class="fas fa-video"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalVideos"><?= $data['dashboard_stats']['videos']['total_videos'] ?? 0 ?></h3>
                                    <p>Toplam Video</p>
                                </div>
                            </div>
                            
                            <div class="stat-card pending">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="pendingItems"><?= ($data['dashboard_stats']['users']['pending_users'] ?? 0) + ($data['dashboard_stats']['videos']['pending_videos'] ?? 0) ?></h3>
                                    <p>Onay Bekleyen</p>
                                </div>
                            </div>
                            
                            <div class="stat-card active">
                                <div class="stat-icon">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeUsers"><?= $data['dashboard_stats']['users']['active_24h'] ?? 0 ?></h3>
                                    <p>Aktif Kullanıcı (24s)</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="quick-actions-grid">
                            <div class="action-card" onclick="location.href='?section=users&status=pending'">
                                <i class="fas fa-user-plus"></i>
                                <h4>Kullanıcı Onayları</h4>
                                <p><?= $data['dashboard_stats']['users']['pending_users'] ?? 0 ?> onay bekliyor</p>
                            </div>
                            
                            <div class="action-card" onclick="location.href='?section=videos&status=pending'">
                                <i class="fas fa-video"></i>
                                <h4>Video Onayları</h4>
                                <p><?= $data['dashboard_stats']['videos']['pending_videos'] ?? 0 ?> onay bekliyor</p>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'users'): ?>
                    <!-- Kullanıcı Yönetimi: filtreler + tablo listeleme -->
                    <div class="users-section">
                        
                        <div class="filters-bar">
                            <form method="GET" class="filter-form">
                                <input type="hidden" name="section" value="users">
                                
                                <div class="filter-group">
                                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                                           placeholder="Kullanıcı ara..." class="search-input">
                                </div>
                                
                                <div class="filter-group">
                                    <select name="status" class="filter-select">
                                        <option value="">Tüm Durumlar</option>
                                        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Onay Bekleyen</option>
                                        <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Onaylı</option>
                                        <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>Reddedilen</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="filter-btn">
                                    <i class="fas fa-filter"></i>
                                    Filtrele
                                </button>
                            </form>
                        </div>
                        
                        <div class="data-table-container">
                            <table class="data-table" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>Kullanıcı</th>
                                        <th>E-posta</th>
                                        <th>Telefon</th>
                                        <th>Rol</th>
                                        <th>Durum</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($data['users']) && !empty($data['users'])): ?>
                                        <?php foreach ($data['users'] as $user): ?>
                                            <tr data-user-id="<?= $user['id'] ?>">
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-avatar">
                                                            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                                                        </div>
                                                        <div class="user-details">
                                                            <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                                            <small>ID: <?= $user['id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['phone'] ?? '') ?></td>
                                                <td>
                                                    <span class="role-badge <?= $user['role'] ?>">
                                                        <?= $user['role'] === 'teacher' ? 'Öğretmen' : 'Öğrenci' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?= $user['status'] ?>">
                                                        <?= ucfirst($user['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <?php if ($user['status'] === 'pending'): ?>
                                                            <button class="btn-approve" onclick="approveUser(<?= $user['id'] ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn-reject" onclick="rejectUser(<?= $user['id'] ?>)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn-edit" onclick="editUser(<?= $user['id'] ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="no-data">
                                                <div class="empty-state">
                                                    <i class="fas fa-users"></i>
                                                    <p>Kullanıcı bulunamadı</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'videos'): ?>
                    <!-- Video Yönetimi: tablo listeleme ve durum rozetleri -->
                    <div class="content-header">
                        <h2>Video Yönetimi</h2>
                    </div>

                    <div class="data-table-container">
                        <table class="data-table" id="videosTable">
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>Yükleyen</th>
                                    <th>Durum</th>
                                    <th>Kaynak</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($videos['videos'])): ?>
                                    <?php foreach ($videos['videos'] as $video): ?>
                                        <?php
                                            $hasYoutube = !empty($video['youtube_url']);
                                            $hasLocal = !empty($video['file_path']);
                                        ?>
                                        <tr data-video-id="<?= (int)$video['id'] ?>">
                                            <td>
                                                <div class="video-info">
                                                    <strong><?= htmlspecialchars($video['title'] ?? ('Video #' . $video['id'])) ?></strong>
                                                    <?php if (!empty($video['description'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($video['description'], 0, 100, '...')) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($video['uploader_name'] ?? '-') ?></td>
                                            <td>
                                                <span class="status-badge <?= htmlspecialchars($video['status'] ?? 'pending') ?>">
                                                    <?= ucfirst($video['status'] ?? 'pending') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($hasYoutube): ?>
                                                    <span class="badge badge-info">YouTube</span>
                                                <?php endif; ?>
                                                <?php if ($hasLocal): ?>
                                                    <span class="badge badge-success">Yerel</span>
                                                <?php endif; ?>
                                                <?php if (!$hasYoutube && !$hasLocal): ?>
                                                    <span class="badge badge-warning">Kaynak yok</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= !empty($video['created_at']) ? date('d.m.Y H:i', strtotime($video['created_at'])) : '-' ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-view" title="Görüntüle" onclick="window.open('video_view.php?id=<?= (int)$video['id'] ?>','_blank')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if (($video['status'] ?? 'pending') === 'pending'): ?>
                                                        <button class="btn-approve" title="Onayla" onclick="approveVideo(<?= (int)$video['id'] ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-reject" title="Reddet" onclick="rejectVideo(<?= (int)$video['id'] ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn-edit" title="YouTube Ayarla" onclick="setYouTubeUrl(<?= (int)$video['id'] ?>, '<?= htmlspecialchars($video['youtube_url'] ?? '', ENT_QUOTES) ?>')">
                                                        <i class="fab fa-youtube"></i>
                                                    </button>
                                                    <?php if ($hasYoutube): ?>
                                                        <button class="btn-warning" title="YouTube Temizle" onclick="clearYouTubeUrl(<?= (int)$video['id'] ?>)">
                                                            <i class="fas fa-eraser"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn-delete" title="Sil" onclick="deleteVideo(<?= (int)$video['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php if ($hasLocal && !empty($video['file_path'])): ?>
                                                        <a class="btn-download" title="İndir" href="<?= 'uploads/' . ltrim($video['file_path'], '/\\') ?>" download>
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($hasYoutube): ?>
                                                        <button class="btn-delete" 
                                                                title="Yerel dosyayı sil"
                                                                onclick="removeLocalVideo(<?= (int)$video['id'] ?>)"
                                                                <?= $hasLocal ? '' : 'disabled' ?>>
                                                            <i class="fas fa-file-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data">
                                            <div class="empty-state">
                                                <i class="fas fa-video"></i>
                                                <p>Video bulunamadı</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <script>
                    async function postForm(url, data) {
                        const body = Object.entries(data).map(([k,v])=>`${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
                        const res = await fetch(url, {method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body});
                        return res.json();
                    }
                    async function approveVideo(id){
                        if(!confirm('Videoyu onaylamak istiyor musunuz?')) return;
                        const r = await postForm('admin.php', {action:'video_action', video_id:id, operation:'approve'});
                        if(r.success){ location.reload(); } else { alert('İşlem başarısız'); }
                    }
                    async function rejectVideo(id){
                        const reason = prompt('Reddetme sebebi (opsiyonel):','');
                        if(reason===null) return;
                        const r = await postForm('admin.php', {action:'video_action', video_id:id, operation:'reject', reason:reason});
                        if(r.success){ location.reload(); } else { alert('İşlem başarısız'); }
                    }
                    async function deleteVideo(id){
                        if(!confirm('Videoyu silmek istiyor musunuz? Bu işlem geri alınamaz.')) return;
                        const r = await postForm('admin.php', {action:'video_action', video_id:id, operation:'delete'});
                        if(r.success){ location.reload(); } else { alert('İşlem başarısız'); }
                    }
                    async function removeLocalVideo(id){
                        if(!confirm('Yerel video dosyası silinsin mi?')) return;
                        const r = await postForm('admin.php', {action:'remove_local_video', video_id:id});
                        alert(r.message || (r.deleted ? 'Dosya silindi' : 'Silinemedi'));
                        if(r.success){ location.reload(); }
                    }
                    async function setYouTubeUrl(id, current){
                        const url = prompt('YouTube URL girin:', current || '');
                        if(url === null) return; // canceled
                        const r = await postForm('admin.php', {action:'update_video_youtube', video_id:id, youtube_url:url});
                        if(r.success){ location.reload(); } else { alert('YouTube URL kaydedilemedi'); }
                    }
                    async function clearYouTubeUrl(id){
                        if(!confirm('YouTube URL temizlensin mi?')) return;
                        const r = await postForm('admin.php', {action:'update_video_youtube', video_id:id, youtube_url:''});
                        if(r.success){ location.reload(); } else { alert('YouTube URL temizlenemedi'); }
                    }
                    </script>
                <?php elseif ($section === 'video_requests'): ?>
                    <div class="content-header">
                        <h2>Video Düzenleme Talepleri</h2>
                        <div class="stats-cards">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?= $videoRequests['total'] ?? 0 ?></div>
                                    <div class="stat-label">Bekleyen Talepler</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Video</th>
                                    <th>Talep Eden</th>
                                    <th>Talep Türü</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($videoRequests['requests'])): ?>
                                    <?php foreach ($videoRequests['requests'] as $request): ?>
                                        <tr>
                                            <td>
                                                <div class="video-info">
                                                    <strong><?= htmlspecialchars($request['current_title']) ?></strong>
                                                    <?php if ($request['request_type'] === 'edit'): ?>
                                                        <br><small>→ <?= htmlspecialchars($request['proposed_title'] ?? 'Değişiklik yok') ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <strong><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></strong>
                                                    <br><small><?= htmlspecialchars($request['email']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $request['request_type'] === 'delete' ? 'badge-danger' : 'badge-warning' ?>">
                                                    <?= $request['request_type'] === 'delete' ? 'Silme' : 'Düzenleme' ?>
                                                </span>
                                            </td>
                                            <td><?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-approve" onclick="approveVideoRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn-reject" onclick="rejectVideoRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button class="btn-view" onclick="viewVideoRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="no-data">
                                            <div class="empty-state">
                                                <i class="fas fa-edit"></i>
                                                <p>Bekleyen video düzenleme talebi bulunmamaktadır.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>
                
            </div>
        </main>
    </div>
    
    <?php
        // Cache-busting for admin JS to ensure latest changes are loaded
        $adminJsPath = __DIR__ . '/assets/js/admin-interactions.js';
        $adminJsVer = file_exists($adminJsPath) ? filemtime($adminJsPath) : time();
    ?>
    <script src="assets/js/admin-interactions.js?v=<?= $adminJsVer ?>"></script>
    <script src="assets/js/ui.js" defer></script>
</body>
</html>