<?php
/**
 * =======================================================
 * Video İzleme Sayfası (video_view.php)
 * -------------------------------------------------------
 * Amaç:
 *  - Öğrenci/öğretmen/admin tarafından tek bir videonun
 *    sade ve güvenli bir player arayüzünde izlenmesini sağlamak.
 * Güvenlik:
 *  - Sadece giriş yapmış kullanıcılar erişebilir.
 * Veri Erişimi:
 *  - 'videos' tablosundan video başlık/açıklama ve kaynak bilgileri çekilir.
 * Player Mantığı:
 *  - Tüm oynatma YouTube üzerinden yapılır; gizliliğe duyarlı 'youtube-nocookie' embed kullanılır.
 * Notlar:
 *  - Sayfanın altındaki küçük JS ile notlar localStorage üzerinde tutulur
 *    (hızlı ve mahremiyeti artıran bir yaklaşım). Sunucuya yazma yoktur.
 * Not:
 *  - Bu dosyada SADECE AÇIKLAMA eklenmiştir; davranış değişmemiştir.
 * =======================================================
 */

// Oturum başlatma ve yapılandırma
session_start();
require_once __DIR__ . '/config.php';

// Erişim kontrolü: giriş yapılmadıysa login sayfasına gönder
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Geçerli video kimliğini al (zorunlu) – id veya video_id kabul edilir
$videoId = (int)($_GET['id'] ?? ($_GET['video_id'] ?? 0));
if ($videoId <= 0) {
    http_response_code(400);
    echo 'Geçersiz video';
    exit;
}

// Video bilgisini veritabanından çek
$video = null;
$stmt = $conn->prepare("SELECT id, title, description, file_path, youtube_url, status, uploaded_by FROM videos WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $res = $stmt->get_result();
    $video = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}
if (!$video) {
    http_response_code(404);
    echo 'Video bulunamadı';
    exit;
}

// Eğer kullanıcı öğretmense: bu videoya dair kendi düzenleme/silme taleplerini listele
$teacherRequests = [];
if (($_SESSION['role'] ?? '') === 'teacher') {
    $reqStmt = $conn->prepare("SELECT id, request_type, status, review_note, created_at, decided_at FROM video_edit_requests WHERE video_id = ? AND requester_id = ? ORDER BY created_at DESC LIMIT 20");
    if ($reqStmt) {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        $reqStmt->bind_param('ii', $videoId, $uid);
        $reqStmt->execute();
        $reqRes = $reqStmt->get_result();
        while ($row = $reqRes->fetch_assoc()) { $teacherRequests[] = $row; }
        $reqStmt->close();
    }
}

// YouTube kaynağı varsa: embed URL'ini normalleştir ve gizliliği artır
$embedUrl = '';
if (!empty($video['youtube_url'])) {
    $u = $video['youtube_url'];
    $id = null;
    // Çeşitli YouTube URL formatlarından video ID'si yakalanır
    if (preg_match('~[?&]v=([a-zA-Z0-9_-]{6,})~', $u, $m)) $id = $m[1];
    if (!$id && preg_match('~youtu\.be/([a-zA-Z0-9_-]{6,})~', $u, $m)) $id = $m[1];
    if (!$id && preg_match('~\/shorts\/([a-zA-Z0-9_-]{6,})~', $u, $m)) $id = $m[1];
    if (!$id && preg_match('~\/embed\/([a-zA-Z0-9_-]{6,})~', $u, $m)) $id = $m[1];
    if (!$id && preg_match('~youtube\.com/(?:v|embed)/([a-zA-Z0-9_-]{6,})~', $u, $m)) $id = $m[1];
    if ($id) {
        // Gizlilik için youtube-nocookie alan adı kullanılır
        $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0&modestbranding=1&iv_load_policy=3&fs=1&controls=1&enablejsapi=1';
    } else {
        // Fallback: URL zaten embed formatındaysa domaini nocookie'ye çevir
        $embedUrl = preg_replace('~^https://www\.youtube\.com/embed/~', 'https://www.youtube-nocookie.com/embed/', $u);
        if (strpos($embedUrl, '?') === false) {
            $embedUrl .= '?rel=0&modestbranding=1&iv_load_policy=3&fs=1&controls=1&enablejsapi=1';
        }
    }
    // enablejsapi parametresi yoksa ekle (YouTube IFrame API için gerekli)
    if (strpos($embedUrl, 'enablejsapi=1') === false) {
        $embedUrl .= (strpos($embedUrl, '?') === false ? '?' : '&') . 'enablejsapi=1';
    }
}

