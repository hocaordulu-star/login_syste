<?php
session_start();
include 'config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Canlı Dersler - Eğitim Platformu</title>
  <script>
    (function(){
      try { localStorage.setItem('theme','light'); } catch(e) {}
      document.documentElement.setAttribute('data-theme','light');
    })();
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets\css\main-styles.css">
  <style>
    .math-modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      backdrop-filter: blur(10px);
    }
    .math-modal.show {
      display: flex;
    }
    .modal-content {
    /* Modal kutusu: yüzey rengi, geniş yuvarlatma, iç boşluk ve gölge. */
    background: var(--surface);
    border-radius: var(--radius-2xl);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-xl);
    max-width: 500px;
    width: 90%;
    text-align: center;
    animation: modalSlideIn 0.3s ease;
    position: relative;
    }
    .live-list {
      display: flex;
      justify-content: space-evenly;
      flex-wrap: wrap;
      align-items: center;
      gap: 20px;
      padding-top: 30px;
      padding-bottom: 280px;
    }
    .live-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      background-color: hsla(0, 18%, 93%, 0.92);
      padding: 20px;
      border-radius: 10px;
      width: 300px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .live-item:hover {
      transform: translateY(-5px);
    }
    .live-item-action {
      border-radius: 10px;
      overflow: hidden;
    }
    .btn-primary{
      text-align: center;
      width: 100px;
    }
    
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <section class="live-lessons live-lessons-page">
    <div class="container">
      <div class="section-title">
        <h2>Canlı Ders Listesi</h2>
        <p>Canlı yayına katılmak istediğiniz dersi seçin.</p>
      </div>

      <div class="live-list">
        <div class="live-item">
          <div class="live-item-info">
            <h3>Matematik</h3>
          </div>
          <div class="live-item-action">
            <button type="button" class="btn-primary join-live-btn" data-subject="Matematik">
              Katıl
            </button>
          </div>
        </div>

        <div class="live-item">
          <div class="live-item-info">
            <h3>Sosyal Bilgiler</h3>
          </div>
          <div class="live-item-action">
            <button type="button" class="btn-primary join-live-btn" data-subject="Sosyal Bilgiler">
               Katıl
            </button>
          </div>
        </div>

        <div class="live-item">
          <div class="live-item-info">
            <h3>Türkçe</h3>
          </div>
          <div class="live-item-action">
            <button type="button" class="btn-primary join-live-btn" data-subject="Türkçe">
               Katıl
            </button>
          </div>
        </div>

        <div class="live-item">
          <div class="live-item-info">
            <h3>Fen Bilimleri</h3>
          </div>
          <div class="live-item-action">
            <button type="button" class="btn-primary join-live-btn" data-subject="Fen Bilimleri">
               Katıl
            </button>
          </div>
        </div>

        <div class="live-item">
          <div class="live-item-info">
            <h3>İngilizce</h3>
          </div>
          <div class="live-item-action">
            <button type="button" class="btn-primary join-live-btn" data-subject="İngilizce">
               Katıl
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div id="liveModal" class="math-modal">
    <div class="modal-content">
      <h3 class="modal-title" id="liveModalTitle">Canlı Ders</h3>
      <div class="live-video-wrapper">
        <iframe
          id="liveVideo"
          src=""
          title="YouTube canlı ders"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowfullscreen
        ></iframe>
      </div>
      <button type="button" class="grade-btn" onclick="closeLiveModal()">Kapat</button>
    </div>
  </div>

  <footer class="footer">
    <div class="container">
      <p>© 2025 EğitimPlus. Tüm hakları saklıdır.</p>
    </div>
  </footer>

  <script>
    (function(){
      var modal = document.getElementById('liveModal');
      if (!modal) return;

      var YOUTUBE_LIVE_URL = 'https://www.youtube.com/embed/VIDEO_ID_DEGISTIRIN';
      var iframe = document.getElementById('liveVideo');
      var titleEl = document.getElementById('liveModalTitle');
      var buttons = document.querySelectorAll('.join-live-btn');

      function openLiveModal(subject) {
        if (!iframe) return;
        if (titleEl) {
          titleEl.textContent = subject + ' Canlı Dersi';
        }
        iframe.src = YOUTUBE_LIVE_URL + '?autoplay=1';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
      }

      function closeLiveModal() {
        
        if (iframe) {
          iframe.src = '';
        }
        modal.classList.remove('show');
        document.body.style.overflow = '';
      }

      window.closeLiveModal = closeLiveModal;

      buttons.forEach(function(btn){
        btn.addEventListener('click', function(){
          var subject = this.getAttribute('data-subject') || 'Canlı Ders';
          openLiveModal(subject);
        });
      });

      modal.addEventListener('click', function(e){
        if (e.target === modal) {
          closeLiveModal();
        }
      });
    })();
  </script>
  <script src="assets/js/ui.js"></script>
  <script src="assets/js/main-interactions.js"></script>
</body>
</html>
