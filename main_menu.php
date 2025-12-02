<?php
/**
 * Ana Menü (main_menu.php)
 *
 * Amaç:
 * - Misafir veya giriş yapmış kullanıcıya göre ana sayfa içeriklerini göstermek
 * - Hero, dersler, canlı ders tanıtımı ve iletişim bölümü
 *
 * Not: Yalnızca açıklamalar eklendi, davranış değiştirilmedi.
 */

// Oturumu başlat (girişli kullanıcıyı tespit etmek için gerekli)
session_start();
include 'config.php';

// Admin ID al (ör. yöneticiye ait bazı bağlantılar/iletişim bilgileri için kullanılabilir)
$adminId = null;
$adm = $conn->prepare("SELECT id FROM users WHERE role='admin' AND status='approved' ORDER BY id ASC LIMIT 1");
if ($adm) {
  $adm->execute();
  $adm->bind_result($adminId);
  $adm->fetch();
  $adm->close();
}

// Giriş yapmış kullanıcı için karşılama bilgileri (ad ve rol)
$greetName = null;
$greetRole = null;
if (isset($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  $gs = $conn->prepare("SELECT first_name, role FROM users WHERE id = ? LIMIT 1");
  if ($gs) {
    $gs->bind_param('i', $uid);
    $gs->execute();
    $gs->bind_result($fn, $rl);
    if ($gs->fetch()) { $greetName = $fn; $greetRole = $rl; }
    $gs->close();
  }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Menü - Eğitim Platformu</title>
    <script>
      // Initialize theme ASAP: force light and persist, so unified pastel shows
      (function(){
        try { localStorage.setItem('theme','light'); } catch(e) {}
        document.documentElement.setAttribute('data-theme','light');
      })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/main-styles.css">
    <style>
      .btn-primary{
            background: var(#a78bfa);
            color: white;
            box-shadow: var(-0 10px 15px -3px rgba(0, 0, 0, 0.04));
            position: relative;
            overflow: hidden;
            border: 3px solid;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            justify-content: center;
            align-items: center;
            display: flex;
            padding: 10px 20px;
            transition: all 0.3s ease;
      }
      .btn-primary:hover {
         background: var(--accent-dark);
         transform: translateY(-2px);
         box-shadow: var(--shadow-lg);
      }
      .btn-secondary {
            background: var(#a78bfa);
            color: white;
            box-shadow: var(-0 10px 15px -3px rgba(0, 0, 0, 0.04));
            position: relative;
            overflow: hidden;
            border: 3px solid;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            justify-content: center;
            align-items: center;
            display: flex;
            padding: 10px 20px;
            transition: all 0.3s ease;
      }
      .btn-secondary:hover {
      background: var(#5aa6ff);
      transform: translateY(-2px);
      box-shadow: var(0 20px 25px -5px rgba(0, 0, 0, 0.06));
      }
    </style>
</head>
<body>
  <?php // Üst navigasyon menüsü
  include 'navbar.php'; ?>

  <!-- Hero Section: başlık, açıklama ve hızlı başlangıç/giriş butonları -->
  <section class="hero">
    <div class="container">
      <div class="hero-content">
        <h1 class="hero-title">Eğitimde Yeni Bir Dönem Başlıyor</h1>
        <p class="hero-description">5. sınıftan 8. sınıfa kadar tüm dersler için kaliteli video içerikleri ve canlı ders imkanı. Türkçe, Matematik, Fen Bilimleri ve Sosyal Bilgiler derslerinde uzman öğretmenlerimizle öğrenmeyi kolaylaştırın.</p>

        <?php if (!isset($_SESSION['user_id'])): ?>
          <div class="hero-buttons">
            <a href="register.php" class="btn btn-primary">
              <i class="fas fa-rocket"></i> Hemen Başla
            </a>
            <a href="index.php" class="btn btn-outline">
              <i class="fas fa-sign-in-alt"></i> Giriş Yap
            </a>
          </div>
        <?php else: ?>
          <div class="welcome-message">
            <div class="welcome-name">
              Hoş geldin, <?= htmlspecialchars($greetName ?? ($_SESSION['email'] ?? '')) ?>! 👋
            </div>
            <div class="welcome-role">
              <?php if ($greetRole === 'student'): ?>
                <i class="fas fa-graduation-cap"></i> Öğrenci - İyi dersler dileriz
              <?php elseif ($greetRole === 'teacher'): ?>
                <i class="fas fa-chalkboard-teacher"></i> Öğretmen - İyi çalışmalar
              <?php elseif ($greetRole === 'admin'): ?>
                <i class="fas fa-user-shield"></i> Yönetici - Hoş geldiniz
              <?php else: ?>
                <i class="fas fa-user"></i> Keyifli kullanımlar
              <?php endif; ?>
            </div>
            
            <!-- Rol bazlı hızlı erişim: Öğrenci/Öğretmen/Admin kısa yolları -->
            <div class="quick-access" style="margin-top: 20px;">
              <?php if ($greetRole === 'student'): ?>
                <a href="#subjects-section" class="btn btn-secondary">
                  <i class="fas fa-book"></i> Derslerime Git
                </a>
              <?php elseif ($greetRole === 'teacher'): ?>
                <a href="teacher_panel.php" class="btn btn-secondary">
                  <i class="fas fa-chalkboard-teacher"></i> Öğretmen Paneli
                </a>
              <?php elseif ($greetRole === 'admin'): ?>
                <a href="admin.php" class="btn btn-secondary">
                  <i class="fas fa-shield-alt"></i> Admin Paneli
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Ders Kategorileri: aktif/pasif ders kartları -->
  <section class="subjects-section" id="subjects-section">
    <div class="container">
      <div class="section-title">
        <h2>Dersler</h2>
        <p>Her ders için özel olarak hazırlanmış içerikler</p>
      </div>
      
      <div class="subjects-grid">
        <!-- Türkçe -->
        <div class="subject-card coming-soon" data-subject="turkish">
          <div class="subject-badge coming-soon">Yakında</div>
          <div class="subject-icon">
            <i class="fas fa-book-open"></i>
          </div>
          <h3 class="subject-title">Türkçe</h3>
          <p class="subject-description">Dil bilgisi, okuma anlama ve yazım kurallarını öğrenin. Hikaye analizi ve yaratıcı yazma teknikleri.</p>
          <div class="subject-action">
            <i class="fas fa-clock"></i>
            Yakında Geliyor
          </div>
        </div>
        
        <!-- Matematik -->
        <div class="subject-card active" data-subject="math">
          <div class="subject-badge">Aktif</div>
          <div class="subject-icon">
            <i class="fas fa-calculator"></i>
          </div>
          <h3 class="subject-title">Matematik</h3>
          <p class="subject-description">Sayılar, geometri ve problem çözme teknikleri. 5, 6, 7 ve 8. sınıf müfredatı.</p>
          <div class="subject-action">
            <i class="fas fa-play-circle"></i>
            Derslere Başla
          </div>
        </div>
        
        <!-- Fen -->
        <div class="subject-card coming-soon" data-subject="science">
          <div class="subject-badge coming-soon">Yakında</div>
          <div class="subject-icon">
            <i class="fas fa-flask"></i>
          </div>
          <h3 class="subject-title">Fen Bilimleri</h3>
          <p class="subject-description">Canlılar, enerji, dünya ve evren bilimi. Deney videoları ve interaktif içerikler.</p>
          <div class="subject-action">
            <i class="fas fa-clock"></i>
            Yakında Geliyor
          </div>
        </div>
        
        <!-- Sosyal -->
        <div class="subject-card coming-soon" data-subject="social">
          <div class="subject-badge coming-soon">Yakında</div>
          <div class="subject-icon">
            <i class="fas fa-globe"></i>
          </div>
          <h3 class="subject-title">Sosyal Bilgiler</h3>
          <p class="subject-description">Tarih, coğrafya ve vatandaşlık bilgileri. Haritalar ve tarihsel dönem analizleri.</p>
          <div class="subject-action">
            <i class="fas fa-clock"></i>
            Yakında Geliyor
          </div>
        </div>
        
        <!-- İngilizce -->
        <div class="subject-card coming-soon" data-subject="english">
          <div class="subject-badge coming-soon">Yakında</div>
          <div class="subject-icon">
            <i class="fas fa-language"></i>
          </div>
          <h3 class="subject-title">İngilizce</h3>
          <p class="subject-description">Konuşma, dinleme ve yazma becerileri. İnteraktif diyaloglar ve kelime oyunları.</p>
          <div class="subject-action">
            <i class="fas fa-clock"></i>
            Yakında Geliyor
          </div>
        </div>
        
        <!-- Resim -->
        <div class="subject-card coming-soon" data-subject="art">
          <div class="subject-badge coming-soon">Yakında</div>
          <div class="subject-icon">
            <i class="fas fa-palette"></i>
          </div>
          <h3 class="subject-title">Resim</h3>
          <p class="subject-description">Sanatsal beceriler ve yaratıcılık. Çizim teknikleri ve renk teorisi.</p>
          <div class="subject-action">
            <i class="fas fa-clock"></i>
            Yakında Geliyor
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Canlı Dersler: birebir/yüz yüze ders tanıtımı -->
  <section class="live-lessons" id="live-section">
    <div class="container">
      <div class="live-content">
        <!-- Görsel -->
        <div class="live-image">
          <img src="photos/özelders.jpg" alt="Canlı Dersler">
        </div>

        <!-- İçerik -->
        <div class="live-text">
          <h2>Canlı Dersler</h2>
          <p>Birebir Öğrenme Deneyimi</p>
          <ul class="live-features">
            <li>Uzman öğretmenler eşliğinde birebir eğitim</li>
            <li>Esnek ders saatleriyle kendi programını belirle</li>
            <li>Ekran paylaşımı ve anlık soru-cevap desteği</li>
          </ul>
          <div class="live-buttons">
            <a href="live_sessions.php" class="btn-primary"><b>Canlı Ders</b></a>
            <a href="#iletisim" class="btn-secondary"><b>Yüz Yüze Ders Rezervasyonu</b></a>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <p>© 2025 EğitimPlus. Tüm hakları saklıdır.</p>
    </div>
  </footer>

  <script>
    // Yardımcı JS fonksiyonları: Basit modal aç/kapa ve yönlendirme
    function showNotReady(subject) {
      const modal = document.createElement('div');
      modal.className = 'math-modal show';
      modal.innerHTML = `
        <div class="modal-content">
          <h3 class="modal-title">${subject} dersi hazır değil</h3>
          <p style="color: #6b7280; margin-bottom: 24px;">Bu ders henüz yayında değil. Lütfen daha sonra tekrar kontrol edin.</p>
          <button onclick="this.parentElement.parentElement.remove()" class="grade-btn">Tamam</button>
        </div>
      `;
      document.body.appendChild(modal);
    }

    function openMathModal() {
      document.getElementById('mathModal').classList.add('show');
    }

    function goToMath(grade) {
      window.location.href = 'math.php?grade=' + grade;
    }

    function closeMathModal() {
      document.getElementById('mathModal').classList.remove('show');
    }

    // Modal dışına tıklandığında kapat
    document.getElementById('mathModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeMathModal();
      }
    });

    // İletişim formu: Mesajlaşma sistemi kaldırıldı, kullanıcıya bilgilendirme göster.
    (function(){
      const form = document.getElementById('contactForm');
      if (!form) return; // guest view
      const feedback = document.getElementById('contactFeedback');
      form.addEventListener('submit', function(e){
        e.preventDefault();
        const textarea = document.getElementById('contactMessage');
        const message = (textarea.value || '').trim();
        if (!message) {
          feedback.style.display = 'block';
          feedback.style.color = '#ef4444';
          feedback.textContent = 'Lütfen bir mesaj yazın.';
          return;
        }
        // Mesajlaşma özelliği devre dışı bildirimi
        feedback.style.display = 'block';
        feedback.style.color = '#ef4444';
        feedback.textContent = 'Mesajlaşma/iletişim özelliği şu anda devre dışıdır.';
      });
    })();
  </script>
  <script src="assets/js/ui.js"></script>
  <script src="assets/js/main-interactions.js"></script>
</body>
</html>
