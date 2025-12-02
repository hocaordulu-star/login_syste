<?php
/**
 * AdminManager Class
 * 
 * Comprehensive admin management system for educational platform
 * Handles users, videos, analytics, reports, and system management
 * 
 * @author Admin System
 * @version 1.0
 */

class AdminManager {
    /** @var string */
    private $lastError = '';
    /**
     * Veritabanı bağlantı nesnesi (mysqli)
     * Uygulamadaki tüm yönetim işlemleri bu bağlantı üzerinden yürütülür.
     * @var \mysqli
     */
    private $conn;
    /**
     * Basit cache yöneticisi
     * Sık kullanılan ve maliyetli sorgular için TTL bazlı önbellek.
     * @var CacheManager
     */
    private $cache;
    /**
     * İşlemi yapan admin kullanıcının ID'si
     * Audit log ve yetki kontrolleri için kullanılır.
     * @var int|null
     */
    private $adminId;
    
    /**
     * Yapıcı (constructor)
     *
     * @param \mysqli $database_connection Veritabanı bağlantısı
     * @param int|null $admin_user_id Oturumdaki admin ID'si (audit log için opsiyonel)
     */
    public function __construct($database_connection, $admin_user_id = null) {
        $this->conn = $database_connection;
        $this->adminId = $admin_user_id;
        // CacheManager sınıfı bu dosyada tanımlı olduğu için direkt kullanabiliriz
        $this->cache = new CacheManager($database_connection);
    }
    
    // ==================== USER MANAGEMENT ====================
    
