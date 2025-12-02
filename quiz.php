<?php
/**
 * quiz.php
 *
 * Amaç:
 *  - Quiz sistemi için AJAX router. İstemciden gelen action parametresine göre
 *    uygun metodu çağırır ve JSON döndürür.
 *  - Rol bazlı yetkilendirme içerir (student/teacher/admin).
 *  - Tüm uç noktalar ayrıntılı yorumlarla belgelidir.
 *
 * Notlar:
 *  - CSRF koruması için token mekanizması eklenebilir (TODO).
 *  - Hatalar HTTP status ve JSON mesajlarıyla sade şekilde döndürülür.
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/QuizManager.php';

$role = $_SESSION['role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;

$qm = new QuizManager($conn, $userId, $role);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!$action) {
    echo json_encode(['success' => false, 'message' => 'action eksik']);
    exit;
}

try {
    switch ($action) {
        // =============================================
        // Genel / Ortak
        // =============================================
        case 'get_quiz_for_video': { // video_view.php içinden kullanılacak
            $videoId = (int)($_POST['video_id'] ?? $_GET['video_id'] ?? 0);
            $res = $qm->getQuizForVideo($videoId);
            echo json_encode($res);
            break;
        }

        // =============================================
        // Öğretmen: CRUD + Onaya Gönderme
        // =============================================
        case 'create_quiz': {
            if (!in_array($role, ['teacher','admin'], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $videoId = (int)($_POST['video_id'] ?? 0);
            $title = trim($_POST['title'] ?? 'Yeni Quiz');
            $description = trim($_POST['description'] ?? '');
            $duration = (int)($_POST['duration_seconds'] ?? 0);
            echo json_encode($qm->createQuiz($videoId, $title, $description, $duration));
            break;
        }
        case 'update_quiz_meta': {
            if (!in_array($role, ['teacher','admin'], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $quizId = (int)($_POST['quiz_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $duration = (int)($_POST['duration_seconds'] ?? 0);
            echo json_encode($qm->updateQuizMeta($quizId, $title, $description, $duration));
            break;
        }
        case 'add_question': {
            if (!in_array($role, ['teacher','admin'], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $quizId = (int)($_POST['quiz_id'] ?? 0);
            $type = trim($_POST['type'] ?? 'mcq');
            $text = (string)($_POST['text'] ?? '');
            $explanation = (string)($_POST['explanation'] ?? '');
            $points = (int)($_POST['points'] ?? 1);
            $orderNo = (int)($_POST['order_no'] ?? 1);
            echo json_encode($qm->addQuestion($quizId, $type, $text, $explanation, $points, $orderNo));
            break;
        }
        case 'add_option': {
            if (!in_array($role, ['teacher','admin'], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $questionId = (int)($_POST['question_id'] ?? 0);
            $text = (string)($_POST['text'] ?? '');
            $isCorrect = (int)($_POST['is_correct'] ?? 0) === 1;
            echo json_encode($qm->addOption($questionId, $text, $isCorrect));
            break;
        }
        case 'set_answer_key_single': {
            if (!in_array($role, ['teacher','admin'], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $questionId = (int)($_POST['question_id'] ?? 0);
            $optionId = (int)($_POST['option_id'] ?? 0);
            echo json_encode($qm->setAnswerKeySingleCorrect($questionId, $optionId));
            break;
        }
        case 'submit_for_approval': {
            if (!in_array($role, ['teacher','admin'], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $quizId = (int)($_POST['quiz_id'] ?? 0);
            echo json_encode($qm->submitForApproval($quizId));
            break;
        }

        // =============================================
        // Admin: Onay/Red
        // =============================================
        case 'approve_quiz': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $quizId = (int)($_POST['quiz_id'] ?? 0);
            $note = (string)($_POST['note'] ?? '');
            echo json_encode($qm->approveQuiz($quizId, $note));
            break;
        }
        case 'reject_quiz': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $quizId = (int)($_POST['quiz_id'] ?? 0);
            $note = (string)($_POST['note'] ?? '');
            echo json_encode($qm->rejectQuiz($quizId, $note));
            break;
        }

        // =============================================
        // Öğrenci: Quiz al, attempt başlat/kaydet/gönder
        // =============================================
        case 'get_quiz': {
            $quizId = (int)($_POST['quiz_id'] ?? $_GET['quiz_id'] ?? 0);
            echo json_encode($qm->getApprovedQuizForStudent($quizId));
            break;
        }
        case 'start_attempt': {
            if ($role !== 'student') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $quizId = (int)($_POST['quiz_id'] ?? 0);
            echo json_encode($qm->startAttempt($quizId));
            break;
        }
        case 'save_answer': {
            if ($role !== 'student') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $attemptId = (int)($_POST['attempt_id'] ?? 0);
            // answers, question_id -> value (tek id ya da array ya da string)
            $payload = $_POST['answers'] ?? [];
            if (is_string($payload)) { $decoded = json_decode($payload, true); $payload = is_array($decoded) ? $decoded : []; }
            $timeSpent = (int)($_POST['time_spent'] ?? 0);
            echo json_encode($qm->saveAnswer($attemptId, $payload, $timeSpent));
            break;
        }
        case 'submit_attempt': {
            if ($role !== 'student') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Yetki yok']); break; }
            $attemptId = (int)($_POST['attempt_id'] ?? 0);
            echo json_encode($qm->submitAttempt($attemptId));
            break;
        }

        default:
            echo json_encode(['success' => false, 'message' => 'Bilinmeyen action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası', 'detail' => $e->getMessage()]);
}
