<?php
/**
 * classes/QuizManager.php
 *
 * Amaç:
 *  - Quiz CRUD, onay akışı (teacher->pending->admin approve), öğrenci denemeleri,
 *    temel otomatik puanlama ve veri erişim metotlarını tek sınıfta toplamak.
 *  - Tüm metotlar ayrıntılı yorumlarla belgelenmiştir.
 *
 * Kullanım Şekli:
 *  require_once 'classes/QuizManager.php';
 *  $qm = new QuizManager($conn, $userId, $role);
 *
 * Güvenlik:
 *  - Rol kontrolü, parametre doğrulamaları ve prepared statement'lar kullanılır.
 *  - JSON döndürülecek veriler server-side filtrelenir.
 */

class QuizManager {
    /** @var mysqli */
    private $conn;
    /** @var int|null */
    private $userId;
    /** @var string */
    private $role; // 'student' | 'teacher' | 'admin' | 'guest'

    public function __construct(mysqli $conn, ?int $userId, string $role) {
        $this->conn = $conn;
        $this->userId = $userId;
        $this->role = $role;
    }

    // ==================================================
    // Yardımcılar
    // ==================================================

    private function ensureRole(array $allowed): void {
        if (!in_array($this->role, $allowed, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz işlem']);
            exit;
        }
    }

    private function now(): string {
        return date('Y-m-d H:i:s');
    }

    // ==================================================
    // Öğretmen: Quiz Oluştur / Güncelle
    // ==================================================

    /**
     * Yeni quiz oluşturur (status=draft). Sadece öğretmen.
     */
    public function createQuiz(int $videoId, string $title, string $description = '', int $durationSeconds = 0): array {
        $this->ensureRole(['teacher', 'admin']);
        if (!$this->userId) return ['success' => false, 'message' => 'Giriş gerekiyor'];

        $sql = "INSERT INTO quizzes (video_id, owner_teacher_id, title, description, duration_seconds, total_points, status, version, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 0, 'draft', 1, NOW(), NOW())";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => 'DB prepare hatası'];
        $stmt->bind_param('i ss si', $videoId, $this->userId, $title, $description, $durationSeconds);
        // Yukarıdaki boşluklar PHP parser'ını karıştırabilir; bu nedenle farklı bir yaklaşım:
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO quizzes (video_id, owner_teacher_id, title, description, duration_seconds, total_points, status, version, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 0, 'draft', 1, NOW(), NOW())");
        if (!$stmt) return ['success' => false, 'message' => 'DB prepare hatası'];
        $stmt->bind_param('iissi', $videoId, $this->userId, $title, $description, $durationSeconds);
        $ok = $stmt->execute();
        $id = $ok ? $stmt->insert_id : 0;
        $stmt->close();
        return ['success' => $ok, 'quiz_id' => (int)$id];
    }

    /**
     * Quiz meta güncelle (title/description/duration). Sadece sahibi öğretmen veya admin.
     */
    public function updateQuizMeta(int $quizId, string $title, string $description, int $durationSeconds): array {
        $this->ensureRole(['teacher', 'admin']);
        if (!$this->canEditQuiz($quizId)) return ['success' => false, 'message' => 'Yetki yok'];
        $stmt = $this->conn->prepare("UPDATE quizzes SET title=?, description=?, duration_seconds=?, updated_at=NOW() WHERE id=?");
        if (!$stmt) return ['success' => false, 'message' => 'DB prepare hatası'];
        $stmt->bind_param('ssii', $title, $description, $durationSeconds, $quizId);
        $ok = $stmt->execute();
        $stmt->close();
        return ['success' => $ok];
    }

    /** Öğretmenin kendi quizi üzerinde yetkisi var mı? Admin her zaman düzenleyebilir. */
    private function canEditQuiz(int $quizId): bool {
        if ($this->role === 'admin') return true;
        $stmt = $this->conn->prepare("SELECT owner_teacher_id FROM quizzes WHERE id=?");
        $stmt->bind_param('i', $quizId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$res) return false;
        return ((int)$res['owner_teacher_id'] === (int)$this->userId);
    }

    // ==================================================
    // Sorular
    // ==================================================

