<?php
/**
 * =====================================================
 * NAVBAR BİLEŞENİ (navbar.php)
 * Uygulamanın üst gezinme çubuğunu (navigation bar) oluşturur.
 * Oturumu güvenli şekilde başlatır ve kullanıcının rolüne göre menü öğelerini gösterir.
 *
 * Not: Bu dosyada sadece açıklamalar eklendi; davranış değiştirilmedi.
 * =====================================================
 */
// Start session safely (avoid warning if headers already sent)
define('IN_NAVBAR', true);
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Only access session variables if session was started successfully
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role   = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$email  = isset($_SESSION['email']) ? $_SESSION['email'] : null;

// Okunmamış mesaj sayısını al
$unreadCount = 0;
if ($userId) {
    $unreadStmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0 AND is_deleted = 0");
    $unreadStmt->bind_param('i', $userId);
    $unreadStmt->execute();
    $unreadResult = $unreadStmt->get_result()->fetch_assoc();
    $unreadCount = (int)$unreadResult['count'];
    $unreadStmt->close();
}

// Kullanıcının avatarını dosya uzantılarına bakarak bulur (varsa)
function nav_get_avatar_src($userId) {
  $base = __DIR__ . '/uploads/avatars/';
  foreach (['jpg','jpeg','png','webp'] as $ext) {
    $path = $base . $userId . '.' . $ext;
    if (file_exists($path)) {
      return 'uploads/avatars/' . $userId . '.' . $ext;
    }
  }
  return null;
}
$avatarSrc = $userId ? nav_get_avatar_src((int)$userId) : null;
?>
<style>
    .navbar {
        position: sticky;
        top: 0;
        z-index: var(--z-header, 1000);
        background: var(--surface, rgba(255,255,255,0.95));
        backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.2));
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: background-color var(--dur, 250ms) ease, box-shadow var(--dur, 250ms) ease;
        will-change: background-color, box-shadow;
    }
  
  .navbar:hover {
    background: var(--surface, rgba(255,255,255,0.98));
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
  }
  
  .navbar-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'Poppins', system-ui, Arial;
  }
  
  .navbar-brand {
    display: flex;
    gap: 12px;
    align-items: center;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary, #667eea), var(--secondary, #764ba2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-decoration: none;
    font-size: 1.5rem;
    transition: all var(--dur, 250ms) ease;
    will-change: transform;
  }
  
  .navbar-brand:hover {
    transform: translateY(-1px);
  }
  
  .navbar-brand i {
    font-size: 1.8rem;
  }
  
  .navbar-nav {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    height: 40px;
  }
  
  .nav-link {
    color: var(--text, #374151);
    text-decoration: none;
    font-weight: 500;
    padding: 10px 16px;
    border-radius: 12px;
    transition: background-color var(--dur, 250ms) ease, color var(--dur, 250ms) ease, transform var(--dur, 250ms) ease;
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    height: 40px; /* desktop height */
    min-height: 44px; /* tap target baseline */
    box-sizing: border-box;
    will-change: background-color, color, transform;
  }
  
  .nav-link:hover {
    background: color-mix(in oklab, var(--primary, #667eea) 12%, transparent);
    color: var(--primary, #667eea);
    transform: translateY(-1px);
  }
  
  .nav-link i {
    font-size: 0.9rem;
    position: relative;
  }
  
  .nav-badge {
    position: absolute;
    top: -8px;
    right: -12px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    animation: pulse 1.5s infinite;
  }
  
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
  }
  
  .nav-button {
    background: linear-gradient(135deg, var(--primary, #667eea), #764ba2);
    color: var(--primary-contrast, #fff);
    border-radius: 12px;
    padding: 10px 16px;
    text-decoration: none;
    font-weight: 500;
    transition: background var(--dur, 250ms) ease, transform var(--dur, 250ms) ease, box-shadow var(--dur, 250ms) ease;
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    height: 40px;
    box-sizing: border-box;
    will-change: background, transform, box-shadow;
  }

  /* Logout variants */
  .nav-logout-compact {
    display: none; /* hidden on desktop by default */
    padding: 8px 12px;
  }
  
  .nav-button:hover {
    background: linear-gradient(135deg, #5b21b6, #6b21a8);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
  }
  
  .nav-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e5e7eb;
    transition: border-color 0.2s ease, transform 0.2s ease;
    cursor: pointer;
    will-change: border-color, transform;
  }
  
  .nav-avatar:hover {
    border-color: #667eea;
    transform: scale(1.1);
  }
  
  .nav-separator {
    width: 1px;
    height: 32px;
    background: linear-gradient(to bottom, transparent, #e5e7eb, transparent);
    margin: 0 8px;
  }

  /* Hamburger button (hidden on desktop) */
  .nav-hamburger {
    display: none;
    inline-size: 44px;
    block-size: 44px;
    border: none;
    border-radius: 10px;
    background: transparent;
    color: #374151;
    cursor: pointer;
    align-items: center;
    justify-content: center;
  }
  .nav-hamburger:hover { background: rgba(0,0,0,0.04); }
  
  .user-menu {
    position: relative;
    display: inline-block;
  }

  /* Top-down mobile menu (top sheet) */
  .nav-top-sheet {
    position: fixed;
    inset-inline: 0;
    inset-block-start: 0;
    background: #ffffff;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    border-bottom: 1px solid #e5e7eb;
    transform: translateY(-100%);
    transition: transform 250ms ease;
    z-index: 1100; /* above navbar */
  }
  .nav-top-sheet.is-open { transform: translateY(0); }
  .nav-top-sheet .sheet-inner {
    padding: 12px 16px 16px;
    display: grid;
    gap: 8px;
  }
  .nav-top-sheet a.sheet-link, .nav-top-sheet button.sheet-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    color: #111827;
    text-decoration: none;
    border: none;
    background: transparent;
    text-align: left;
    cursor: pointer;
  }
  .nav-top-sheet a.sheet-link:hover,
  .nav-top-sheet button.sheet-link:hover { background: #f3f4f6; }

  .nav-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity 250ms ease;
    z-index: 1099;
  }
  .nav-overlay.is-show { opacity: 1; pointer-events: auto; }
  
  .user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--panel-bg, #ffffff);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    padding: 16px;
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
    border: 1px solid var(--border, #e5e7eb);
    margin-top: 8px;
    will-change: opacity, visibility, transform;
  }
  
  .user-menu:hover .user-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }
  
  .user-info {
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
    margin-bottom: 12px;
  }
  
  .user-name {
    font-weight: 600;
    color: var(--text, #1f2937);
    margin-bottom: 4px;
  }
  
  .user-role {
    font-size: 0.875rem;
    color: #6b7280;
  }
  
  .dropdown-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    color: var(--text, #374151);
    text-decoration: none;
    border-radius: 8px;
    transition: background-color 0.2s ease, color 0.2s ease;
    will-change: background-color, color;
  }
  
  .dropdown-link:hover {
    background: color-mix(in oklab, var(--primary, #667eea) 10%, transparent);
    color: var(--primary, #667eea);
  }
  
  .dropdown-link i {
    width: 16px;
    text-align: center;
  }
  
  @media (max-width: 768px) {
    .navbar-container {
      padding: 12px 16px;
    }
    .nav-hamburger { display: inline-flex; }
    /* Hide inline navbar links; use top sheet instead */
    .navbar-nav { display: none; }
    
    .navbar-nav .nav-link {
      padding: 8px 12px;
      font-size: 14px;
    }
    
    /* Hide long texts on small screens, keep icons visible */
    .navbar-nav .nav-link .hide-sm { display: none; }
    
    .navbar-brand {
      font-size: 1.25rem;
    }
    
    .navbar-brand i {
      font-size: 1.5rem;
    }
    
    .nav-button {
      padding: 8px 16px;
      font-size: 13px;
    }
  }
  
  @media (max-width: 640px) {
    /* handled by 768px rule; additionally tweak logout button variants */
    /* Show compact logout, hide full text logout on very small screens */
    .nav-button.nav-logout-full { display: none; }
    .nav-button.nav-logout-compact { display: flex; }
  }

  /* Respect device safe areas on iOS notch devices */
  .navbar-container { padding-left: calc(16px + env(safe-area-inset-left)); padding-right: calc(16px + env(safe-area-inset-right)); }
  .nav-top-sheet .sheet-inner { padding-left: calc(12px + env(safe-area-inset-left)); padding-right: calc(12px + env(safe-area-inset-right)); }
</style>

<nav class="navbar">
  <div class="navbar-container">
    <a href="main_menu.php" class="navbar-brand">
      <i class="fas fa-graduation-cap"></i>
      EğitimPlus
    </a>
    <?php if($userId): ?>
    <button
      class="nav-hamburger"
      aria-label="Menüyü aç/kapat"
      aria-controls="navTopSheet"
      aria-expanded="false"
      data-navsheet-toggle
      title="Menü">
      <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" fill="none"/>
      </svg>
    </button>
    <?php endif; ?>
    
    <div class="navbar-nav">
      <a class="nav-link" href="main_menu.php">
        <i class="fas fa-home"></i>
        <span class="hide-sm">Ana Sayfa</span>
      </a>
      
      <?php if($userId): ?>
        <?php // Kullanıcı oturumu açık: rolüne göre menü bağlantılarını göster ?>
        <?php if($role === 'admin'): ?>
          <a class="nav-link" href="admin.php">
            <i class="fas fa-shield-alt"></i>
            <span class="hide-sm">Admin Panel</span>
          </a>
        <?php elseif($role === 'teacher'): ?>
          <a class="nav-link" href="teacher_panel.php">
            <i class="fas fa-chalkboard-teacher"></i>
            <span class="hide-sm">Öğretmen Paneli</span>
          </a>
        <?php elseif($role === 'student'): ?>
          <a class="nav-link" href="student_panel.php">
            <i class="fas fa-user-graduate"></i>
            <span class="hide-sm">Öğrenci Paneli</span>
          </a>
        <?php endif; ?>
        
        <a class="nav-link" href="inbox.php" style="position: relative;">
          <i class="fas fa-envelope"></i>
          <span class="hide-sm">Mesajlar</span>
          <?php if ($unreadCount > 0): ?>
            <span class="nav-badge"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a>
        
        <span class="nav-separator"></span>
        
        <div class="user-menu">
          <?php // Kullanıcı menüsü: Ayarlar ve çıkış seçenekleri ?>
          <a class="nav-link" href="settings.php">
            <i class="fas fa-cog"></i>
            <span class="hide-sm">Ayarlar</span>
          </a>
          
          <?php if($avatarSrc): ?>
            <img class="nav-avatar" src="<?= htmlspecialchars($avatarSrc) ?>" alt="Avatar">
          <?php endif; ?>
          
          <div class="user-dropdown">
            <div class="user-info">
              <div class="user-name"><?= htmlspecialchars($email ?? 'Kullanıcı') ?></div>
              <div class="user-role">
                <?php if($role === 'admin'): ?>
                  <i class="fas fa-user-shield"></i> Admin
                <?php elseif($role === 'teacher'): ?>
                  <i class="fas fa-chalkboard-teacher"></i> Öğretmen
                <?php elseif($role === 'student'): ?>
                  <i class="fas fa-user-graduate"></i> Öğrenci
                <?php endif; ?>
              </div>
            </div>
            
            <a href="settings.php" class="dropdown-link">
              <i class="fas fa-cog"></i>
              Ayarlar
            </a>
            
            <a href="logout.php" class="dropdown-link">
              <i class="fas fa-sign-out-alt"></i>
              Çıkış Yap
            </a>
          </div>
        </div>
        
        

        <a class="nav-button nav-logout-full" href="logout.php">
          <i class="fas fa-sign-out-alt"></i>
          Çıkış
        </a>
        <a class="nav-button nav-logout-compact" href="logout.php" aria-label="Çıkış">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      <?php else: ?>
        <?php // Misafir kullanıcı: Giriş ve Kayıt Ol bağlantıları ?>
        <a class="nav-link" href="index.php">
          <i class="fas fa-sign-in-alt"></i>
          <span class="hide-sm">Giriş</span>
        </a>
        
        <a class="nav-button" href="register.php">
          <i class="fas fa-user-plus"></i>
          Kayıt Ol
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Mobile top-down menu (top sheet) -->
<div class="nav-overlay" id="navOverlay" hidden></div>
<div class="nav-top-sheet" id="navTopSheet" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="sheet-inner">
    <a class="sheet-link" href="main_menu.php">
      <i class="fas fa-home"></i>
      Ana Sayfa
    </a>

    

    <?php if($userId): ?>
      <?php if($role === 'admin'): ?>
        <a class="sheet-link" href="admin.php">
          <i class="fas fa-shield-alt"></i>
          Admin Panel
        </a>
      <?php elseif($role === 'teacher'): ?>
        <a class="sheet-link" href="teacher_panel.php">
          <i class="fas fa-chalkboard-teacher"></i>
          Öğretmen Paneli
        </a>
      <?php elseif($role === 'student'): ?>
        <a class="sheet-link" href="student_panel.php">
          <i class="fas fa-user-graduate"></i>
          Öğrenci Paneli
        </a>
      <?php endif; ?>

      <a class="sheet-link" href="inbox.php" style="position: relative;">
        <i class="fas fa-envelope"></i>
        Mesajlar
        <?php if ($unreadCount > 0): ?>
          <span class="nav-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
      </a>

      <a class="sheet-link" href="settings.php">
        <i class="fas fa-cog"></i>
        Ayarlar
      </a>
      <a class="sheet-link" href="logout.php">
        <i class="fas fa-sign-out-alt"></i>
        Çıkış Yap
      </a>
    <?php else: ?>
      <a class="sheet-link" href="index.php">
        <i class="fas fa-sign-in-alt"></i>
        Giriş
      </a>
      <a class="sheet-link" href="register.php">
        <i class="fas fa-user-plus"></i>
        Kayıt Ol
      </a>
    <?php endif; ?>
  </div>
  
</div>

<script>
(function(){
  const btn = document.querySelector('[data-navsheet-toggle]');
  const sheet = document.getElementById('navTopSheet');
  const overlay = document.getElementById('navOverlay');
  if (!btn || !sheet || !overlay) return;
  let lastFocused = null;
  function openSheet(){
    lastFocused = document.activeElement;
    sheet.classList.add('is-open');
    sheet.setAttribute('aria-hidden','false');
    overlay.classList.add('is-show');
    overlay.hidden = false;
    btn.setAttribute('aria-expanded','true');
    document.addEventListener('keydown', onEsc);
  }
  function closeSheet(){
    sheet.classList.remove('is-open');
    sheet.setAttribute('aria-hidden','true');
    overlay.classList.remove('is-show');
    overlay.hidden = true;
    btn.setAttribute('aria-expanded','false');
    document.removeEventListener('keydown', onEsc);
    if (lastFocused) lastFocused.focus();
  }
  function onEsc(e){ if (e.key === 'Escape') closeSheet(); }
  btn.addEventListener('click', function(){
    const opened = sheet.classList.contains('is-open');
    opened ? closeSheet() : openSheet();
  });
  overlay.addEventListener('click', closeSheet);
})();
</script>