$title = htmlspecialchars($video['title'] ?? ('Video #' . $videoId), ENT_QUOTES, 'UTF-8');
$desc = htmlspecialchars($video['description'] ?? '', ENT_QUOTES, 'UTF-8');
// Artık yerel oynatma yok. Tüm oynatma YouTube embed üzerinden yapılır.
// Kullanıcı rolünü oku (öğrenci/öğretmen/admin/guest). Rol bazlı quiz alanı için kullanılacak
$role = $_SESSION['role'] ?? 'guest';
// Uploader öğretmen ID'si (öğrenci için mesaj başlatma)
$uploaderTeacherId = isset($video['uploaded_by']) ? (int)$video['uploaded_by'] : null;

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Video</title>
    <link rel="manifest" href="/login-system/manifest.json">
    <script>
      // Force dark theme ASAP
      (function(){
        try { localStorage.setItem('theme','dark'); } catch(e) {}
        var root = document.documentElement;
        root.setAttribute('data-theme','dark');
      })();
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/main-styles.css">
    <style>
      /* Responsive, theme-aware video view styles */
      body { margin: 0; }
      .video-container {
        max-width: var(--container-lg, 1100px);
        margin-inline: auto;
        padding: var(--space-6, 1.5rem) var(--space-4, 1rem);
      }
      .video-layout {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: var(--space-6, 1.5rem);
      }
      @media (max-width: 1024px) {
        .video-layout { grid-template-columns: 1fr; }
      }
      .panel {
        background: var(--panel-bg, #ffffff);
        color: var(--text, #111827);
        border: 1px solid var(--border, rgba(0,0,0,.08));
        border-radius: var(--radius-lg, 1rem);
        box-shadow: var(--shadow, 0 8px 24px rgba(0,0,0,.08));
        padding: var(--space-5, 1.25rem);
      }
      .player-title { margin: 0 0 var(--space-3, .75rem); font-size: 1.25rem; font-weight: 700; }
      .frame { position: relative; width: 100%; padding-top: 56.25%; border-radius: var(--radius-lg, 1rem); overflow: hidden; background: #000; }
      .frame iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
      .meta { margin-top: var(--space-3, .75rem); color: var(--text-muted, #6b7280); font-size: .95rem; line-height: 1.6; }
      .topbar { display:flex; justify-content:space-between; align-items:center; gap: var(--space-3, .75rem); margin-bottom: var(--space-3, .75rem); }
      .badge { display:inline-block; background: color-mix(in oklab, var(--primary, #667eea) 12%, transparent); color: var(--primary, #667eea); padding: 4px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600; }
      .card { background: var(--surface, #fff); border: 1px solid var(--border, rgba(0,0,0,.08)); border-radius: var(--radius-lg, 1rem); padding: var(--space-4, 1rem); }
      .card h2 { margin: 0 0 var(--space-3, .75rem); font-size: 1rem; font-weight: 700; color: var(--text, #111827); }
      .toolbar { display:flex; gap: var(--space-2, .5rem); align-items:center; margin-bottom: var(--space-2, .5rem); }
      textarea#notes { width: 100%; min-height: 160px; resize: vertical; padding: .75rem; border-radius: .75rem; border: 1px solid var(--border, rgba(0,0,0,.08)); background: var(--bg, #f9fafb); color: var(--text, #111827); font: inherit; }
      .actions { display:flex; gap: var(--space-2, .5rem); flex-wrap: wrap; }
      .side-grid { display: grid; gap: var(--space-4, 1rem); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="video-container">
      <div class="video-layout">
        <section class="panel">
          <div class="topbar">
            <div class="actions">
              <?php
                $backUrl = 'main_menu.php';
                if ($role === 'teacher') { $backUrl = 'teacher_panel.php'; }
                elseif ($role === 'admin') { $backUrl = 'admin.php'; }
                elseif ($role === 'student') { $backUrl = 'student_panel.php'; }
              ?>
              <a class="btn btn-outline" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">← Geri Dön</a>
              <span class="badge">Rol: <?= htmlspecialchars($role) ?></span>
            </div>
            <div class="actions">
              <?php if ($role === 'teacher'): ?>
                <a class="btn btn-secondary" href="video_edit_request.php?video_id=<?= $videoId ?>&type=edit">Düzenleme Talebi</a>
                <a class="btn btn-secondary" href="video_edit_request.php?video_id=<?= $videoId ?>&type=delete">Silme Talebi</a>
              <?php elseif ($role === 'student'): ?>
                <a class="btn btn-primary" href="javascript:void(0)" onclick="alert('Bu özellik geliştirilme aşamasındadır.')">Bu Video İçin Quiz</a>
                <?php if (!empty($uploaderTeacherId)): ?>
                  <button
                    class="btn btn-outline"
                    id="messageTeacherBtn"
                    data-teacher-id="<?= (int)$uploaderTeacherId ?>"
                    data-video-id="<?= (int)$videoId ?>"
                    data-video-title="<?= htmlspecialchars($video['title'] ?? ('Video #' . $videoId), ENT_QUOTES, 'UTF-8') ?>"
                    data-video-url="<?= htmlspecialchars(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http').
                      '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI'] ?? '/login-system/') . '/video_view.php?id=' . $videoId), ENT_QUOTES, 'UTF-8') ?>"
                  >Öğretmene Mesaj</button>
                <?php endif; ?>
              <?php elseif ($role === 'admin'): ?>
                <a class="btn btn-secondary" href="admin.php">Yönetim Paneli</a>
              <?php endif; ?>
            </div>
          </div>
          <h1 class="player-title"><?= $title ?></h1>
          <div class="frame">
            <?php if ($embedUrl): ?>
              <iframe id="yt-player" src="<?= htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                      allowfullscreen referrerpolicy="no-referrer" title="<?= $title ?>">
              </iframe>
            <?php else: ?>
              <div class="card" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;text-align:center;">
                Bu video için YouTube bağlantısı bulunamadı. Lütfen öğretmeninizden YouTube linki eklemek için düzenleme talebi oluşturmasını isteyin.
              </div>
            <?php endif; ?>
          </div>
          <?php if ($desc): ?><div class="meta"><?= $desc ?></div><?php endif; ?>
        </section>
        <?php $uiJsPath = 'assets/js/ui.js'; $uiJsVer = file_exists($uiJsPath) ? filemtime($uiJsPath) : time(); ?>
        <script src="assets/js/ui.js?v=<?= $uiJsVer ?>"></script>
        <aside class="side-grid">
          <div class="card">
            <h2>Notlarım</h2>
            <div class="toolbar">
              <button class="btn btn-primary" id="saveNotes"><i class="fas fa-save"></i> Kaydet</button>
              <button class="btn btn-outline" id="clearNotes"><i class="fas fa-trash"></i> Temizle</button>
            </div>
            <textarea id="notes" placeholder="Bu video ile ilgili notlarınızı buraya yazın..."></textarea>
            <div id="saveStatus" class="meta"></div>
          </div>
          <?php if ($role === 'teacher'): ?>
          <div class="card">
            <h2>Düzenleme Taleplerim</h2>
            <?php if (empty($teacherRequests)): ?>
              <div class="meta">Bu video için henüz bir talebiniz yok.</div>
            <?php else: ?>
              <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.5rem;">
                <?php foreach ($teacherRequests as $r): ?>
                  <li class="card">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;">
                      <strong style="text-transform:capitalize;">Talep #<?= (int)$r['id'] ?> • <?= htmlspecialchars($r['request_type'] ?? '') ?></strong>
                      <span class="badge"><?= htmlspecialchars($r['status'] ?? '') ?></span>
                    </div>
                    <div class="meta" style="margin-top:.25rem;">
                      Oluşturulma: <?= htmlspecialchars($r['created_at'] ?? '') ?>
                      <?php if (!empty($r['decided_at'])): ?> • Sonuç: <?= htmlspecialchars($r['decided_at']) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($r['review_note'])): ?>
                      <div style="font-size:.9rem;margin-top:.5rem;">Admin Notu: <?= nl2br(htmlspecialchars($r['review_note'])) ?></div>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
              <div class="toolbar" style="margin-top:.5rem;">
                <a class="btn btn-secondary" href="video_edit_request.php?video_id=<?= $videoId ?>&type=edit">Yeni Düzenleme Talebi</a>
                <a class="btn btn-secondary" href="video_edit_request.php?video_id=<?= $videoId ?>&type=delete">Silme Talebi</a>
              </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="card">
            <h2>Test / Alıştırmalar</h2>
            <div id="quizArea" class="meta">Yakında: öğretmenin atadığı testler burada görünecek.</div>
          </div>
          <div class="card">
            <h2>Kaynaklar</h2>
            <ul style="margin:0;padding-left:1.25rem;color:inherit;">
              <li>Ders dokümanları (yakında)</li>
              <li>İndirme bağlantıları (izinli ise)</li>
            </ul>
          </div>
        </aside>
      </div>
    </div>

<script>
(function(){
  // Notlar özelliği: kullanıcının tarayıcısında localStorage ile tutulur.
  // Amaç: Hızlı ve sunucuyu yormayan basit bir kişisel not defteri.
  const videoId = <?= json_encode($videoId) ?>;
  const userId = <?= json_encode((int)($_SESSION['user_id'] ?? 0)) ?>;
  const role = <?= json_encode($role) ?>; // 'student' | 'teacher' | 'admin' | 'guest'
  const key = `video_notes_${userId}_${videoId}`; // Kullanıcı + video bazlı benzersiz anahtar
  const notes = document.getElementById('notes');
  const saveBtn = document.getElementById('saveNotes');
  const clearBtn = document.getElementById('clearNotes');
  const status = document.getElementById('saveStatus');

  // Sayfa açılışında var olan notu yükle (varsa)
  try {
    const existing = localStorage.getItem(key);
    if (existing) notes.value = existing;
  } catch(_){ /* storage erişilemezse sessizce geç */ }

  // Küçük durum mesajı helper'ı
  function mark(msg){ status.textContent = msg; setTimeout(()=>{status.textContent='';}, 2000); }

  // Kaydet: localStorage'a yaz
  saveBtn.addEventListener('click', ()=>{
    try { localStorage.setItem(key, notes.value || ''); mark('Kaydedildi'); } catch(e){ mark('Kaydedilemedi'); }
  });
  // Temizle: alanı ve storage'ı sıfırla
  clearBtn.addEventListener('click', ()=>{
    if (!confirm('Notları temizlemek istiyor musunuz?')) return;
    notes.value='';
    try { localStorage.removeItem(key); mark('Temizlendi'); } catch(e){ mark('Temizlenemedi'); }
  });

  // ==============================
  // İlerleme Takibi (SUNUCUYA POST)
  // ==============================
  let lastSentAt = 0;          // Son gönderim zaman damgası (ms)
  let lastSentPercent = 0;     // Son gönderilen yüzde
  const SEND_INTERVAL_MS = 15000; // minimum gönderim aralığı
  const MIN_PERCENT_STEP = 10; // yüzde ilerleme eşiği

  async function sendProgress(watchSeconds, totalSeconds){
    try {
      const form = new FormData();
      form.append('action', 'update_progress');
      form.append('video_id', String(videoId));
      form.append('watch_duration', String(Math.max(0, Math.floor(watchSeconds))));
      form.append('total_duration', String(Math.max(0, Math.floor(totalSeconds || 0))));
      await fetch('math.php', { method: 'POST', body: form, credentials: 'same-origin' });
    } catch(e) {
      // sessiz geç – kritik değil
    }
  }

  function shouldSend(nowMs, percent){
    if ((nowMs - lastSentAt) >= SEND_INTERVAL_MS) return true;
    if ((percent - lastSentPercent) >= MIN_PERCENT_STEP) return true;
    return false;
  }

  function markSent(nowMs, percent){
    lastSentAt = nowMs;
    lastSentPercent = percent;
  }

  // Yerel HTML5 video oynatma kaldırıldı (YouTube-only)

  // YouTube IFrame API ile takip
  const ytFrame = document.getElementById('yt-player');
  if (ytFrame) {
    // IFrame API yükle
    const tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(tag);

    let ytPlayer = null;
    window.onYouTubeIframeAPIReady = function(){
      ytPlayer = new YT.Player('yt-player', {
        events: {
          onReady: () => {
            // periyodik kontrol kur
            const tick = () => {
              if (!ytPlayer || typeof ytPlayer.getCurrentTime !== 'function') return;
              const now = Date.now();
              const current = ytPlayer.getCurrentTime() || 0;
              const total = ytPlayer.getDuration() || 0;
              const state = ytPlayer.getPlayerState();
              const percent = total > 0 ? Math.min(100, (current/total)*100) : 0;
              if (state === YT.PlayerState.PLAYING || state === YT.PlayerState.PAUSED) {
                if (shouldSend(now, percent)) { markSent(now, percent); sendProgress(current, total); }
              }
            };
            // 5 sn'de bir kontrol
            setInterval(tick, 5000);
          },
          onStateChange: (e) => {
            if (e.data === YT.PlayerState.ENDED) {
              const total = ytPlayer.getDuration() || 0;
              markSent(Date.now(), 100);
              sendProgress(total, total);
            }
          }
        }
      });
    };
  }
  
  // ==============================
  // Öğretmene Mesaj (video bağlamı linki ile)
  // ==============================
  (function(){
    const btn = document.getElementById('messageTeacherBtn');
    if (!btn) return;
    btn.addEventListener('click', async () => {
      const teacherId = Number(btn.getAttribute('data-teacher-id'));
      const vid = Number(btn.getAttribute('data-video-id'));
      const vtitle = btn.getAttribute('data-video-title') || `Video #${vid}`;
      const vurl = btn.getAttribute('data-video-url') || (window.location.origin + window.location.pathname + `?id=${vid}`);
      const text = `Merhaba hocam, bu video hakkında bir mesajım var: ${vtitle} \n${vurl}`;
      try {
        // 1) Ensure/reuse conversation
        const ensure = await fetch('chat.php?action=ensure_conversation', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ type: 'general', participants: [teacherId] })
        });
        const ensureJson = await ensure.json();
        if (!ensureJson || !ensureJson.success) {
          const err = (ensureJson && (ensureJson.error || ensureJson.message)) || 'Konuşma oluşturulamadı';
          alert('Mesaj başlatılamadı: ' + err);
          return;
        }
        const convId = (ensureJson.data && ensureJson.data.conversation_id) || ensureJson.conversation_id;
        if (!convId) {
          alert('Mesaj başlatılamadı: Geçersiz konuşma kimliği');
          return;
        }
        // 2) Send initial message
        const send = await fetch('chat.php?action=send_message', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ conversation_id: Number(convId), message: text })
        });
        const sendJson = await send.json();
        if (!sendJson || !sendJson.success) {
          const err2 = (sendJson && (sendJson.error || sendJson.message)) || 'Mesaj gönderilemedi';
          alert('Mesaj gönderilemedi: ' + err2);
          return;
        }
        window.location.href = `messages.php?conversation_id=${encodeURIComponent(convId)}`;
      } catch (e) {
        alert('Mesaj gönderilirken bir hata oluştu.');
      }
    });
  })();
})();
</script>
</body>
</html>
