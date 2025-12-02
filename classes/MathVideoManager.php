<?php
/**
 * =====================================================
 * MATEMATİK VİDEO YÖNETİM SİSTEMİ
 * Tüm matematik sayfalarının ortak işlevlerini yöneten ana sınıf
 * =====================================================
 */

class MathVideoManager {
    private $conn;           // Veritabanı bağlantısı
    private $cache;          // Cache sistemi
    private $userId;         // Mevcut kullanıcı ID'si
    private $userRole;       // Kullanıcı rolü (student, teacher, admin)
    
    /**
     * Sınıf yapıcısı - Temel ayarları yapar
     * @param mysqli $connection Veritabanı bağlantısı
     * @param int $userId Kullanıcı ID'si
     * @param string $userRole Kullanıcı rolü
     */
    public function __construct($connection, $userId = null, $userRole = 'guest') {
        $this->conn = $connection;
        $this->userId = $userId;
        $this->userRole = $userRole;
        $this->cache = new CacheManager($connection);
    }
    
    /**
     * =====================================================
     * VİDEO LİSTELEME VE FİLTRELEME FONKSİYONLARI
     * =====================================================
     */
    
    /**
     * Belirtilen sınıf için videoları getirir (filtreleme ile)
     * @param string $grade Sınıf seviyesi (5, 6, 7, 8)
     * @param array $filters Filtre parametreleri
     * @return array Video listesi
     */
    public function getVideosForGrade($grade, $filters = []) {
        // Cache anahtarı oluştur
        $cacheKey = "videos_grade_{$grade}_" . md5(serialize($filters) . $this->userRole);
        
        // Önce cache'den kontrol et
        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        // Temel sorgu parametreleri
        $params = [];
        $types = '';
        
        // WHERE koşullarını oluştur
        // Not: Konu(subject) 'math' olmayan ama NULL/boş olanları da dahil et, sınıf filtresi 'all'/NULL/boş olanları kapsar
        $where = " WHERE (v.subject='math' OR v.subject IS NULL OR v.subject='') AND (v.grade=? OR v.grade='all' OR v.grade IS NULL OR v.grade='')";
        $params[] = $grade;
        $types .= 's';
        
        // Rol bazlı erişim kontrolü
        if ($this->userRole !== 'admin' && $this->userRole !== 'teacher') {
            $where .= " AND v.status='approved'";
        }
        
        // Ünite filtresi (unit_id ile)
        if (!empty($filters['unit_id']) && $filters['unit_id'] > 0) {
            $where .= " AND v.unit_id = ?";
            $params[] = (int)$filters['unit_id'];
            $types .= 'i';
        }
        
        // Konu filtresi
        if (!empty($filters['topic'])) {
            $where .= " AND v.topic LIKE ?";
            $params[] = '%' . trim($filters['topic']) . '%';
            $types .= 's';
        }
        
        // Genel arama (başlık, açıklama, konu, ünite)
        if (!empty($filters['q'])) {
            $searchTerm = '%' . trim($filters['q']) . '%';
            $where .= " AND (v.title LIKE ? OR v.description LIKE ? OR v.topic LIKE ? OR u.unit_name LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
        }
        
        // Admin için durum filtresi
        if ($this->userRole === 'admin' && !empty($filters['status'])) {
            $where .= " AND v.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        // Ana sorguyu hazırla - Video bilgileri + ünite bilgileri + istatistikler
        $sql = "SELECT 
                    v.id, v.user_id, v.title, v.description, v.youtube_url, 
                    v.filename, v.status, v.unit, v.unit_id, v.topic, 
                    v.created_at, v.featured,
                    u.unit_name, u.unit_order,
                    vs.total_views, vs.completion_rate, vs.like_count,
                    COALESCE(vs.total_views, 0) as view_count,
                    COALESCE(vs.completion_rate, 0) as completion_rate
                FROM videos v 
                LEFT JOIN units u ON v.unit_id = u.id 
                LEFT JOIN video_statistics vs ON v.id = vs.video_id
                {$where} 
                ORDER BY v.featured DESC, v.created_at DESC";
        
        // Sorguyu çalıştır
        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            // Her video için ek bilgiler ekle
            $row['is_bookmarked'] = $this->isVideoBookmarked($row['id']);
            $row['user_progress'] = $this->getUserVideoProgress($row['id']);
            $row['quiz_available'] = $this->hasQuiz($row['id']);
            $videos[] = $row;
        }
        $stmt->close();
        
        // Sonucu cache'e kaydet (15 dakika)
        $this->cache->set($cacheKey, $videos, 900);
        
        return $videos;
    }
    