    /**
     * Kullanıcıları filtreler ve sayfalama ile listeler.
     *
     * Filtreler: status, role, search (ad soyad veya e‑posta). Sonuçlar önbelleğe alınır.
     *
     * @param array $filters ['status'=>string, 'role'=>string, 'search'=>string]
     * @param int   $page    Sayfa numarası (1'den başlar)
     * @param int   $limit   Sayfa başına kayıt sayısı
     * @return array { users: array, total: int, page: int, limit: int, pages: int }
     */
    public function getUsers($filters = [], $page = 1, $limit = 20) {
        $cacheKey = 'admin_users_' . md5(serialize($filters) . $page . $limit);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        $where = ['1=1'];
        $params = [];
        $types = '';
        
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['role'])) {
            $where[] = 'role = ?';
            $params[] = $filters['role'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(CONCAT_WS(' ', first_name, last_name) LIKE ? OR email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search]);
            $types .= 'ss';
        }
        
        $offset = ($page - 1) * $limit;
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users WHERE $whereClause";
        $countStmt = $this->conn->prepare($countSql);
        if ($types) $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get users
        $sql = "SELECT id, CONCAT_WS(' ', first_name, last_name) AS full_name, first_name, last_name, email, phone, role, status, created_at, last_login 
                FROM users WHERE $whereClause 
                ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
        
        $data = [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
        
        $this->cache->set($cacheKey, $data, 300); // 5 minutes
        return $data;
    }
    
    /**
     * Toplu kullanıcı işlemleri uygular.
     *
     * Desteklenen işlemler: activate, deactivate, delete, change_role.
     * change_role için ek olarak $data['role'] beklenir.
     * İşlem başarılı olursa audit log yazılır ve ilgili cache anahtarları temizlenir.
     *
     * @param int[]  $userIds  Kullanıcı ID listesi
     * @param string $operation İşlem türü
     * @param array  $data      Ek veriler (örn. ['role'=>'teacher'])
     * @return bool Başarı durumu
     */
    public function bulkUserOperation($userIds, $operation, $data = []) {
        if (empty($userIds) || !is_array($userIds)) return false;
        
        $userIds = array_map('intval', $userIds);
        // Build dynamic placeholders for IN (...) safely (e.g. "?, ?, ?")
        // Note: values are still bound via prepared statements below
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        
        switch ($operation) {
            case 'activate':
                $sql = "UPDATE users SET status = 'approved' WHERE id IN ($placeholders)";
                break;
            case 'deactivate':
                $sql = "UPDATE users SET status = 'suspended' WHERE id IN ($placeholders)";
                break;
            case 'delete':
                $sql = "DELETE FROM users WHERE id IN ($placeholders)";
                break;
            case 'change_role':
                if (empty($data['role'])) return false;
                $sql = "UPDATE users SET role = ? WHERE id IN ($placeholders)";
                array_unshift($userIds, $data['role']);
                break;
            default:
                return false;
        }
        
        $stmt = $this->conn->prepare($sql);
        // Bind types match the list composition. For change_role, first arg is role (string)
        $types = str_repeat('i', count($userIds));
        if ($operation === 'change_role') $types = 's' . substr($types, 1); // first param becomes string
        
        $stmt->bind_param($types, ...$userIds);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $this->logAdminAction('bulk_user_operation', [
                'operation' => $operation,
                'user_ids' => $userIds,
                'data' => $data
            ]);
            $this->cache->delete('admin_users_*');
        }
        
        return $success;
    }
    
    // ==================== VIDEO MANAGEMENT ====================
    
    /**
     * Gelişmiş filtrelerle videoları getirir ve sayfalar.
     *
     * Filtreler: status, grade, search, date_from, date_to. Sonuçlar önbelleğe alınır.
     *
     * @param array $filters
     * @param int   $page
     * @param int   $limit
     * @return array { videos: array, total: int, page: int, limit: int, pages: int }
     */
    public function getVideos($filters = [], $page = 1, $limit = 20) {
        $cacheKey = 'admin_videos_' . md5(serialize($filters) . $page . $limit);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        $where = ['1=1'];
        $params = [];
        $types = '';
        
        if (!empty($filters['status'])) {
            $where[] = 'v.status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['grade'])) {
            $where[] = 'v.grade = ?';
            $params[] = $filters['grade'];
            $types .= 'i';
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(v.title LIKE ? OR v.topic LIKE ? OR v.description LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
            $types .= 'sss';
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'v.created_at >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'v.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }
        
        $offset = ($page - 1) * $limit;
        $whereClause = implode(' AND ', $where);
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM videos v 
                     JOIN users u ON v.uploaded_by = u.id WHERE $whereClause";
        $countStmt = $this->conn->prepare($countSql);
        if ($types) $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get videos with uploader and simple stats columns (placeholders for future metrics)
        $sql = "SELECT v.*, CONCAT_WS(' ', u.first_name, u.last_name) as uploader_name,
                       v.view_count,
                       0 as completion_rate,
                       0 as average_rating
                FROM videos v 
                JOIN users u ON v.uploaded_by = u.id 
                WHERE $whereClause 
                ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            $videos[] = $row;
        }
        $stmt->close();
        
        $data = [
            'videos' => $videos,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }
    
    /**
     * Toplu video işlemleri uygular.
     *
     * Desteklenen işlemler: approve, reject, delete.
     * approve/reject işlemlerinde adminId ve (reject için) sebep kaydedilir.
     * Başarı halinde hem admin hem öğrenci tarafı ile ilgili cache anahtarları temizlenir.
     *
     * @param int[]  $videoIds Video ID listesi
     * @param string $operation İşlem türü
     * @param array  $data      Ek veriler (örn. ['reason'=>'...'])
     * @return bool Başarı durumu
     */
    public function bulkVideoOperation($videoIds, $operation, $data = []) {
        if (empty($videoIds) || !is_array($videoIds)) return false;
        
        $videoIds = array_map('intval', $videoIds);
        $placeholders = str_repeat('?,', count($videoIds) - 1) . '?';
        
        // Collect uploader (teacher) IDs for cache invalidation BEFORE mutating videos table
        $uploaderIds = [];
        $preStmt = $this->conn->prepare("SELECT DISTINCT uploaded_by FROM videos WHERE id IN ($placeholders)");
        if ($preStmt) {
            $preTypes = str_repeat('i', count($videoIds));
            $preStmt->bind_param($preTypes, ...$videoIds);
            if ($preStmt->execute()) {
                $res = $preStmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    if (isset($row['uploaded_by'])) {
                        $uploaderIds[] = (int)$row['uploaded_by'];
                    }
                }
            }
            $preStmt->close();
        }
        
        $sql = '';
        $types = '';
        $params = [];
        
        switch ($operation) {
            case 'approve':
                $sql = "UPDATE videos SET status = 'approved', approved_by = ?, updated_at = NOW() WHERE id IN ($placeholders)";
                $types = 'i' . str_repeat('i', count($videoIds));
                $params = array_merge([$this->adminId], $videoIds);
                break;
            case 'reject':
                $reason = $data['reason'] ?? 'Bulk rejection';
                $sql = "UPDATE videos SET status = 'rejected', rejection_reason = ?, approved_by = ?, updated_at = NOW() WHERE id IN ($placeholders)";
                $types = 'si' . str_repeat('i', count($videoIds));
                $params = array_merge([$reason, $this->adminId], $videoIds);
                break;
            case 'delete':
                $sql = "DELETE FROM videos WHERE id IN ($placeholders)";
                $types = str_repeat('i', count($videoIds));
                $params = $videoIds;
                break;
            default:
                return false;
        }

        // Prepare and bind dynamic IN list
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("AdminManager::bulkVideoOperation prepare failed: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        if (!$success) {
            error_log("AdminManager::bulkVideoOperation execute failed: " . $stmt->error);
        }
        $stmt->close();

        if ($success) {
            $this->logAdminAction('bulk_video_operation', [
                'operation' => $operation,
                'video_ids' => $videoIds,
                'data' => $data
            ]);
            // Invalidate admin caches
            $this->cache->delete('admin_videos_*');
            // Invalidate student-facing caches so approvals reflect immediately
            $this->cache->delete('videos_grade_*');
            $this->cache->delete('units_grade_*');
            $this->cache->delete('video_stats_*');
            // Invalidate teacher-specific caches for affected uploaders
            if (!empty($uploaderIds)) {
                $uploaderIds = array_values(array_unique(array_filter($uploaderIds)));
                foreach ($uploaderIds as $tId) {
                    $this->cache->delete("teacher_videos_{$tId}_*");
                    $this->cache->delete("teacher_dashboard_stats_{$tId}");
                    $this->cache->delete("teacher_video_analytics_{$tId}_*");
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Kullanıcı aktivite analitiği.
     *
     * Günlük aktif kullanıcılar, saatlik giriş dağılımı ve kayıt trendlerini döndürür.
     * Sonuçlar 30 dakika önbelleğe alınır.
     *
     * @param int $days Kaç günlük veri analiz edilsin
     * @return array { daily_active: array, hourly_pattern: array, registration_trends: array }
     */
    public function getUserActivityAnalytics($days = 30) {
        $cacheKey = "user_activity_analytics_{$days}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        // Daily active users
        $dailyActive = [];
        $stmt = $this->conn->prepare("
            SELECT DATE(last_login) as date, COUNT(DISTINCT id) as active_users
            FROM users 
            WHERE last_login >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(last_login)
            ORDER BY date DESC
        ");
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $dailyActive[] = $row;
        }
        $stmt->close();
        
        // Hourly activity pattern
        $hourlyPattern = [];
        $stmt = $this->conn->prepare("
            SELECT HOUR(last_login) as hour, COUNT(*) as logins
            FROM users 
            WHERE last_login >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY HOUR(last_login)
            ORDER BY hour
        ");
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $hourlyPattern[] = $row;
        }
        $stmt->close();
        
        // User registration trends
        $registrationTrends = [];
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as registrations
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $registrationTrends[] = $row;
        }
        $stmt->close();
        
        $analytics = [
            'daily_active' => $dailyActive,
            'hourly_pattern' => $hourlyPattern,
            'registration_trends' => $registrationTrends
        ];
        
        $this->cache->set($cacheKey, $analytics, 1800); // 30 minutes
        return $analytics;
    }
    
    /**
     * Video analitiği.
     *
     * En çok izlenen videolar, yükleme trendleri ve sınıf dağılımını döndürür.
     * Sonuçlar 30 dakika önbelleğe alınır.
     *
     * @param int $days Kaç günlük veri analiz edilsin
     * @return array { most_viewed: array, upload_trends: array, grade_distribution: array }
     */
    public function getVideoAnalytics($days = 30) {
        $cacheKey = "video_analytics_{$days}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        // Most viewed videos
        $mostViewed = [];
        $stmt = $this->conn->prepare("
            SELECT v.id, v.title, v.grade, vs.view_count, vs.completion_rate
            FROM videos v
            JOIN video_statistics vs ON v.id = vs.video_id
            WHERE v.status = 'approved'
            ORDER BY vs.view_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $mostViewed[] = $row;
        }
        $stmt->close();
        
        // Video upload trends
        $uploadTrends = [];
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as uploads
            FROM videos 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $uploadTrends[] = $row;
        }
        $stmt->close();
        
        // Grade distribution
        $gradeDistribution = [];
        $stmt = $this->conn->query("
            SELECT grade, COUNT(*) as count
            FROM videos 
            WHERE status = 'approved'
            GROUP BY grade
            ORDER BY grade
        ");
        while ($row = $stmt->fetch_assoc()) {
            $gradeDistribution[] = $row;
        }
        
        $analytics = [
            'most_viewed' => $mostViewed,
            'upload_trends' => $uploadTrends,
            'grade_distribution' => $gradeDistribution
        ];
        
        $this->cache->set($cacheKey, $analytics, 1800);
        return $analytics;
    }
    
    /**
     * Dashboard istatistiklerini döndürür (kullanıcı, video, sistem, performans).
     * Sonuçlar 5 dakika önbelleğe alınır.
     * @return array
     */
    public function getDashboardStats() {
        $cacheKey = 'admin_dashboard_stats';
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        // User statistics
        $userStats = $this->conn->query("
            SELECT 
                COUNT(*) AS total_users,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS active_users,
                SUM(CASE WHEN status <> 'approved' THEN 1 ELSE 0 END) AS inactive_users,
                SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) AS teachers,
                SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) AS students,
                SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) AS active_24h
            FROM users
        ")->fetch_assoc();
        
        // Video statistics
        $videoStats = $this->conn->query("
            SELECT 
                COUNT(*) AS total_videos,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_videos,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_videos,
                SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) AS featured_videos,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) AS new_24h
            FROM videos
        ")->fetch_assoc();
        
        // System statistics
        $systemStats = [
            'total_messages' => 0,
            'unread_messages' => 0,
            'new_messages_24h' => 0,
        ];
        
        // Performance metrics
        $performanceStats = [
            'database_size' => $this->getDatabaseSize(),
            'cache_hit_rate' => $this->cache->getHitRate(),
            'avg_response_time' => $this->getAverageResponseTime()
        ];
        
        $stats = [
            'users' => $userStats,
            'videos' => $videoStats,
            'system' => $systemStats,
            'performance' => $performanceStats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->cache->set($cacheKey, $stats, 300); // 5 minutes
        return $stats;
    }
    
    /**
     * Sistem sağlık durumunu döndürür.
     *
     * Veritabanı, cache, disk ve bellek kullanımını özetler.
     * @return array
     */
    public function getSystemHealth() {
        $health = [];
        
        // Database health
        $health['database'] = [
            'status' => 'healthy',
            'size' => $this->getDatabaseSize(),
            'connections' => $this->getDatabaseConnections(),
            'slow_queries' => $this->getSlowQueries()
        ];
        
        // Cache health
        $health['cache'] = [
            'status' => 'healthy',
            'hit_rate' => $this->cache->getHitRate(),
            'size' => $this->cache->getSize(),
            'entries' => $this->cache->getEntryCount()
        ];
        
        // Disk usage
        $health['disk'] = [
            'total' => disk_total_space('.'),
            'free' => disk_free_space('.'),
            'used_percent' => round((1 - disk_free_space('.') / disk_total_space('.')) * 100, 2)
        ];
        
        // Memory usage
        $health['memory'] = [
            'usage' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];
        
        return $health;
    }
    
    // ==================== HELPER METHODS ====================
    
    /**
     * Current database size in MB.
     * Uses information_schema to sum data and index lengths for the active schema.
     * @return float Size in megabytes
     */
    private function getDatabaseSize() {
        $result = $this->conn->query("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        return $result->fetch_assoc()['size_mb'] ?? 0;
    }
    
    /**
     * Number of current MySQL connections (Threads_connected).
     * @return int Active connection count
     */
    private function getDatabaseConnections() {
        $result = $this->conn->query("SHOW STATUS LIKE 'Threads_connected'");
        return $result->fetch_assoc()['Value'] ?? 0;
    }
    
    /**
     * Number of slow queries since server start (global counter).
     * @return int Slow query count
     */
    private function getSlowQueries() {
        $result = $this->conn->query("SHOW STATUS LIKE 'Slow_queries'");
        return $result->fetch_assoc()['Value'] ?? 0;
    }
    
    /**
     * Average response time for the app (mocked for now).
     * Replace with real APM metrics integration when available.
     * @return int Milliseconds
     */
    private function getAverageResponseTime() {
        // TODO: integrate with real metrics (e.g., Prometheus/APM)
        return rand(50, 200); // Mock data for now
    }
    
    /**
     * Admin işlemlerini denetim kaydı (audit log) olarak yazar.
     *
     * IP ve User-Agent bilgileriyle birlikte JSON detayları saklar.
     * adminId boşsa kayıt yapılmaz.
     *
     * @param string $action  İşlem adı
     * @param array  $details Detaylar (JSON olarak saklanır)
     * @return bool Başarı
     */
    public function logAdminAction($action, $details = []) {
        if (!$this->adminId) return false;
        
        $stmt = $this->conn->prepare("
            INSERT INTO admin_audit_logs (admin_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param('issss', $this->adminId, $action, $detailsJson, $ip, $userAgent);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * logAuditAction için geriye dönük uyumlu takma ad.
     * @see AdminManager::logAdminAction
     */
    public function logAuditAction($action, $details = []) {
        return $this->logAdminAction($action, $details);
    }
    
    /**
     * Audit log kayıtlarını sayfalı olarak getirir.
     *
     * @param int $page
     * @param int $limit
     * @return array Kayıt listesi
     */
    public function getAuditLogs($page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->conn->prepare("
            SELECT al.*, u.first_name, u.last_name
            FROM admin_audit_logs al
            JOIN users u ON al.admin_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        $stmt->close();
        
        return $logs;
    }

    /**
     * Returns the last error message set by this manager.
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }

    // ==================== VIDEO EDIT REQUESTS ====================
    /**
     * Bekleyen video düzenleme taleplerini getirir.
     *
     * Talepler video ve kullanıcı bilgileriyle birlikte döndürülür.
     * Sonuçlar 5 dakika önbelleğe alınır.
     *
     * @param int $page
     * @param int $limit
     * @return array { requests: array, total: int, page: int, limit: int, pages: int }
     */
    public function getPendingVideoEditRequests($page = 1, $limit = 20) {
        $cacheKey = "pending_video_edit_requests_{$page}_{$limit}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->conn->prepare("
            SELECT ver.*, v.title as current_title, v.description as current_description,
                   u.first_name, u.last_name, u.email
            FROM video_edit_requests ver
            JOIN videos v ON ver.video_id = v.id
            JOIN users u ON ver.requester_id = u.id
            WHERE ver.status = 'pending'
            ORDER BY ver.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        $stmt->close();
        
        // Get total count
        $totalStmt = $this->conn->prepare("SELECT COUNT(*) as total FROM video_edit_requests WHERE status = 'pending'");
        $totalStmt->execute();
        $total = $totalStmt->get_result()->fetch_assoc()['total'];
        $totalStmt->close();
        
        $data = [
            'requests' => $requests,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }
    
    /**
     * Video düzenleme talebini onaylar.
     *
     * Silme talebinde videoyu siler; diğer taleplerde önerilen alanları COALESCE ile günceller.
     * İşlemler bir transaction içinde yürütülür; başarılı olursa audit log yazılır ve cache temizlenir.
     *
     * @param int    $requestId Talep ID
     * @param string $adminNote Admin notu (opsiyonel)
     * @return bool Başarı
     */
    public function approveVideoEditRequest($requestId, $adminNote = '') {
        $stmt = $this->conn->prepare("SELECT * FROM video_edit_requests WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$request) { $this->lastError = 'Talep bulunamadı veya pending değil'; return false; }

        // Fetch video owner BEFORE any possible delete
        $ownerId = null;
        $vStmt = $this->conn->prepare("SELECT uploaded_by FROM videos WHERE id = ?");
        if ($vStmt) {
            $vStmt->bind_param('i', $request['video_id']);
            if ($vStmt->execute()) {
                $vRes = $vStmt->get_result()->fetch_assoc();
                if ($vRes && isset($vRes['uploaded_by'])) { $ownerId = (int)$vRes['uploaded_by']; }
            }
            $vStmt->close();
        }

        // Use transaction to ensure atomic approve + update steps
        $this->conn->begin_transaction();

        try {
            if ($request['request_type'] === 'delete') {
                // Delete the video
                $deleteStmt = $this->conn->prepare("DELETE FROM videos WHERE id = ?");
                $deleteStmt->bind_param('i', $request['video_id']);
                if (!$deleteStmt->execute()) {
                    throw new Exception('Video silme başarısız: ' . $deleteStmt->error);
                }
                $deleteStmt->close();
            } else {
                // Normalize proposed values: empty strings -> NULL to keep COALESCE working as intended
                $prop_title = isset($request['proposed_title']) && $request['proposed_title'] !== '' ? $request['proposed_title'] : null;
                $prop_desc = isset($request['proposed_description']) && $request['proposed_description'] !== '' ? $request['proposed_description'] : null;
                $prop_grade = isset($request['proposed_grade']) && in_array($request['proposed_grade'], ['5','6','7','8'], true) ? $request['proposed_grade'] : null;
                // unit_id 0 veya <0 ise NULL kabul et (FK hatasını önlemek için)
                $prop_unit  = null;
                if (isset($request['proposed_unit_id']) && $request['proposed_unit_id'] !== '') {
                    $tmpUnit = (int)$request['proposed_unit_id'];
                    if ($tmpUnit > 0) { $prop_unit = $tmpUnit; }
                }
                $prop_topic = isset($request['proposed_topic']) && $request['proposed_topic'] !== '' ? $request['proposed_topic'] : null;
                $prop_url   = isset($request['proposed_youtube_url']) && $request['proposed_youtube_url'] !== '' ? $request['proposed_youtube_url'] : null;

                $updateStmt = $this->conn->prepare("
                    UPDATE videos SET 
                        title = COALESCE(?, title),
                        description = COALESCE(?, description),
                        grade = COALESCE(?, grade),
                        unit_id = COALESCE(?, unit_id),
                        topic = COALESCE(?, topic),
                        youtube_url = COALESCE(?, youtube_url),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->bind_param('sssissi', 
                    $prop_title,
                    $prop_desc,
                    $prop_grade,
                    $prop_unit,
                    $prop_topic,
                    $prop_url,
                    $request['video_id']
                );
                if (!$updateStmt->execute()) {
                    throw new Exception('Video güncelleme başarısız: ' . $updateStmt->error);
                }
                $updateStmt->close();
            }

            // Update the request status
            $statusStmt = $this->conn->prepare("
                UPDATE video_edit_requests SET 
                    status = 'approved', 
                    decided_at = NOW(),
                    review_note = ?
                WHERE id = ?
            ");
            $statusStmt->bind_param('si', $adminNote, $requestId);
            $statusStmt->execute();
            $statusStmt->close();

            $this->conn->commit();

            // Log the action
            $this->logAdminAction('video_edit_request_approved', [
                'request_id' => $requestId,
                'video_id' => $request['video_id'],
                'request_type' => $request['request_type'],
                'admin_note' => $adminNote
            ]);

            // Notify requester (teacher) about approval
            $msg = sprintf(
                'Video düzenleme talebiniz onaylandı. Talep ID: #%d. Not: %s',
                $requestId,
                $adminNote !== '' ? $adminNote : '—'
            );
            $this->sendSystemMessage((int)$request['requester_id'], $msg, 'video_edit_request');

            // Clear the cache for requests and video listings
            $this->cache->delete('pending_video_edit_requests_*');
            $this->cache->delete('admin_videos_*');
            // Invalidate teacher caches for requester and video owner
            $reqId = (int)$request['requester_id'];
            if ($reqId > 0) {
                $this->cache->delete("teacher_videos_{$reqId}_*");
                $this->cache->delete("teacher_dashboard_stats_{$reqId}");
                $this->cache->delete("teacher_video_analytics_{$reqId}_*");
            }
            if (!empty($ownerId)) {
                $this->cache->delete("teacher_videos_{$ownerId}_*");
                $this->cache->delete("teacher_dashboard_stats_{$ownerId}");
                $this->cache->delete("teacher_video_analytics_{$ownerId}_*");
            }

            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            // Best-effort error logging for debugging
            @error_log('[approveVideoEditRequest] request_id=' . $requestId . ' error=' . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Reject a video edit request.
     *
     * Rejects a pending video edit request and logs the action.
     * Clears the cache for pending requests.
     *
     * @param int    $requestId The ID of the request to reject.
     * @param string $adminNote Optional note from the admin.
     * @return bool True if the request was rejected successfully, false otherwise.
     */
    public function rejectVideoEditRequest($requestId, $adminNote = '') {
        $stmt = $this->conn->prepare("\n            UPDATE video_edit_requests SET \n                status = 'rejected', \n                decided_at = NOW(),\n                review_note = ?\n            WHERE id = ? AND status = 'pending'\n        ");

        $stmt->bind_param('si', $adminNote, $requestId);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            $this->logAdminAction('video_edit_request_rejected', [
                'request_id' => $requestId,
                'admin_note' => $adminNote
            ]);

            // Fetch requester to notify
            $rq = $this->conn->prepare("SELECT requester_id, video_id FROM video_edit_requests WHERE id = ?");
            $rq->bind_param('i', $requestId);
            $rq->execute();
            $rqRes = $rq->get_result()->fetch_assoc();
            $rq->close();
            if ($rqRes && isset($rqRes['requester_id'])) {
                $msg = sprintf(
                    'Video düzenleme talebiniz reddedildi. Talep ID: #%d. Not: %s',
                    $requestId,
                    $adminNote !== '' ? $adminNote : '—'
                );
                $this->sendSystemMessage((int)$rqRes['requester_id'], $msg, 'video_edit_request');
            }

            // Invalidate the listing cache so the UI reflects the latest status
            $this->cache->delete('pending_video_edit_requests_*');
            // Also invalidate teacher caches (requester and video owner)
            if ($rqRes) {
                $reqId = (int)$rqRes['requester_id'];
                $vidId = isset($rqRes['video_id']) ? (int)$rqRes['video_id'] : 0;
                $ownerId = null;
                if ($vidId > 0) {
                    $vo = $this->conn->prepare('SELECT uploaded_by FROM videos WHERE id = ?');
                    if ($vo) {
                        $vo->bind_param('i', $vidId);
                        if ($vo->execute()) {
                            $vrow = $vo->get_result()->fetch_assoc();
                            if ($vrow && isset($vrow['uploaded_by'])) { $ownerId = (int)$vrow['uploaded_by']; }
                        }
                        $vo->close();
                    }
                }
                if ($reqId > 0) {
                    $this->cache->delete("teacher_videos_{$reqId}_*");
                    $this->cache->delete("teacher_dashboard_stats_{$reqId}");
                    $this->cache->delete("teacher_video_analytics_{$reqId}_*");
                }
                if (!empty($ownerId)) {
                    $this->cache->delete("teacher_videos_{$ownerId}_*");
                    $this->cache->delete("teacher_dashboard_stats_{$ownerId}");
                    $this->cache->delete("teacher_video_analytics_{$ownerId}_*");
                }
            }
        }

        return $success;
    }

    /**
     * Sistem mesajı gönderir (messages tablosuna kayıt açar).
     * @param int $toUserId   Alıcı kullanıcı ID
     * @param string $message  Mesaj içeriği
     * @param string $topic    Konu etiketi (örn. 'video_edit_request')
     * @return bool Başarı
     */
    private function sendSystemMessage($toUserId, $message, $topic = 'system') {
        $toUserId = (int)$toUserId;
        if ($toUserId <= 0 || $message === '') return false;

        $stmt = $this->conn->prepare("
            INSERT INTO messages (user_id, receiver_id, topic, message, context, is_read, created_at)
            VALUES (?, ?, ?, ?, 'system', 0, NOW())
        ");
        if (!$stmt) return false;
        $senderId = (int)$this->adminId ?: null; // admin yoksa NULL bırak
        // If adminId is null, we still bind an int; use 0 to represent system
        if ($senderId === null) $senderId = 0;
        $stmt->bind_param('iiss', $senderId, $toUserId, $topic, $message);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    /**
     * Video YouTube'a yüklendikten sonra yerel dosyayı kaldırır.
     * - youtube_url boş olmamalı (aksi halde kullanıcı deneyimini bozabilir)
     * - Sadece uploads dizini içinde güvenli silme yapılır (path traversal önlenir)
     * - file_path alanı NULL yapılır; meta veriler ve YouTube oynatma korunur
     *
     * @param int $videoId Video ID
     * @return array ['success'=>bool, 'deleted'=>bool, 'message'=>string]
     */
    public function removeLocalVideoFile($videoId) {
        $videoId = (int)$videoId;
        if ($videoId <= 0) {
            return ['success' => false, 'deleted' => false, 'message' => 'Geçersiz video'];
        }

        // Fetch current file_path and youtube_url
        $stmt = $this->conn->prepare("SELECT file_path, youtube_url FROM videos WHERE id = ?");
        $stmt->bind_param('i', $videoId);
        $stmt->execute();
        $video = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$video) {
            return ['success' => false, 'deleted' => false, 'message' => 'Video bulunamadı'];
        }

        // Ensure YouTube URL exists before deleting local file
        $youtubeUrl = trim((string)($video['youtube_url'] ?? ''));
        if ($youtubeUrl === '') {
            return ['success' => false, 'deleted' => false, 'message' => 'YouTube bağlantısı eklenmeden yerel dosya silinemez'];
        }

        $deleted = false;
        $filePathDb = $video['file_path'] ?? null;

        if (!empty($filePathDb)) {
            // Build secure absolute path inside uploads directory
            $baseDir = realpath(__DIR__ . '/../uploads');
            $target = $baseDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePathDb), DIRECTORY_SEPARATOR);
            $realTarget = realpath($target);

            // Only delete if the file exists and is under uploads dir
            // realpath + prefix check prevents path traversal outside uploads
            if ($realTarget && strpos($realTarget, $baseDir) === 0 && is_file($realTarget)) {
                $deleted = @unlink($realTarget);
            }
        }

        // Update DB: nullify file_path regardless of unlink result to stop referencing heavy local file
        // This ensures subsequent reads don't attempt to serve a missing or large local file
        $up = $this->conn->prepare("UPDATE videos SET file_path = NULL, updated_at = NOW() WHERE id = ?");
        $up->bind_param('i', $videoId);
        $ok = $up->execute();
        $up->close();

        if ($ok) {
            $this->logAdminAction('remove_local_video_file', [
                'video_id' => $videoId,
                'deleted' => $deleted,
            ]);
            $this->cache->delete('admin_videos_*');
            return ['success' => true, 'deleted' => $deleted, 'message' => $deleted ? 'Yerel dosya silindi' : 'Yerel dosya bulunamadı veya silinemedi; veritabanı güncellendi'];
        }

        return ['success' => false, 'deleted' => $deleted, 'message' => 'Veritabanı güncellenemedi'];
    }

}

/**
 * CacheManager
 * 
 * Simple TTL-based cache persisted in the `system_cache` table.
 * Designed for small JSON payloads (lists, stats) to reduce repeated DB work.
 *
 * Notes:
 * - Keys are arbitrary strings. Prefer a consistent namespace (e.g., admin_users_*, videos_grade_*)
 * - Values are JSON-encoded/decoded transparently.
 * - `delete()` supports exact key or LIKE patterns via `*` wildcard.
 * - `getHitRate()` is per-request-process; not a global metric.
 */
class CacheManager {
    private $conn;
    private $hitCount = 0;
    private $missCount = 0;
    
    /**
     * @param \mysqli $database_connection Active DB connection
     */
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Fetches a cache entry if present and not expired.
     * Increments hit/miss counters for this process.
     * @param string $key
     * @return mixed|null Decoded JSON payload or null if missing/expired
     */
    public function get($key) {
        $stmt = $this->conn->prepare("SELECT cache_data, expires_at FROM system_cache WHERE cache_key = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $this->hitCount++;
            $stmt->close();
            return json_decode($row['cache_data'], true);
        }
        
        $this->missCount++;
        $stmt->close();
        return null;
    }
    
    /**
     * Upserts a cache entry with a time-to-live in seconds.
     * @param string $key
     * @param mixed  $value Any JSON-serializable value
     * @param int    $ttl   Time-to-live in seconds (default 15 minutes)
     * @return bool Success
     */
    public function set($key, $value, $ttl = 900) {
        $stmt = $this->conn->prepare("
            INSERT INTO system_cache (cache_key, cache_data, expires_at) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
            ON DUPLICATE KEY UPDATE 
            cache_data = VALUES(cache_data), 
            expires_at = VALUES(expires_at)
        ");
        
        $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
        $stmt->bind_param("ssi", $key, $jsonValue, $ttl);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Deletes a cache entry or a set of entries by pattern.
     * Use `*` as a wildcard (transformed to SQL LIKE `%`).
     * @param string $pattern Exact key or wildcard pattern
     * @return bool Success
     */
    public function delete($pattern) {
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '%', $pattern);
            $stmt = $this->conn->prepare("DELETE FROM system_cache WHERE cache_key LIKE ?");
        } else {
            $stmt = $this->conn->prepare("DELETE FROM system_cache WHERE cache_key = ?");
        }
        
        $stmt->bind_param("s", $pattern);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Returns the in-process cache hit rate percentage.
     * @return float 0..100
     */
    public function getHitRate() {
        $total = $this->hitCount + $this->missCount;
        return $total > 0 ? round(($this->hitCount / $total) * 100, 2) : 0;
    }
    
    /**
     * Total size of non-expired cache payloads in bytes.
     * @return int
     */
    public function getSize() {
        $result = $this->conn->query("SELECT SUM(LENGTH(cache_data)) as size FROM system_cache WHERE expires_at > NOW()");
        return $result->fetch_assoc()['size'] ?? 0;
    }
    
    /**
     * Number of non-expired cache entries.
     * @return int
     */
    public function getEntryCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM system_cache WHERE expires_at > NOW()");
        return $result->fetch_assoc()['count'] ?? 0;
    }
}
?>
