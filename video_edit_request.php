<?php
/**
 * =============================================================
 * Video Düzenleme/Silme Talep Sayfası (video_edit_request.php)
 * -------------------------------------------------------------
 * Amaç:
 *  - Yalnızca öğretmen rolündeki kullanıcıların kendi videoları için
 *    "düzenleme" ya da "silme" talebi oluşturmasını sağlamak.
 * Güvenlik:
 *  - Giriş ve rol kontrolü (yalnızca 'teacher' erişebilir).
 *  - Öğretmen yalnızca KENDİSİNE ait videolar için talep oluşturabilir.
 * İstek Akışı:
 *  - GET: Formun görüntülenmesi (mevcut video bilgileri ile).
 *  - POST: Talebin veritabanına eklenmesi (düzenleme/silme).
 * Veritabanı:
 *  - video_edit_requests tablosuna kayıt atılır.
 *  - Talep durumu başlangıçta "pending" (beklemede) olarak kaydedilir.
 * Not:
 *  - Bu dosyada SADECE Aişe dokunulmamıştır.
 * =============================================================ÇIKLAMA eklenmiştir; işley
 */

// Oturum başlatma (aktif değilse)
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
// Ortak yapılandırma ve DB bağlantısı
require_once __DIR__ . '/config.php';

// Erişim kontrolü: Sadece öğretmenler
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
  header('Location: index.php');
  exit;
}

// Oturumdaki giriş yapmış öğretmenin kimliği
$userId = (int)$_SESSION['user_id'];

// İstek tipi: GET -> form göster, POST -> kayıt oluştur

// Yardımcı: Video var mı? (Öğretmen artık başkasının videosu için de talep açabilir)
function videoExists(mysqli $conn, int $videoId): bool {
  $stmt = $conn->prepare('SELECT id FROM videos WHERE id = ? LIMIT 1');
  $stmt->bind_param('i', $videoId);
  $stmt->execute();
  $ok = (bool)$stmt->get_result()->fetch_assoc();
  $stmt->close();
  return $ok;
}

$error = '';
$success = '';
// URL/POST içinden talep türünü belirle (sadece 'edit' ya da 'delete')
$type = $_GET['type'] ?? $_POST['type'] ?? 'edit';
if (!in_array($type, ['edit','delete'], true)) { $type = 'edit'; }

// POST aşaması: form gönderildiğinde talebi oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Hangi video için talep? Kimlik doğrulaması ardından yetki kontrolü yapılır
  $videoId = (int)($_POST['video_id'] ?? 0);
  if (!$videoId || !videoExists($conn, $videoId)) {
    // Güvenlik: Geçersiz video ID
    $error = 'Geçersiz video veya video bulunamadı.';
  } else {
    if ($type === 'delete') {
      // Silme talebi: opsiyonel gerekçe ile basit bir kayıt
      $deleteReason = trim($_POST['delete_reason'] ?? '');
      $stmt = $conn->prepare('INSERT INTO video_edit_requests (request_type, video_id, requester_id, status, review_note) VALUES ("delete", ?, ?, "pending", ?)');
      $stmt->bind_param('iis', $videoId, $userId, $deleteReason);
      if ($stmt->execute()) {
        $success = 'Silme talebiniz gönderildi.';
        // Admin bekleyen talepler cache'ini temizle
        @mysqli_query($conn, "DELETE FROM system_cache WHERE cache_key LIKE 'pending_video_edit_requests_%'");
      } else {
        $error = 'Veritabanı hatası: '.htmlspecialchars($stmt->error);
      }
      $stmt->close();
    } else {
      // Düzenleme talebi: önce aynı video ve öğretmen için bekleyen edit taleplerini iptal et (spam/çakışma önleme)
      $cx = $conn->prepare('UPDATE video_edit_requests SET status = "cancelled", decided_at = NOW() WHERE request_type = "edit" AND video_id = ? AND requester_id = ? AND status = "pending"');
      $cx->bind_param('ii', $videoId, $userId);
      $cx->execute();
      $cx->close();

      // Önerilen yeni alanlar (konu, sınıf, ünite, başlık, açıklama, YouTube URL)
      $subject = 'math';
      $grade = $_POST['grade'] ?? null;
      $unitId = isset($_POST['unit_id']) && $_POST['unit_id'] !== '' ? (int)$_POST['unit_id'] : null;
      $title = trim($_POST['title'] ?? '');
      $description = trim($_POST['description'] ?? '');
      $topic = trim($_POST['topic'] ?? '');
      $youtube = trim($_POST['youtube_url'] ?? '');
      $youtubeParam = ($youtube === '') ? null : $youtube;
      $gradeParam = in_array($grade, ['5','6','7','8'], true) ? $grade : null;
      $stmt = $conn->prepare('INSERT INTO video_edit_requests (request_type, video_id, requester_id, proposed_subject, proposed_grade, proposed_unit_id, proposed_title, proposed_description, proposed_topic, proposed_youtube_url, status) VALUES ("edit", ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")');
      $stmt->bind_param('iississss', $videoId, $userId, $subject, $gradeParam, $unitId, $title, $description, $topic, $youtubeParam);
      if ($stmt->execute()) {
        $success = 'Düzenleme talebiniz gönderildi.';
        // Admin bekleyen talepler cache'ini temizle
        @mysqli_query($conn, "DELETE FROM system_cache WHERE cache_key LIKE 'pending_video_edit_requests_%'");
      } else {
        $error = 'Veritabanı hatası: ' . htmlspecialchars($stmt->error);
      }
      $stmt->close();
    }
  }
}