    /**
     * Belirtilen sınıf için ünite listesini getirir
     * @param string $grade Sınıf seviyesi
     * @return array Ünite listesi
     */
    public function getUnitsForGrade($grade) {
        $cacheKey = "units_grade_{$grade}";
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        $stmt = $this->conn->prepare("
            SELECT id, unit_order, unit_name, description 
            FROM units 
            WHERE subject='math' AND grade=? AND is_active=1 
            ORDER BY unit_order
        ");
        $stmt->bind_param('s', $grade);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $units = [];
        while ($row = $result->fetch_assoc()) {
            // Her ünite için video sayısını ekle
            $row['video_count'] = $this->getVideoCountForUnit($row['id']);
            $units[] = $row;
        }
        $stmt->close();
        
        // Cache'e kaydet (1 saat)
        $this->cache->set($cacheKey, $units, 3600);
        
        return $units;
    }
    
    /**
     * =====================================================
     * KULLANICI İLERLEME TAKİBİ FONKSİYONLARI
     * =====================================================
     */
    
    /**
     * Kullanıcının video izleme ilerlemesini kaydeder
     * @param int $videoId Video ID'si
     * @param int $watchDuration İzlenen süre (saniye)
     * @param int $totalDuration Toplam video süresi
     * @return bool Başarı durumu
     */
    public function updateVideoProgress($videoId, $watchDuration, $totalDuration) {
        if (!$this->userId) return false;
        
        try {
            // Stored procedure kullanarak ilerleme güncelle
            $stmt = $this->conn->prepare("CALL UpdateVideoProgress(?, ?, ?, ?)");
            $stmt->bind_param('iiii', $this->userId, $videoId, $watchDuration, $totalDuration);
            $result = $stmt->execute();
            $stmt->close();
            
            // İlgili cache'leri temizle
            $this->cache->clearPattern("user_progress_{$this->userId}_*");
            $this->cache->clearPattern("video_stats_*");
            
            return $result;
        } catch (Exception $e) {
            error_log("Video progress update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının belirli bir videodaki ilerlemesini getirir
     * @param int $videoId Video ID'si
     * @return array|null İlerleme bilgileri
     */
    public function getUserVideoProgress($videoId) {
        if (!$this->userId) return null;
        
        $cacheKey = "user_progress_{$this->userId}_{$videoId}";
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        $stmt = $this->conn->prepare("
            SELECT watch_duration, total_duration, completion_percentage, 
                   is_completed, watch_count, last_watched_at
            FROM video_progress 
            WHERE user_id = ? AND video_id = ?
        ");
        $stmt->bind_param('ii', $this->userId, $videoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $progress = $result->fetch_assoc();
        $stmt->close();
        
        // Cache'e kaydet (5 dakika)
        $this->cache->set($cacheKey, $progress, 300);
        
        return $progress;
    }
    
    /**
     * =====================================================
     * BOOKMARK/FAVORİ SİSTEMİ FONKSİYONLARI
     * =====================================================
     */
    
    /**
     * Videoyu favorilere ekler veya çıkarır
     * @param int $videoId Video ID'si
     * @param string $notes Kullanıcı notları (opsiyonel)
     * @return array Sonuç durumu
     */
    public function toggleBookmark($videoId, $notes = '') {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Giriş yapmanız gerekiyor'];
        }
        
        try {
            // Önce bookmark var mı kontrol et
            $stmt = $this->conn->prepare("
                SELECT id FROM video_bookmarks 
                WHERE user_id = ? AND video_id = ?
            ");
            $stmt->bind_param('ii', $this->userId, $videoId);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($exists) {
                // Favorilerden çıkar
                $stmt = $this->conn->prepare("
                    DELETE FROM video_bookmarks 
                    WHERE user_id = ? AND video_id = ?
                ");
                $stmt->bind_param('ii', $this->userId, $videoId);
                $stmt->execute();
                $stmt->close();
                
                $message = 'Video favorilerden çıkarıldı';
                $action = 'removed';
            } else {
                // Favorilere ekle
                $stmt = $this->conn->prepare("
                    INSERT INTO video_bookmarks (user_id, video_id, notes) 
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param('iis', $this->userId, $videoId, $notes);
                $stmt->execute();
                $stmt->close();
                
                $message = 'Video favorilere eklendi';
                $action = 'added';
            }
            
            // Video istatistiklerini güncelle (mysqli ile doğru kullanım)
            $stmt = $this->conn->prepare("CALL UpdateVideoStatistics(?)");
            if ($stmt) {
                $stmt->bind_param('i', $videoId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Cache'leri temizle
            $this->cache->clearPattern("bookmark_*");
            $this->cache->clearPattern("video_stats_*");
            
            return [
                'success' => true, 
                'message' => $message, 
                'action' => $action
            ];
            
        } catch (Exception $e) {
            error_log("Bookmark toggle error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Bir hata oluştu'];
        }
    }
    
    /**
     * Videonun kullanıcı tarafından favorilere eklenip eklenmediğini kontrol eder
     * @param int $videoId Video ID'si
     * @return bool Favori durumu
     */
    public function isVideoBookmarked($videoId) {
        if (!$this->userId) return false;
        
        $cacheKey = "bookmark_{$this->userId}_{$videoId}";
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM video_bookmarks 
            WHERE user_id = ? AND video_id = ?
        ");
        $stmt->bind_param('ii', $this->userId, $videoId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $isBookmarked = $result['count'] > 0;
        
        // Cache'e kaydet (10 dakika)
        $this->cache->set($cacheKey, $isBookmarked, 600);
        
        return $isBookmarked;
    }
    
    /**
     * =====================================================
     * QUİZ SİSTEMİ FONKSİYONLARI
     * =====================================================
     */
    
    /**
     * Videoya ait quiz olup olmadığını kontrol eder
     * @param int $videoId Video ID'si
     * @return bool Quiz varlığı
     */
    public function hasQuiz($videoId) {
        $cacheKey = "quiz_exists_{$videoId}";
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM quizzes 
            WHERE video_id = ? AND is_active = 1
        ");
        $stmt->bind_param('i', $videoId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $hasQuiz = $result['count'] > 0;
        
        // Cache'e kaydet (30 dakika)
        $this->cache->set($cacheKey, $hasQuiz, 1800);
        
        return $hasQuiz;
    }
    
    /**
     * Video için quiz bilgilerini getirir
     * @param int $videoId Video ID'si
     * @return array|null Quiz bilgileri
     */
    public function getQuizForVideo($videoId) {
        $stmt = $this->conn->prepare("
            SELECT q.*, COUNT(qq.id) as question_count
            FROM quizzes q
            LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
            WHERE q.video_id = ? AND q.is_active = 1
            GROUP BY q.id
        ");
        $stmt->bind_param('i', $videoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $quiz = $result->fetch_assoc();
        $stmt->close();
        
        return $quiz;
    }
    
    /**
     * =====================================================
     * İSTATİSTİK VE RAPORLAMA FONKSİYONLARI
     * =====================================================
     */
    
    /**
     * Video için detaylı istatistikleri getirir
     * @param int $videoId Video ID'si
     * @return array İstatistik bilgileri
     */
    public function getVideoStatistics($videoId) {
        $cacheKey = "video_stats_{$videoId}";
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        $stmt = $this->conn->prepare("
            SELECT vs.*, 
                   AVG(vr.rating) as average_rating,
                   COUNT(vr.id) as rating_count
            FROM video_statistics vs
            LEFT JOIN video_ratings vr ON vs.video_id = vr.video_id
            WHERE vs.video_id = ?
            GROUP BY vs.video_id
        ");
        $stmt->bind_param('i', $videoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        // Cache'e kaydet (5 dakika)
        $this->cache->set($cacheKey, $stats, 300);
        
        return $stats;
    }
    
    /**
     * =====================================================
     * YARDIMCI FONKSİYONLAR
     * =====================================================
     */
    
    /**
     * Belirli bir ünite için video sayısını getirir
     * @param int $unitId Ünite ID'si
     * @return int Video sayısı
     */
    private function getVideoCountForUnit($unitId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM videos 
            WHERE unit_id = ? AND status = 'approved'
        ");
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return (int)$result['count'];
    }
    
    /**
     * Öğretmenin kendi videolarını getirir
     * @param string $grade Sınıf seviyesi
     * @return array Video listesi
     */
    public function getTeacherVideos($grade) {
        if ($this->userRole !== 'teacher' || !$this->userId) {
            return [];
        }
        
        $stmt = $this->conn->prepare("
            SELECT v.id, v.title, v.status, v.created_at, v.youtube_url, 
                   v.unit, v.topic, vs.total_views, vs.completion_rate
            FROM videos v
            LEFT JOIN video_statistics vs ON v.id = vs.video_id
            WHERE v.user_id = ? AND v.subject = 'math' AND v.grade = ? 
            ORDER BY v.created_at DESC
        ");
        $stmt->bind_param('is', $this->userId, $grade);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            $videos[] = $row;
        }
        $stmt->close();
        
        return $videos;
    }
}

/**
 * =====================================================
 * CACHE YÖNETİM SİSTEMİ
 * Performans için basit cache sistemi
 * =====================================================
 */
class CacheManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Cache'den veri getirir
     * @param string $key Cache anahtarı
     * @return mixed|null Cache verisi veya null
     */
    public function get($key) {
        try {
            $stmt = $this->conn->prepare("
                SELECT cache_data FROM system_cache 
                WHERE cache_key = ? AND expires_at > NOW()
            ");
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row) {
                return json_decode($row['cache_data'], true);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Cache get error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cache'e veri kaydeder
     * @param string $key Cache anahtarı
     * @param mixed $data Kaydedilecek veri
     * @param int $ttl Yaşam süresi (saniye)
     * @return bool Başarı durumu
     */
    public function set($key, $data, $ttl = 3600) {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
            $jsonData = json_encode($data);
            
            $stmt = $this->conn->prepare("
                INSERT INTO system_cache (cache_key, cache_data, expires_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                cache_data = VALUES(cache_data), 
                expires_at = VALUES(expires_at)
            ");
            $stmt->bind_param('sss', $key, $jsonData, $expiresAt);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Cache set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Belirli pattern'e uyan cache'leri temizler
     * @param string $pattern Pattern (örn: "user_*")
     * @return bool Başarı durumu
     */
    public function clearPattern($pattern) {
        try {
            $likePattern = str_replace('*', '%', $pattern);
            $stmt = $this->conn->prepare("DELETE FROM system_cache WHERE cache_key LIKE ?");
            $stmt->bind_param('s', $likePattern);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Cache clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Süresi dolmuş cache'leri temizler
     * @return bool Başarı durumu
     */
    public function cleanExpired() {
        try {
            $stmt = $this->conn->prepare("DELETE FROM system_cache WHERE expires_at < NOW()");
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Cache clean error: " . $e->getMessage());
            return false;
        }
    }
}
?>
