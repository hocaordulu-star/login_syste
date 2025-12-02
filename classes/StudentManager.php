<?php
/**
 * StudentManager
 * Öğrenciye ait video, quiz ve ödev işlemlerini yürüten servis sınıfı.
 * Uygulama genelinde yalnızca hazırlandığı kullanıcı (userId) bağlamında çalışır.
 */
class StudentManager {
    /**
     * Veritabanı bağlantısı (mysqli)
     * @var \mysqli
     */
    private $conn;
    /**
     * Oturumdaki öğrenci kimliği
     * @var int
     */
    private $userId;
    
    /**
     * @param \mysqli $connection Veritabanı bağlantısı
     * @param int      $userId     Oturumdaki öğrenci ID'si
     */
    public function __construct($connection, $userId) {
        $this->conn = $connection;
        $this->userId = $userId;
    }
    
    /**
     * Öğrenci dashboard istatistiklerini getir
     */
    public function getDashboardStats() {
        try {
            $stats = [
                'videos' => $this->getVideoStats(),
                'progress' => $this->getProgressStats(),
                'assignments' => $this->getAssignmentStats(),
                'achievements' => $this->getAchievementStats()
            ];
            
            return $stats;
        } catch (Exception $e) {
            error_log("StudentManager getDashboardStats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Video istatistiklerini getir
     */
    private function getVideoStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT vp.video_id) as watched_videos,
                COUNT(DISTINCT CASE WHEN vp.is_completed = 1 THEN vp.video_id END) as completed_videos,
                COALESCE(SUM(vp.watch_duration), 0) as total_watch_time,
                COUNT(DISTINCT vb.video_id) as bookmarked_videos
            FROM video_progress vp
            LEFT JOIN video_bookmarks vb ON vb.user_id = vp.user_id
            WHERE vp.user_id = ?
        ");
        
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'watched_videos' => (int)($result['watched_videos'] ?? 0),
            'completed_videos' => (int)($result['completed_videos'] ?? 0),
            'total_watch_time' => (int)($result['total_watch_time'] ?? 0),
            'bookmarked_videos' => (int)($result['bookmarked_videos'] ?? 0)
        ];
    }
    