    public function addQuestion(int $quizId, string $type, string $text, string $explanation, int $points, int $orderNo): array {
        $this->ensureRole(['teacher', 'admin']);
        if (!$this->canEditQuiz($quizId)) return ['success' => false, 'message' => 'Yetki yok'];
        $allowed = ['mcq','multi','truefalse','short','numeric'];
        if (!in_array($type, $allowed, true)) $type = 'mcq';
        $stmt = $this->conn->prepare("INSERT INTO quiz_questions (quiz_id, type, text, explanation, points, order_no, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if (!$stmt) return ['success' => false, 'message' => 'DB prepare hatası'];
        $stmt->bind_param('isssii', $quizId, $type, $text, $explanation, $points, $orderNo);
        $ok = $stmt->execute();
        $qid = $ok ? $stmt->insert_id : 0;
        $stmt->close();
        return ['success' => $ok, 'question_id' => (int)$qid];
    }

    public function addOption(int $questionId, string $text, bool $isCorrect = false): array {
        $this->ensureRole(['teacher', 'admin']);
        $stmt = $this->conn->prepare("INSERT INTO quiz_options (question_id, text, is_correct, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        if (!$stmt) return ['success' => false, 'message' => 'DB prepare hatası'];
        $i = $isCorrect ? 1 : 0;
        $stmt->bind_param('isi', $questionId, $text, $i);
        $ok = $stmt->execute();
        $oid = $ok ? $stmt->insert_id : 0;
        $stmt->close();
        return ['success' => $ok, 'option_id' => (int)$oid];
    }

    public function setAnswerKeySingleCorrect(int $questionId, int $optionId): array {
        $this->ensureRole(['teacher', 'admin']);
        // önce tüm seçenekleri sıfırla
        $stmt = $this->conn->prepare("UPDATE quiz_options SET is_correct=0 WHERE question_id=?");
        $stmt->bind_param('i', $questionId);
        $stmt->execute();
        $stmt->close();
        // sonra tek doğruyu işaretle
        $stmt = $this->conn->prepare("UPDATE quiz_options SET is_correct=1 WHERE id=? AND question_id=?");
        $stmt->bind_param('ii', $optionId, $questionId);
        $ok = $stmt->execute();
        $stmt->close();
        return ['success' => $ok];
    }

    // ==================================================
    // Onay Akışı
    // ==================================================

    public function submitForApproval(int $quizId): array {
        $this->ensureRole(['teacher', 'admin']);
        if ($this->role === 'teacher' && !$this->canEditQuiz($quizId)) return ['success' => false, 'message' => 'Yetki yok'];
        $stmt = $this->conn->prepare("UPDATE quizzes SET status='pending', updated_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $quizId);
        $ok = $stmt->execute();
        $stmt->close();
        return ['success' => $ok];
    }

    public function approveQuiz(int $quizId, string $note = ''): array {
        $this->ensureRole(['admin']);
        $stmt = $this->conn->prepare("UPDATE quizzes SET status='approved', approved_by=?, approved_at=NOW(), updated_at=NOW() WHERE id=?");
        $stmt->bind_param('ii', $this->userId, $quizId);
        $ok = $stmt->execute();
        $stmt->close();
        // review kaydı
        $stmt = $this->conn->prepare("INSERT INTO quiz_reviews (quiz_id, reviewer_id, decision, note, created_at) VALUES (?, ?, 'approved', ?, NOW())");
        $stmt->bind_param('iis', $quizId, $this->userId, $note);
        $stmt->execute();
        $stmt->close();
        return ['success' => $ok];
    }

    public function rejectQuiz(int $quizId, string $note = ''): array {
        $this->ensureRole(['admin']);
        $stmt = $this->conn->prepare("UPDATE quizzes SET status='rejected', approved_by=NULL, approved_at=NULL, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $quizId);
        $ok = $stmt->execute();
        $stmt->close();
        $stmt = $this->conn->prepare("INSERT INTO quiz_reviews (quiz_id, reviewer_id, decision, note, created_at) VALUES (?, ?, 'rejected', ?, NOW())");
        $stmt->bind_param('iis', $quizId, $this->userId, $note);
        $stmt->execute();
        $stmt->close();
        return ['success' => $ok];
    }

    // ==================================================
    // Öğrenci: Quiz Alma / Başlatma / Cevap Kaydetme / Gönderme
    // ==================================================

    /** Onaylı quiz verisini (öğrenciye uygun) getirir. */
    public function getApprovedQuizForStudent(int $quizId): array {
        $stmt = $this->conn->prepare("SELECT id, video_id, title, description, duration_seconds, total_points FROM quizzes WHERE id=? AND status='approved'");
        $stmt->bind_param('i', $quizId);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$quiz) return ['success' => false, 'message' => 'Quiz bulunamadı veya onaylı değil'];

        // sorular
        $stmt = $this->conn->prepare("SELECT id, type, text, explanation, points, order_no FROM quiz_questions WHERE quiz_id=? ORDER BY order_no ASC, id ASC");
        $stmt->bind_param('i', $quizId);
        $stmt->execute();
        $qs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // seçenekler (is_correct öğrenciye gönderilmez)
        $questions = [];
        foreach ($qs as $q) {
            $qid = (int)$q['id'];
            $stmt = $this->conn->prepare("SELECT id, text FROM quiz_options WHERE question_id=? ORDER BY id ASC");
            $stmt->bind_param('i', $qid);
            $stmt->execute();
            $opts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $q['options'] = $opts;
            $questions[] = $q;
        }

        $quiz['questions'] = $questions;
        return ['success' => true, 'quiz' => $quiz];
    }

    public function startAttempt(int $quizId): array {
        $this->ensureRole(['student']);
        if (!$this->userId) return ['success' => false, 'message' => 'Giriş gerekiyor'];
        // tek aktif attempt bırakmak için mevcut in_progress varsa onu döndür
        $stmt = $this->conn->prepare("SELECT id FROM quiz_attempts WHERE quiz_id=? AND student_id=? AND status='in_progress' ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('ii', $quizId, $this->userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) return ['success' => true, 'attempt_id' => (int)$row['id']];

        $stmt = $this->conn->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, status, started_at, time_spent_seconds) VALUES (?, ?, 'in_progress', NOW(), 0)");
        $stmt->bind_param('ii', $quizId, $this->userId);
        $ok = $stmt->execute();
        $id = $ok ? $stmt->insert_id : 0;
        $stmt->close();
        return ['success' => $ok, 'attempt_id' => (int)$id];
    }

    public function saveAnswer(int $attemptId, array $answerPayload, int $timeSpentSec = 0): array {
        $this->ensureRole(['student']);
        // attempt öğrenciye ait mi?
        $stmt = $this->conn->prepare("SELECT quiz_id FROM quiz_attempts WHERE id=? AND student_id=? AND status='in_progress'");
        $stmt->bind_param('ii', $attemptId, $this->userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return ['success' => false, 'message' => 'Attempt bulunamadı'];

        // mevcut yanıtları al, merge et
        $stmt = $this->conn->prepare("SELECT answers_json FROM quiz_attempts WHERE id=?");
        $stmt->bind_param('i', $attemptId);
        $stmt->execute();
        $cur = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $existing = [];
        if ($cur && !empty($cur['answers_json'])) {
            $decoded = json_decode($cur['answers_json'], true);
            if (is_array($decoded)) $existing = $decoded;
        }
        // merge: question_id -> value
        foreach ($answerPayload as $qid => $val) {
            $existing[$qid] = $val;
        }
        $json = json_encode($existing, JSON_UNESCAPED_UNICODE);

        $stmt = $this->conn->prepare("UPDATE quiz_attempts SET answers_json=?, time_spent_seconds=? WHERE id=?");
        $stmt->bind_param('sii', $json, $timeSpentSec, $attemptId);
        $ok = $stmt->execute();
        $stmt->close();
        return ['success' => $ok];
    }

    public function submitAttempt(int $attemptId): array {
        $this->ensureRole(['student']);
        // attempt doğrulama
        $stmt = $this->conn->prepare("SELECT qa.quiz_id, q.duration_seconds, qa.answers_json FROM quiz_attempts qa JOIN quizzes q ON q.id=qa.quiz_id WHERE qa.id=? AND qa.student_id=? AND qa.status='in_progress'");
        $stmt->bind_param('ii', $attemptId, $this->userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return ['success' => false, 'message' => 'Gönderim yapılamıyor'];

        $quizId = (int)$row['quiz_id'];
        $answers = $row['answers_json'] ? json_decode($row['answers_json'], true) : [];
        $score = $this->autoScore($quizId, $answers);

        $stmt = $this->conn->prepare("UPDATE quiz_attempts SET status='submitted', submitted_at=NOW(), score=? WHERE id=?");
        $stmt->bind_param('ii', $score, $attemptId);
        $ok = $stmt->execute();
        $stmt->close();

        return ['success' => $ok, 'score' => $score];
    }

    /** Basit otomatik puanlama (MCQ/TrueFalse/Single-correct odaklı) */
    private function autoScore(int $quizId, array $answers): int {
        $total = 0;
        // tüm soruları ve seçenekleri çek
        $stmt = $this->conn->prepare("SELECT id, type, points FROM quiz_questions WHERE quiz_id=?");
        $stmt->bind_param('i', $quizId);
        $stmt->execute();
        $qs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($qs as $q) {
            $qid = (int)$q['id'];
            $points = (int)$q['points'];
            $type = $q['type'];
            $given = $answers[$qid] ?? null; // beklenen: tek seçimde option_id, true/false'ta bool, vs.

            if ($type === 'truefalse') {
                // correct option tek olmalı, onu getir
                $stmt = $this->conn->prepare("SELECT is_correct FROM quiz_options WHERE question_id=? AND ((text='true') OR (text='false')) ORDER BY is_correct DESC LIMIT 1");
                $stmt->bind_param('i', $qid);
                $stmt->execute();
                $opt = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($opt && (int)$opt['is_correct'] === (int)!!$given) $total += $points;
            } elseif ($type === 'mcq' || $type === 'numeric' || $type === 'short') {
                // mcq: tek doğru option_id kontrolü, numeric/short: şimdilik birebir eşit kontrol (ileride normalize)
                if ($type === 'mcq') {
                    $stmt = $this->conn->prepare("SELECT id FROM quiz_options WHERE question_id=? AND is_correct=1 LIMIT 1");
                    $stmt->bind_param('i', $qid);
                    $stmt->execute();
                    $opt = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($opt && (int)$opt['id'] === (int)$given) $total += $points;
                } elseif ($type === 'numeric' || $type === 'short') {
                    // basit kontrol: doğru cevap options.text içinde tutuluyor varsayımı
                    $stmt = $this->conn->prepare("SELECT text FROM quiz_options WHERE question_id=? AND is_correct=1 LIMIT 1");
                    $stmt->bind_param('i', $qid);
                    $stmt->execute();
                    $opt = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($opt && isset($given)) {
                        if (trim(mb_strtolower((string)$opt['text'])) === trim(mb_strtolower((string)$given))) {
                            $total += $points;
                        }
                    }
                }
            } elseif ($type === 'multi') {
                // Çoklu doğru: tüm doğru seçenekler verildiyse puan
                $stmt = $this->conn->prepare("SELECT id FROM quiz_options WHERE question_id=? AND is_correct=1 ORDER BY id");
                $stmt->bind_param('i', $qid);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                $correctIds = array_map(fn($r) => (int)$r['id'], $rows);
                $givenArr = array_map('intval', is_array($given) ? $given : []);
                sort($correctIds); sort($givenArr);
                if ($correctIds === $givenArr) $total += $points;
            }
        }
        return $total;
    }

    // ==================================================
    // Genel Erişim / Yardımcı
    // ==================================================

    /** Videoya bağlı onaylı quiz var mı? */
    public function getQuizForVideo(int $videoId): array {
        $stmt = $this->conn->prepare("SELECT id, title, duration_seconds, total_points, status FROM quizzes WHERE video_id=? ORDER BY status='approved' DESC, id DESC LIMIT 1");
        $stmt->bind_param('i', $videoId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return ['success' => true, 'quiz' => null];
        return ['success' => true, 'quiz' => $row];
    }
}