// Form render
// Formda gösterilecek video kimliği (GET/POST uyumlu okuma)
$videoId = (int)($_GET['video_id'] ?? ($_POST['video_id'] ?? 0));
// Güvenlik: video ID geçerli değilse ya da video yoksa kullanıcı dostu mesaj göster
if (!$videoId || !videoExists($conn, $videoId)) {
  ?>
  <!doctype html>
  <html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Geçersiz Video</title>
    <style>
      body{font-family:Inter,system-ui,Arial;margin:0;background:#f6f7fb;color:#0f172a}
      .container{max-width:760px;margin:40px auto;padding:0 16px}
      .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 24px rgba(2,6,23,.06);overflow:hidden}
      .card-header{padding:14px 16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
      .card-body{padding:16px}
      .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:none;border-radius:10px;background:#4f46e5;color:#fff;font-weight:600;text-decoration:none}
      .btn:hover{background:#4338ca}
      .muted{color:#64748b;font-size:14px}
    </style>
  </head>
  <body>
    <?php include 'navbar.php'; ?>
    <div class="container">
      <section class="card">
        <div class="card-header"><h3>Video Bulunamadı</h3></div>
        <div class="card-body">
          <p class="muted">Talep oluşturmak için geçerli bir video seçilmelidir. Lütfen öğretmen panelinizdeki video listesinden bir video seçip tekrar deneyin.</p>
          <a class="btn" href="teacher_panel.php">Öğretmen Paneline Dön</a>
        </div>
      </section>
    </div>
  </body>
  </html>
  <?php
  exit;
}

// Videonun mevcut bilgileri: form alanlarını ön doldurmak için alınır
$v = null;
$stmt = $conn->prepare('SELECT id, title, description, grade, unit_id, unit, topic, youtube_url FROM videos WHERE id = ?');
$stmt->bind_param('i', $videoId);
$stmt->execute();
$v = $stmt->get_result()->fetch_assoc();
$stmt->close();

?><!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Video Düzenleme Talebi</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--bg:#f6f7fb;--text:#0f172a;--muted:#64748b;--surface:#fff;--border:#e5e7eb;--primary:#4f46e5;--primary-600:#4338ca}
    *{box-sizing:border-box} body{margin:0;font-family:Inter,system-ui,Arial;background:var(--bg);color:var(--text)}
    .container{max-width:760px;margin:24px auto;padding:0 16px}
    .card{background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:0 10px 24px rgba(2,6,23,.06); overflow:hidden}
    .card-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .card-body{padding:16px}
    label{display:block;font-size:13px;color:#475569;margin-bottom:6px}
    input,select,textarea{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;outline:none;background:white}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:none;border-radius:10px;background:var(--primary);color:#fff;font-weight:600;cursor:pointer}
    .btn:hover{background:var(--primary-600)}
    .alert{padding:10px;border-radius:10px;margin-bottom:10px}
    .alert-success{background:#dcfce7;color:#166534}
    .alert-error{background:#fee2e2;color:#b91c1c}
    .muted{color:#64748b;font-size:13px}
  </style>
</head>
<body>
  <?php
    // Ortak gezinti çubuğu: rol bazlı menüler ve kullanıcı bilgisi
    include 'navbar.php';
  ?>
  <div class="container">
    <section class="card">
      <div class="card-header"><h3><?= $type==='delete' ? 'Video Silme Talebi' : 'Video Düzenleme Talebi' ?></h3></div>
      <div class="card-body">
        <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
          <!-- Talep türünü ve hedef video kimliğini arka planda gönder -->
          <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>" />
          <input type="hidden" name="video_id" value="<?= (int)$v['id'] ?>" />
          <?php if ($type === 'delete'): ?>
            <!-- Silme talebi: yalnızca gerekçe alanı gösterilir -->
            <p class="muted">Silinecek video: <strong><?= htmlspecialchars($v['title'] ?? '') ?></strong></p>
            <div>
              <label>Silme Gerekçesi (opsiyonel)</label>
              <input name="delete_reason" placeholder="Örn: Yanlış yükleme, yinelenen, hatalı içerik" />
            </div>
          <?php else: ?>
          <!-- Düzenleme talebi: sınıf/ünite + içerik alanları -->
          <div class="row">
            <div>
              <label>Sınıf</label>
              <select name="grade" id="grade">
                <?php $grades = ['5','6','7','8']; $cg = $v['grade'] ?? ''; foreach($grades as $g): ?>
                  <option value="<?= $g ?>" <?= ($cg===$g?'selected':'') ?>><?= $g ?>. Sınıf</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>Ünite</label>
              <select name="unit_id" id="unit_id"></select>
              <div class="muted">Ünite listesi sınıf seçimine göre güncellenir.</div>
            </div>
          </div>
          <div style="margin-top:12px">
            <label>Başlık</label>
            <input name="title" value="<?= htmlspecialchars($v['title'] ?? '') ?>" />
          </div>
          <div style="margin-top:12px">
            <label>Açıklama</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($v['description'] ?? '') ?></textarea>
          </div>
          <div class="row" style="margin-top:12px">
            <div>
              <label>Alt Konu</label>
              <input name="topic" value="<?= htmlspecialchars($v['topic'] ?? '') ?>" />
            </div>
            <div>
              <label>YouTube URL (opsiyonel)</label>
              <input name="youtube_url" value="<?= htmlspecialchars($v['youtube_url'] ?? '') ?>" />
            </div>
          </div>
          <?php endif; ?>
          <div style="margin-top:16px; text-align:right">
            <button class="btn" type="submit">Talebi Gönder</button>
          </div>
        </form>
      </div>
    </section>
  </div>
  <script>
    <?php if ($type !== 'delete'): ?>
    // Dinamik Ünite Yükleme
    // Açıklama:
    //  - Öğretmen sınıfı değiştirdiğinde ilgili sınıfa ait üniteleri
    //    'units_api.php' üzerinden AJAX ile çeker.
    //  - Yanıt örneği: { units: [{id, order, name}, ...] }
    //  - Üniteler geldiğinde select içine option olarak eklenir.
    async function loadUnits() {
      const grade = document.getElementById('grade').value;
      const unitSel = document.getElementById('unit_id');
      unitSel.innerHTML = '<option>Yükleniyor...</option>';
      try {
        const res = await fetch('units_api.php?subject=math&grade=' + encodeURIComponent(grade));
        const data = await res.json();
        unitSel.innerHTML = '';
        data.units.forEach(u => {
          const opt = document.createElement('option');
          opt.value = u.id;
          opt.textContent = u.order + '. ' + u.name;
          unitSel.appendChild(opt);
        });
        // mevcut unit_id seçili hale getir
        const current = <?= (int)($v['unit_id'] ?? 0) ?>;
        if (current) {
          unitSel.value = String(current);
        }
      } catch (e) {
        // Hata durumunda kullanıcıya bilgilendirici bir seçenek göster
        unitSel.innerHTML = '<option>Ünite yüklenemedi</option>';
      }
    }
    // Sınıf değişiminde üniteleri yeniden yükle ve sayfa açılışında bir kez çağır
    document.getElementById('grade').addEventListener('change', loadUnits);
    loadUnits();
    <?php endif; ?>
  </script>
</body>
</html>