    /**
     * İlerleme istatistiklerini getir
     */
    private function getProgressStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                AVG(vp.completion_percentage) as avg_completion,
                COUNT(DISTINCT CASE WHEN vp.completion_percentage >= 90 THEN vp.video_id END) as mastered_topics,
                COUNT(DISTINCT vp.video_id) as total_started
            FROM video_progress vp
            WHERE vp.user_id = ?
        ");
        
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'avg_completion' => round($result['avg_completion'] ?? 0, 1),
            'mastered_topics' => (int)($result['mastered_topics'] ?? 0),
            'total_started' => (int)($result['total_started'] ?? 0)
        ];
    }
    
    /**
     * Ödev istatistiklerini getir
     */
    private function getAssignmentStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_assignments,
                COUNT(CASE WHEN asub.status = 'submitted' THEN 1 END) as submitted,
                COUNT(CASE WHEN asub.status = 'graded' THEN 1 END) as graded,
                AVG(CASE WHEN asub.score IS NOT NULL THEN asub.score END) as avg_score
            FROM assignments a
            LEFT JOIN assignment_submissions asub ON asub.assignment_id = a.id AND asub.student_id = ?
            WHERE a.is_active = 1 AND (a.grade = (SELECT grade FROM users WHERE id = ?) OR a.grade = 'all')
        ");
        
        $stmt->bind_param("ii", $this->userId, $this->userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'total_assignments' => (int)($result['total_assignments'] ?? 0),
            'submitted' => (int)($result['submitted'] ?? 0),
            'graded' => (int)($result['graded'] ?? 0),
            'avg_score' => round($result['avg_score'] ?? 0, 1)
        ];
    }
    
    /**
     * Başarım istatistiklerini getir
     */
    private function getAchievementStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT qa.quiz_id) as quizzes_taken,
                COUNT(DISTINCT CASE WHEN qa.is_passed = 1 THEN qa.quiz_id END) as quizzes_passed,
                AVG(qa.percentage) as avg_quiz_score,
                COUNT(DISTINCT vn.video_id) as videos_with_notes
            FROM quiz_attempts qa
            LEFT JOIN video_notes vn ON vn.user_id = qa.user_id
            WHERE qa.user_id = ?
        ");
        
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'quizzes_taken' => (int)($result['quizzes_taken'] ?? 0),
            'quizzes_passed' => (int)($result['quizzes_passed'] ?? 0),
            'avg_quiz_score' => round($result['avg_quiz_score'] ?? 0, 1),
            'videos_with_notes' => (int)($result['videos_with_notes'] ?? 0)
        ];
    }
    
    /**
     * Öğrenci için uygun videoları getir.
     *
     * Grade null ise öğrencinin kendi sınıfı kullanılır; '' veya 'all' tüm sınıflar demektir.
     * Birleştirilmiş sorgu; ilerleme, yer imi ve istatistikleri tek seferde döndürür.
     *
     * @param string|int|null $grade  Sınıf filtresi (null, ''/'all' veya 5-12 gibi)
     * @param string          $subject Ders (varsayılan: 'math')
     * @param int             $page    Sayfa
     * @param int             $limit   Sayfa başı kayıt
     * @return array { videos: array, total: int, page: int, pages: int }
     */
    public function getAvailableVideos($grade = null, $subject = 'math', $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Öğrencinin sınıfını al (sadece NULL ise). Boş string ('') tüm sınıflar demektir.
            if ($grade === null) {
                $stmt = $this->conn->prepare("SELECT grade FROM users WHERE id = ?");
                $stmt->bind_param("i", $this->userId);
                $stmt->execute();
                $grade = $stmt->get_result()->fetch_assoc()['grade'] ?? '5';
            }
            
            // '' veya 'all' değerlerinde sınıf filtresi uygulanmaz
            $applyGrade = !($grade === '' || strtolower((string)$grade) === 'all');
            
            // Dinamik SQL: grade filtresi varsa ekle
            $sql = "
                SELECT 
                    v.*,
                    u.title as unit_title,
                    COALESCE(vp.completion_percentage, 0) as progress,
                    COALESCE(vp.is_completed, 0) as is_completed,
                    COALESCE(vb.id, 0) as is_bookmarked,
                    COALESCE(vs.total_views, 0) as view_count,
                    COALESCE(vs.average_quiz_score, 0) as avg_quiz_score
                FROM videos v
                LEFT JOIN units u ON u.id = v.unit_id
                LEFT JOIN video_progress vp ON vp.video_id = v.id AND vp.user_id = ?
                LEFT JOIN video_bookmarks vb ON vb.video_id = v.id AND vb.user_id = ?
                LEFT JOIN video_statistics vs ON vs.video_id = v.id
                WHERE v.status = 'approved' 
                  AND (v.subject = ? OR v.subject IS NULL OR v.subject = '')
            ";
            if ($applyGrade) {
                // Dinamik grade filtresi: video belirli bir sınıfa veya 'all' etiketine sahip olabilir
                $sql .= " AND (v.grade = ? OR v.grade = 'all' OR v.grade IS NULL OR v.grade = '') ";
            }
            $sql .= " ORDER BY u.unit_order ASC, v.created_at DESC LIMIT ? OFFSET ? ";

            $stmt = $this->conn->prepare($sql);
            if ($applyGrade) {
                $stmt->bind_param("iissii", $this->userId, $this->userId, $subject, $grade, $limit, $offset);
            } else {
                $stmt->bind_param("iisii", $this->userId, $this->userId, $subject, $limit, $offset);
            }
            $stmt->execute();
            $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Toplam kayıt sayısı için ayrı, hafif bir sayım sorgusu
            $countSql = "
                SELECT COUNT(*) as total
                FROM videos v
                WHERE v.status = 'approved'
                  AND (v.subject = ? OR v.subject IS NULL OR v.subject = '')
            ";
            if ($applyGrade) {
                $countSql .= " AND (v.grade = ? OR v.grade = 'all' OR v.grade IS NULL OR v.grade = '') ";
            }
            $countStmt = $this->conn->prepare($countSql);
            if ($applyGrade) {
                $countStmt->bind_param("ss", $subject, $grade);
            } else {
                $countStmt->bind_param("s", $subject);
            }
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            return [
                'videos' => $videos,
                'total' => (int)$total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("StudentManager getAvailableVideos error: " . $e->getMessage());
            return ['videos' => [], 'total' => 0, 'page' => 1, 'pages' => 0];
        }
    }
    
    /**
     * Video izleme ilerlemesini kaydet
     */
    public function updateVideoProgress($videoId, $watchDuration, $totalDuration) {
        try {
            $stmt = $this->conn->prepare("CALL UpdateVideoProgress(?, ?, ?, ?)");
            $stmt->bind_param("iiii", $this->userId, $videoId, $watchDuration, $totalDuration);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'İlerleme kaydedildi'];
        } catch (Exception $e) {
            error_log("StudentManager updateVideoProgress error: " . $e->getMessage());
            return ['success' => false, 'message' => 'İlerleme kaydedilemedi'];
        }
    }
    

    /**
     * Video bookmark ekle/kaldır
     */
    public function toggleBookmark($videoId, $notes = '') {
        try {
            // Mevcut bookmark kontrolü
            $stmt = $this->conn->prepare("SELECT id FROM video_bookmarks WHERE user_id = ? AND video_id = ?");
            $stmt->bind_param("ii", $this->userId, $videoId);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            
            if ($exists) {
                // Bookmark kaldır
                $stmt = $this->conn->prepare("DELETE FROM video_bookmarks WHERE user_id = ? AND video_id = ?");
                $stmt->bind_param("ii", $this->userId, $videoId);
                $stmt->execute();
                return ['success' => true, 'action' => 'removed', 'message' => 'Favorilerden kaldırıldı'];
            } else {
                // Bookmark ekle
                $stmt = $this->conn->prepare("INSERT INTO video_bookmarks (user_id, video_id, notes) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $this->userId, $videoId, $notes);
                $stmt->execute();
                return ['success' => true, 'action' => 'added', 'message' => 'Favorilere eklendi'];
            }
        } catch (Exception $e) {
            error_log("StudentManager toggleBookmark error: " . $e->getMessage());
            return ['success' => false, 'message' => 'İşlem gerçekleştirilemedi'];
        }
    }
    
    /**
     * Video notu ekle
     */
    public function addVideoNote($videoId, $timestampSeconds, $noteText, $noteType = 'personal') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO video_notes (user_id, video_id, timestamp_seconds, note_text, note_type) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiiss", $this->userId, $videoId, $timestampSeconds, $noteText, $noteType);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Not eklendi', 'note_id' => $this->conn->insert_id];
        } catch (Exception $e) {
            error_log("StudentManager addVideoNote error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Not eklenemedi'];
        }
    }
    
    /**
     * Video notlarını getir
     */
    public function getVideoNotes($videoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM video_notes 
                WHERE user_id = ? AND video_id = ? 
                ORDER BY timestamp_seconds ASC
            ");
            $stmt->bind_param("ii", $this->userId, $videoId);
            $stmt->execute();
            $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return ['success' => true, 'notes' => $notes];
        } catch (Exception $e) {
            error_log("StudentManager getVideoNotes error: " . $e->getMessage());
            return ['success' => false, 'notes' => []];
        }
    }
    
    /**
     * Quiz başlat
     */
    public function startQuiz($quizId) {
        try {
            // Quiz bilgilerini al
            $stmt = $this->conn->prepare("
                SELECT q.*, v.title as video_title
                FROM quizzes q
                JOIN videos v ON v.id = q.video_id
                WHERE q.id = ? AND q.is_active = 1
            ");
            $stmt->bind_param("i", $quizId);
            $stmt->execute();
            $quiz = $stmt->get_result()->fetch_assoc();
            
            if (!$quiz) {
                return ['success' => false, 'message' => 'Quiz bulunamadı'];
            }
            
            // Quiz sorularını al
            $stmt = $this->conn->prepare("
                SELECT * FROM quiz_questions 
                WHERE quiz_id = ? 
                ORDER BY question_order ASC
            ");
            $stmt->bind_param("i", $quizId);
            $stmt->execute();
            $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return [
                'success' => true,
                'quiz' => $quiz,
                'questions' => $questions
            ];
        } catch (Exception $e) {
            error_log("StudentManager startQuiz error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Quiz başlatılamadı'];
        }
    }
    
    /**
     * Quiz cevaplarını kaydet
     */
    public function submitQuizAnswers($quizId, $answers, $timeTaken) {
        try {
            // Quiz sorularını ve doğru cevapları al
            $stmt = $this->conn->prepare("
                SELECT qq.*, q.passing_score
                FROM quiz_questions qq
                JOIN quizzes q ON q.id = qq.quiz_id
                WHERE qq.quiz_id = ?
            ");
            $stmt->bind_param("i", $quizId);
            $stmt->execute();
            $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $totalScore = 0;
            $maxScore = 0;
            $correctAnswers = 0;
            
            foreach ($questions as $question) {
                $maxScore += $question['points'];
                $userAnswer = $answers[$question['id']] ?? '';
                
                if (strtolower(trim($userAnswer)) === strtolower(trim($question['correct_answer']))) {
                    $totalScore += $question['points'];
                    $correctAnswers++;
                }
            }
            
            $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
            $isPassed = $percentage >= $questions[0]['passing_score'];
            
            // Deneme sayısını hesapla
            $stmt = $this->conn->prepare("
                SELECT COALESCE(MAX(attempt_number), 0) + 1 as next_attempt
                FROM quiz_attempts 
                WHERE user_id = ? AND quiz_id = ?
            ");
            $stmt->bind_param("ii", $this->userId, $quizId);
            $stmt->execute();
            $attemptNumber = $stmt->get_result()->fetch_assoc()['next_attempt'];
            
            // Sonucu kaydet
            $stmt = $this->conn->prepare("
                INSERT INTO quiz_attempts 
                (user_id, quiz_id, score, max_score, percentage, is_passed, time_taken_minutes, attempt_number, answers, completed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $answersJson = json_encode($answers);
            $stmt->bind_param("iiiidiiis", $this->userId, $quizId, $totalScore, $maxScore, $percentage, $isPassed, $timeTaken, $attemptNumber, $answersJson);
            $stmt->execute();
            
            return [
                'success' => true,
                'score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => round($percentage, 1),
                'is_passed' => $isPassed,
                'correct_answers' => $correctAnswers,
                'total_questions' => count($questions),
                'attempt_number' => $attemptNumber
            ];
            
        } catch (Exception $e) {
            error_log("StudentManager submitQuizAnswers error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Quiz sonucu kaydedilemedi'];
        }
    }
    
    /**
     * Öğrenci ödevlerini getir
     */
    public function getAssignments($status = 'all', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Öğrencinin sınıfını al
            $stmt = $this->conn->prepare("SELECT grade FROM users WHERE id = ?");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $studentGrade = $stmt->get_result()->fetch_assoc()['grade'] ?? '5';
            
            $whereClause = "WHERE a.is_active = 1 AND (a.grade = ? OR a.grade = 'all')";
            $params = [$studentGrade];
            $types = "s";
            
            if ($status !== 'all') {
                $whereClause .= " AND COALESCE(asub.status, 'not_submitted') = ?";
                $params[] = $status;
                $types .= "s";
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    a.*,
                    u.first_name, u.last_name,
                    asub.status as submission_status,
                    asub.score,
                    asub.feedback,
                    asub.submitted_at,
                    asub.graded_at,
                    CASE 
                        WHEN a.due_date < NOW() AND asub.id IS NULL THEN 'overdue'
                        WHEN asub.id IS NULL THEN 'not_submitted'
                        ELSE COALESCE(asub.status, 'not_submitted')
                    END as current_status
                FROM assignments a
                JOIN users u ON u.id = a.teacher_id
                LEFT JOIN assignment_submissions asub ON asub.assignment_id = a.id AND asub.student_id = ?
                $whereClause
                ORDER BY a.due_date ASC
                LIMIT ? OFFSET ?
            ");
            
            $params = array_merge([$this->userId], $params, [$limit, $offset]);
            $types .= "ii";
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return ['success' => true, 'assignments' => $assignments];
        } catch (Exception $e) {
            error_log("StudentManager getAssignments error: " . $e->getMessage());
            return ['success' => false, 'assignments' => []];
        }
    }
    
    /**
     * Favori videoları getir
     */
    public function getBookmarkedVideos($page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->conn->prepare("
                SELECT 
                    v.*,
                    u.title as unit_title,
                    vb.notes as bookmark_notes,
                    vb.created_at as bookmarked_at,
                    COALESCE(vp.completion_percentage, 0) as progress
                FROM video_bookmarks vb
                JOIN videos v ON v.id = vb.video_id
                LEFT JOIN units u ON u.id = v.unit_id
                LEFT JOIN video_progress vp ON vp.video_id = v.id AND vp.user_id = ?
                WHERE vb.user_id = ?
                ORDER BY vb.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->bind_param("iiii", $this->userId, $this->userId, $limit, $offset);
            $stmt->execute();
            $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return ['success' => true, 'videos' => $videos];
        } catch (Exception $e) {
            error_log("StudentManager getBookmarkedVideos error: " . $e->getMessage());
            return ['success' => false, 'videos' => []];
        }
    }
    
    /**
     * Son aktiviteleri getir.
     *
     * Tek sonuç listesinde 3 farklı etkinlik türü bir UNION ile birleştirilir:
     * - video_watched: Kullanıcının izlediği videolar (son izlenme zamanı)
     * - quiz_completed: Tamamlanan quizler (quiz adı + video başlığı)
     * - assignment_submitted: Gönderilen ödevler
     * En yeni faaliyetler üstte olacak şekilde tarihe göre sıralanır.
     *
     * @param int $limit Gösterilecek kayıt sayısı
     * @return array { success: bool, activities: array }
     */
    public function getRecentActivity($limit = 10) {
        try {
            // UNION blokları sırasıyla: video izleme, quiz tamamlama ve ödev gönderimi
            // Not: Sorgu içinde yorum kullanmıyoruz; açıklamalar PHP tarafında tutulur.
            $stmt = $this->conn->prepare("\n                (SELECT 'video_watched' as type, v.title as title, vp.last_watched_at as date, v.id as resource_id
                FROM video_progress vp 
                JOIN videos v ON v.id = vp.video_id 
                 WHERE vp.user_id = ?)
                UNION ALL
                (SELECT 'quiz_completed' as type, CONCAT(q.title, ' - ', v.title) as title, qa.completed_at as date, q.id as resource_id
                 FROM quiz_attempts qa 
                 JOIN quizzes q ON q.id = qa.quiz_id 
                 JOIN videos v ON v.id = q.video_id 
                 WHERE qa.user_id = ? AND qa.completed_at IS NOT NULL)
                UNION ALL
                (SELECT 'assignment_submitted' as type, a.title as title, asub.submitted_at as date, a.id as resource_id
                 FROM assignment_submissions asub 
                 JOIN assignments a ON a.id = asub.assignment_id 
                 WHERE asub.student_id = ?)
                ORDER BY date DESC
                LIMIT ?
            ");
            
            $stmt->bind_param("iiii", $this->userId, $this->userId, $this->userId, $limit);
            $stmt->execute();
            $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return ['success' => true, 'activities' => $activities];
        } catch (Exception $e) {
            error_log("StudentManager getRecentActivity error: " . $e->getMessage());
            return ['success' => false, 'activities' => []];
        }
    }
}
?>
