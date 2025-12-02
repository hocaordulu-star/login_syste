<?php
/**
 * TeacherManager Class
 * 
 * Comprehensive teacher management system for educational platform
 * Handles video uploads, analytics, dashboard stats, and teacher operations
 * 
 * @author Teacher System
 * @version 1.0
 */

class TeacherManager {
    private $conn;
    private $cache;
    private $teacherId;
    
    public function __construct($database_connection, $teacher_user_id = null) {
        $this->conn = $database_connection;
        $this->teacherId = $teacher_user_id;
        $this->cache = new TeacherCacheManager($database_connection);
    }
    
    // ==================== DASHBOARD STATISTICS ====================
    
    /**
     * Get teacher dashboard statistics
     */
    public function getDashboardStats() {
        $cacheKey = "teacher_dashboard_stats_{$this->teacherId}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        $stats = [];
        
        // Video statistics
        $videoStats = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_videos,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_videos,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_videos,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_videos,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as videos_this_week,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as videos_this_month
            FROM videos 
            WHERE uploaded_by = ?
        ");
        $videoStats->bind_param('i', $this->teacherId);
        $videoStats->execute();
        $vsRow = $videoStats->get_result()->fetch_assoc();
        $stats['videos'] = $vsRow ?: [
            'total_videos' => 0,
            'pending_videos' => 0,
            'approved_videos' => 0,
            'rejected_videos' => 0,
            'videos_this_week' => 0,
            'videos_this_month' => 0
        ];
        $videoStats->close();
        
        // Video views and engagement
        $engagementStats = $this->conn->prepare("
            SELECT 
                COALESCE(SUM(vs.view_count), 0) as total_views,
                COALESCE(AVG(vs.completion_rate), 0) as avg_completion_rate,
                COALESCE(SUM(vs.like_count), 0) as total_likes,
                COUNT(DISTINCT vp.user_id) as unique_viewers
            FROM videos v
            LEFT JOIN video_statistics vs ON v.id = vs.video_id
            LEFT JOIN video_progress vp ON v.id = vp.video_id
            WHERE v.uploaded_by = ? AND v.status = 'approved'
        ");
        $engagementStats->bind_param('i', $this->teacherId);
        $engagementStats->execute();
        $engRow = $engagementStats->get_result()->fetch_assoc();
        $stats['engagement'] = $engRow ?: [
            'total_views' => 0,
            'avg_completion_rate' => 0,
            'total_likes' => 0,
            'unique_viewers' => 0
        ];
        $engagementStats->close();
        
        // Recent activity (messages removed; keep messages_week key as 0 for compatibility)
        $recentActivity = $this->conn->prepare("
            SELECT 
                COUNT(CASE WHEN ver.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as edit_requests_week,
                COUNT(CASE WHEN ver.status = 'pending' THEN 1 END) as pending_requests,
                0 as messages_week
            FROM users u
            LEFT JOIN video_edit_requests ver ON u.id = ver.requester_id
            WHERE u.id = ?
        ");
        $recentActivity->bind_param('i', $this->teacherId);
        $recentActivity->execute();
        $actRow = $recentActivity->get_result()->fetch_assoc();
        $stats['activity'] = $actRow ?: [
            'edit_requests_week' => 0,
            'pending_requests' => 0,
            'messages_week' => 0
        ];
        $recentActivity->close();
        
        $stats['generated_at'] = date('Y-m-d H:i:s');
        
        $this->cache->set($cacheKey, $stats, 300); // 5 minutes
        return $stats;
    }
    
    // ==================== VIDEO MANAGEMENT ====================
    
    /**
     * Get teacher's videos with advanced filters
     */
    public function getVideos($filters = [], $page = 1, $limit = 20) {
        $cacheKey = 'teacher_videos_' . $this->teacherId . '_' . md5(serialize($filters) . $page . $limit);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        $where = ['v.uploaded_by = ?'];
        $params = [$this->teacherId];
        $types = 'i';
        
        if (!empty($filters['status'])) {
            $where[] = 'v.status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['grade'])) {
            $where[] = 'v.grade = ?';
            $params[] = $filters['grade'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(v.title LIKE ? OR v.description LIKE ? OR v.topic LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
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
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM videos v WHERE " . implode(' AND ', $where);
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get videos with pagination
        $offset = ($page - 1) * $limit;
        $sql = "
            SELECT v.*, 
                   vs.view_count, vs.completion_rate, vs.like_count,
                   NULL AS last_change_summary,
                   NULL AS last_change_at,
                   NULL AS editor_first,
                   NULL AS editor_last,
                   COUNT(vp.video_id) as progress_count
            FROM videos v
            LEFT JOIN video_statistics vs ON v.id = vs.video_id
            LEFT JOIN video_progress vp ON v.id = vp.video_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY v.id
            ORDER BY v.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
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
     * Upload new video
     */
    public function uploadVideo($videoData, $fileData) {
        $title = trim($videoData['title'] ?? '');
        $description = trim($videoData['description'] ?? '');
        $grade = trim($videoData['grade'] ?? '');
        $unitId = isset($videoData['unit_id']) ? (int)$videoData['unit_id'] : 0;
        $topic = trim($videoData['topic'] ?? '');
        $youtubeUrl = trim($videoData['youtube_url'] ?? '');
        
        // Validation
        if (empty($title) || !in_array($grade, ['5','6','7','8'], true) || $unitId <= 0) {
            return ['success' => false, 'message' => 'Başlık, sınıf ve ünite zorunludur.'];
        }
        
        // Get unit name
        $unitStmt = $this->conn->prepare('SELECT unit_name FROM units WHERE id = ? AND grade = ? AND subject = "math" AND is_active = 1');
        $unitStmt->bind_param('is', $unitId, $grade);
        $unitStmt->execute();
        $unitStmt->bind_result($unitName);
        $unitStmt->fetch();
        $unitStmt->close();
        
        if (!$unitName) {
            return ['success' => false, 'message' => 'Geçersiz ünite seçimi.'];
        }
        
        $filename = null;
        
        // Handle file upload if provided
        if (!empty($fileData['name'])) {
            $allowed = ['mp4', 'mov', 'webm'];
            $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed, true)) {
                return ['success' => false, 'message' => 'Desteklenen formatlar: MP4, MOV, WEBM'];
            }
            
            if (!is_dir('uploads')) {
                @mkdir('uploads', 0777, true);
            }
            
            $filename = uniqid("video_") . "." . $ext;
            $destination = "uploads/" . $filename;
            
            if (!move_uploaded_file($fileData['tmp_name'], $destination)) {
                return ['success' => false, 'message' => 'Dosya yüklenemedi.'];
            }
        }
        
        // Insert video
        $stmt = $this->conn->prepare("\n            INSERT INTO videos (uploaded_by, subject, grade, unit_id, unit, topic, title, description, file_path, youtube_url, status, created_at) \n            VALUES (?, 'math', ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())\n        ");
        if ($stmt === false) {
            return ['success' => false, 'message' => 'SQL hazırlama hatası: ' . $this->conn->error];
        }
        
        $topicParam = empty($topic) ? null : $topic;
        $descParam = empty($description) ? null : $description;
        $youtubeParam = empty($youtubeUrl) ? null : $youtubeUrl;
        
        $stmt->bind_param('isissssss', $this->teacherId, $grade, $unitId, $unitName, $topicParam, $title, $descParam, $filename, $youtubeParam);
        
        if ($stmt->execute()) {
            $videoId = $this->conn->insert_id;
            $stmt->close();
            
            // Best-effort: cache clear and activity log should not break upload result
            try {
                if (isset($this->cache)) {
                    // Teacher caches
                    @$this->cache->delete("teacher_videos_{$this->teacherId}_*");
                    @$this->cache->delete("teacher_dashboard_stats_{$this->teacherId}");
                    // Admin caches
                    @$this->cache->delete('admin_videos_*');
                    @$this->cache->delete('admin_dashboard_stats');
                }
            } catch (\Throwable $e) { /* ignore non-critical cache errors */ }
            
            try {
                if (method_exists($this, 'logActivity')) {
                    $this->logActivity('video_upload', 'video', $videoId, [
                        'title' => $title,
                        'grade' => $grade,
                        'unit_id' => $unitId
                    ]);
                }
            } catch (\Throwable $e) { /* ignore non-critical logging errors */ }
            
            return ['success' => true, 'message' => 'Video başarıyla yüklendi. Admin onayı bekleniyor.', 'video_id' => $videoId];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $this->conn->error];
        }
    }
    
    /**
     * Bulk video operations
     */
    public function bulkVideoOperation($videoIds, $operation) {
        if (empty($videoIds) || !is_array($videoIds)) {
            return ['success' => false, 'message' => 'Video seçimi gerekli.'];
        }
        
        $videoIds = array_map('intval', $videoIds);
        $placeholders = str_repeat('?,', count($videoIds) - 1) . '?';
        
        // Verify ownership
        $checkStmt = $this->conn->prepare("SELECT COUNT(*) as count FROM videos WHERE id IN ($placeholders) AND uploaded_by = ?");
        $params = array_merge($videoIds, [$this->teacherId]);
        $types = str_repeat('i', count($videoIds)) . 'i';
        $checkStmt->bind_param($types, ...$params);
        $checkStmt->execute();
        $owned = $checkStmt->get_result()->fetch_assoc()['count'];
        $checkStmt->close();
        
        if ($owned !== count($videoIds)) {
            return ['success' => false, 'message' => 'Sadece kendi videolarınızı işleyebilirsiniz.'];
        }
        
        switch ($operation) {
            case 'delete':
                $sql = "DELETE FROM videos WHERE id IN ($placeholders) AND uploaded_by = ?";
                break;
            case 'request_edit':
                // Create bulk edit requests
                foreach ($videoIds as $videoId) {
                    $reqStmt = $this->conn->prepare("
                        INSERT INTO video_edit_requests (request_type, video_id, requester_id, status, created_at) 
                        VALUES ('edit', ?, ?, 'pending', NOW())
                    ");
                    $reqStmt->bind_param('ii', $videoId, $this->teacherId);
                    $reqStmt->execute();
                    $reqStmt->close();
                }
                
                $this->logActivity('bulk_edit_request', 'videos', null, ['video_ids' => $videoIds]);
                return ['success' => true, 'message' => count($videoIds) . ' video için düzenleme talebi gönderildi.'];
                
            default:
                return ['success' => false, 'message' => 'Geçersiz işlem.'];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $this->cache->delete("teacher_videos_{$this->teacherId}_*");
            $this->cache->delete("teacher_dashboard_stats_{$this->teacherId}");
            
            $this->logActivity('bulk_video_operation', 'videos', null, [
                'operation' => $operation,
                'video_ids' => $videoIds,
                'count' => count($videoIds)
            ]);
            
            $message = $operation === 'delete' ? 'Videolar silindi.' : 'İşlem tamamlandı.';
            return ['success' => true, 'message' => $message];
        }
        
        return ['success' => false, 'message' => 'İşlem başarısız.'];
    }
    
    // ==================== ANALYTICS ====================
    
    /**
     * Get video analytics
     */
    public function getVideoAnalytics($days = 30) {
        $cacheKey = "teacher_video_analytics_{$this->teacherId}_{$days}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;
        
        // Most viewed videos
        $mostViewed = [];
        $stmt = $this->conn->prepare("
            SELECT v.id, v.title, v.grade, vs.view_count, vs.completion_rate, vs.like_count
            FROM videos v
            JOIN video_statistics vs ON v.id = vs.video_id
            WHERE v.uploaded_by = ? AND v.status = 'approved'
            ORDER BY vs.view_count DESC
            LIMIT 10
        ");
        $stmt->bind_param('i', $this->teacherId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $mostViewed[] = $row;
        }
        $stmt->close();
        
        // Grade distribution
        $gradeDistribution = [];
        $stmt = $this->conn->prepare("
            SELECT v.grade, COUNT(*) as count, AVG(vs.view_count) as avg_views
            FROM videos v
            LEFT JOIN video_statistics vs ON v.id = vs.video_id
            WHERE v.uploaded_by = ? AND v.status = 'approved'
            GROUP BY v.grade
            ORDER BY v.grade
        ");
        $stmt->bind_param('i', $this->teacherId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $gradeDistribution[] = $row;
        }
        $stmt->close();
        
        // Upload trends
        $uploadTrends = [];
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM videos 
            WHERE uploaded_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->bind_param('ii', $this->teacherId, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $uploadTrends[] = $row;
        }
        $stmt->close();
        
        $analytics = [
            'most_viewed' => $mostViewed,
            'grade_distribution' => $gradeDistribution,
            'upload_trends' => $uploadTrends
        ];
        
        $this->cache->set($cacheKey, $analytics, 1800); // 30 minutes
        return $analytics;
    }
    
    // ==================== ACTIVITY LOGGING ====================
    
    /**
     * Log teacher activity
     */
    public function logActivity($activityType, $resourceType = null, $resourceId = null, $details = []) {
        $stmt = $this->conn->prepare("
            INSERT INTO user_activity_logs (user_id, activity_type, resource_type, resource_id, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param('isssss', $this->teacherId, $activityType, $resourceType, $resourceId, $detailsJson, $ip, $userAgent);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    // ==================== NOTIFICATIONS ====================
    
    /**
     * Get teacher notifications
     */
    public function getNotifications($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT 'video_approved' as type, v.title as message, v.updated_at as created_at
            FROM videos v 
            WHERE v.uploaded_by = ? AND v.status = 'approved' AND v.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            UNION ALL
            
            SELECT 'video_rejected' as type, v.title as message, v.updated_at as created_at
            FROM videos v 
            WHERE v.uploaded_by = ? AND v.status = 'rejected' AND v.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            UNION ALL
            
            SELECT 'edit_request_approved' as type, CONCAT('Düzenleme talebi onaylandı: ', v.title) as message, ver.decided_at as created_at
            FROM video_edit_requests ver
            JOIN videos v ON ver.video_id = v.id
            WHERE ver.requester_id = ? AND ver.status = 'approved' AND ver.decided_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            ORDER BY created_at DESC
            LIMIT ?
        ");
        
        $stmt->bind_param('iiii', $this->teacherId, $this->teacherId, $this->teacherId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        
        return $notifications;
    }
}

/**
 * Teacher Cache Manager Class
 */
class TeacherCacheManager {
    private $conn;
    private $hitCount = 0;
    private $missCount = 0;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
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
    
    public function getHitRate() {
        $total = $this->hitCount + $this->missCount;
        return $total > 0 ? round(($this->hitCount / $total) * 100, 2) : 0;
    }
}
?>
