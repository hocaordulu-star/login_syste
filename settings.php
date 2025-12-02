<?php
/**
 * =====================================================
 * AYARLAR SAYFASI (settings.php)
 * Kullanıcının profil bilgilerini, şifresini ve avatarını güncellemesini sağlar.
 * Bölümler: Kimlik doğrulama, kullanıcı/veri yükleme, POST işlemleri (profil/şifre/avatar), arayüz.
 * Not: Bu düzenlemede yalnızca açıklamalar eklendi; uygulama davranışı değiştirilmedi.
 * =====================================================
 */

session_start();
require_once 'config.php';

// 1) Kimlik Doğrulama Kontrolü
// Oturumda user_id yoksa giriş sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];

// 2) Kullanıcı Bilgisi Yükleme
// Profil kartlarında göstermek ve validasyon için temel kullanıcı alanlarını alıyoruz.
$stmt = $conn->prepare('SELECT id, first_name, last_name, email, phone, password, role, status FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  header('Location: index.php');
  exit;
}

$role = $user['role'];
// 3) Rol Bazlı Profil Alanları
// Öğrenci için: school, grade
// Öğretmen için: school, department, experience_years
$profile = [ 'school' => null, 'grade' => null, 'department' => null, 'experience_years' => null ];

if ($role === 'student') {
  // Öğrenci ise student profil tablosundan ek alanları çek
  $ps = $conn->prepare('SELECT school, grade FROM students WHERE user_id = ?');
  $ps->bind_param('i', $userId);
  $ps->execute();
  $res = $ps->get_result()->fetch_assoc();
  if ($res) { $profile = array_merge($profile, $res); }
  $ps->close();
} elseif ($role === 'teacher') {
  // Öğretmen ise teacher profil tablosundan ek alanları çek
  $pt = $conn->prepare('SELECT school, department, experience_years FROM teachers WHERE user_id = ?');
  $pt->bind_param('i', $userId);
  $pt->execute();
  $res = $pt->get_result()->fetch_assoc();
  if ($res) { $profile = array_merge($profile, $res); }
  $pt->close();
}

$success = '';
$error = '';

// 4) Yardımcı Fonksiyon: Avatar dosya yolu bulucu
// Kullanıcının avatarının (varsa) hangi uzantıda kayıtlı olduğunu tespit eder.
// Varsa ilgili dosya yolunu döndürür, yoksa null döner.
function getAvatarPath(int $userId): ?string {
  $candidates = ["uploads/avatars/{$userId}.jpg","uploads/avatars/{$userId}.jpeg","uploads/avatars/{$userId}.png","uploads/avatars/{$userId}.webp"];
  foreach ($candidates as $path) {
    if (file_exists($path)) return $path;
  }
  return null;
}

// 5) POST İsteklerinin İşlenmesi
// form=profile  -> profil temel bilgileri ve rol bazlı alanlar
// form=password -> şifre güncelleme
// form=avatar   -> profil fotoğrafı yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Profil güncelleme
  if (isset($_POST['form']) && $_POST['form'] === 'profile') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($first === '' || $email === '') {
      $error = 'Ad ve e‑posta zorunludur.';
    } else {
      // E-posta benzersizlik kontrolü (mevcut kullanıcı haricinde)
      $chk = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
      $chk->bind_param('si', $email, $userId);
      $chk->execute();
      $exists = $chk->get_result()->fetch_assoc();
      $chk->close();
      if ($exists) {
        $error = 'Bu e‑posta başka bir hesap tarafından kullanılıyor.';
      } else {
        // Temel kullanıcı bilgilerini güncelle
        $up = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?');
        $up->bind_param('ssssi', $first, $last, $email, $phone, $userId);
        $up->execute();
        $up->close();
        $_SESSION['email'] = $email;

        if ($role === 'student') {
          $school = trim($_POST['school'] ?? '');
          $grade  = trim($_POST['grade'] ?? '');
          // Öğrenci profilinde mevcut kayıt var mı?
          $has = $conn->prepare('SELECT user_id FROM students WHERE user_id = ?');
          $has->bind_param('i', $userId);
          $has->execute();
          $row = $has->get_result()->fetch_assoc();
          $has->close();
          if ($row) {
            // Güncelleme (boş stringleri NULL yap)
            $us = $conn->prepare('UPDATE students SET school = ?, grade = ? WHERE user_id = ?');
            $schoolParam = ($school === '') ? null : $school;
            $gradeParam = ($grade === '') ? null : $grade;
            $us->bind_param('ssi', $schoolParam, $gradeParam, $userId);
            $us->execute();
            $us->close();
          } else {
            // Ekleme (boş stringleri NULL yap)
            $is = $conn->prepare('INSERT INTO students (user_id, school, grade) VALUES (?, ?, ?)');
            $schoolParam = ($school === '') ? null : $school;
            $gradeParam = ($grade === '') ? null : $grade;
            $is->bind_param('iss', $userId, $schoolParam, $gradeParam);
            $is->execute();
            $is->close();
          }
        } elseif ($role === 'teacher') {
          $school = trim($_POST['school'] ?? '');
          $department = trim($_POST['department'] ?? '');
          $experience_years = (int)($_POST['experience_years'] ?? 0);
          $has = $conn->prepare('SELECT user_id FROM teachers WHERE user_id = ?');
          $has->bind_param('i', $userId);
          $has->execute();
          $row = $has->get_result()->fetch_assoc();
          $has->close();
          if ($row) {
            // Güncelleme (boş stringleri NULL yap)
            $ut = $conn->prepare('UPDATE teachers SET school = ?, department = ?, experience_years = ? WHERE user_id = ?');
            $schoolParam = ($school === '') ? null : $school;
            $deptParam = ($department === '') ? null : $department;
            $ut->bind_param('ssii', $schoolParam, $deptParam, $experience_years, $userId);
            $ut->execute();
            $ut->close();
          } else {
            // Ekleme (boş stringleri NULL yap)
            $it = $conn->prepare('INSERT INTO teachers (user_id, school, department, experience_years) VALUES (?, ?, ?, ?)');
            $schoolParam = ($school === '') ? null : $school;
            $deptParam = ($department === '') ? null : $department;
            $it->bind_param('issi', $userId, $schoolParam, $deptParam, $experience_years);
            $it->execute();
            $it->close();
          }
        }

        $success = 'Profil bilgileriniz güncellendi.';
        // Ekrana yansıtmak için local değişkenleri güncelle
        $user['first_name'] = $first;
        $user['last_name'] = $last;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $profile['school'] = $school ?? $profile['school'];
        if ($role === 'student') { $profile['grade'] = $grade ?? $profile['grade']; }
        if ($role === 'teacher') { $profile['department'] = $department ?? $profile['department']; $profile['experience_years'] = $experience_years; }
      }
    }
  }

  // Şifre değiştirme
  // Geçerli şifre doğrulanır, yeni şifre min. 6 karakter olmalıdır.
  if (isset($_POST['form']) && $_POST['form'] === 'password') {
    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    if ($current === '' || $new === '' || $confirm === '') {
      $error = 'Tüm şifre alanlarını doldurun.';
    } elseif ($new !== $confirm) {
      $error = 'Yeni şifre ve doğrulama eşleşmiyor.';
    } elseif (strlen($new) < 6) {
      $error = 'Yeni şifre en az 6 karakter olmalı.';
    } else {
      if (!password_verify($current, $user['password'])) {
        $error = 'Mevcut şifre yanlış.';
      } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upw = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $upw->bind_param('si', $hash, $userId);
        $upw->execute();
        $upw->close();
        $success = 'Şifreniz güncellendi.';
        $user['password'] = $hash;
      }
    }
  }

  // Avatar yükleme
  // Dosya tip/boyut doğrulaması yapılır. Eski avatarlar temizlenir, yeni dosya taşınır.
  if (isset($_POST['form']) && $_POST['form'] === 'avatar' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
      $allowed = ['jpg','jpeg','png','webp'];
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed, true)) {
        $error = 'Desteklenmeyen dosya türü. (jpg, jpeg, png, webp)';
      } elseif ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Dosya boyutu 2MB sınırını aşıyor.';
      } else {
        if (!is_dir('uploads/avatars')) {
          @mkdir('uploads/avatars', 0777, true);
        }
        // Eski dosyaları temizle
        foreach (['jpg','jpeg','png','webp'] as $e) {
          $old = "uploads/avatars/{$userId}.{$e}";
          if (file_exists($old)) @unlink($old);
        }
        $dest = "uploads/avatars/{$userId}.{$ext}";
        if (move_uploaded_file($file['tmp_name'], $dest)) {
          $success = 'Avatar güncellendi.';
        } else {
          $error = 'Dosya yüklenemedi.';
        }
      }
    } else {
      $error = 'Yükleme sırasında bir hata oluştu.';
    }
  }
}

$avatarPath = getAvatarPath($userId);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ayarlar - Eğitim Sistemi</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    :root {
      --primary: #667eea;
      --primary-dark: #5a6fd8;
      --secondary: #764ba2;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --info: #3b82f6;
      --light: #f8fafc;
      --dark: #1e293b;
      --muted: #64748b;
      --border: #e2e8f0;
      --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      --gradient: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      --glass: rgba(255, 255, 255, 0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg,rgb(161, 134, 196) 0%,rgb(255, 255, 255) 100%);
      min-height: 100vh;
      color: var(--dark);
      line-height: 1.6;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 2rem;
    }

    .page-header {
      background: var(--glass);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: var(--shadow);
      border: 1px solid rgba(255, 255, 255, 0.2);
      text-align: center;
    }

    .page-title {
      font-size: 2.5rem;
      font-weight: 700;
      background: var(--gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
    }

    .page-subtitle {
      color: var(--muted);
      font-size: 1.1rem;
      font-weight: 400;
    }

    .settings-card {
      background: var(--glass);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      margin-bottom: 2rem;
      box-shadow: var(--shadow);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      will-change: transform, box-shadow;
    }

    .settings-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
    }

    .card-header {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      padding: 1.5rem 2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .card-header h3 {
      margin: 0;
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--dark);
    }

    .card-body {
      padding: 2rem;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-label {
      display: block;
      font-size: 0.9rem;
      color: var(--dark);
      margin-bottom: 0.5rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-input {
      width: 100%;
      padding: 0.875rem 1.25rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      outline: none;
      font-size: 0.95rem;
      background: rgba(255, 255, 255, 0.1);
      color: var(--dark);
      backdrop-filter: blur(10px);
      transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
      will-change: border-color, background-color, box-shadow;
      font-family: 'Poppins', sans-serif;
    }

    .form-input::placeholder {
      color: var(--muted);
    }

    .form-input:focus {
      border-color: var(--primary);
      background: rgba(255, 255, 255, 0.2);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-select {
      width: 100%;
      padding: 0.875rem 1.25rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      outline: none;
      font-size: 0.95rem;
      background: rgba(255, 255, 255, 0.1);
      color: var(--dark);
      backdrop-filter: blur(10px);
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
      will-change: border-color, box-shadow;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
    }

    .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-file {
      width: 100%;
      padding: 0.875rem 1.25rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      outline: none;
      font-size: 0.95rem;
      background: rgba(255, 255, 255, 0.1);
      color: var(--dark);
      backdrop-filter: blur(10px);
      transition: border-color 0.2s ease, background-color 0.2s ease;
      will-change: border-color, background-color;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
    }

    .form-file::-webkit-file-upload-button {
      background: var(--gradient);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      margin-right: 1rem;
      cursor: pointer;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.875rem 1.5rem;
      border: none;
      border-radius: 12px;
      background: var(--gradient);
      color: white;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      will-change: transform, box-shadow;
      font-size: 0.95rem;
      font-family: 'Poppins', sans-serif;
      box-shadow: var(--shadow);
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.2);
      color: var(--dark);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .alert {
      padding: 1rem 1.5rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 500;
      box-shadow: var(--shadow);
      animation: slideIn 0.5s ease;
    }

    .alert-success {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
    }

    .alert-error {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .avatar-section {
      display: flex;
      align-items: center;
      gap: 2rem;
      flex-wrap: wrap;
    }

    .avatar-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
    }

    .avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid rgba(255, 255, 255, 0.3);
      box-shadow: var(--shadow);
      transition: transform 0.2s ease, border-color 0.2s ease;
      will-change: transform, border-color;
    }

    .avatar:hover {
      transform: scale(1.05);
      border-color: var(--primary);
    }

    .avatar-fallback {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--gradient);
      color: white;
      font-weight: 700;
      font-size: 2rem;
      border: 4px solid rgba(255, 255, 255, 0.3);
      box-shadow: var(--shadow);
      transition: transform 0.2s ease, border-color 0.2s ease;
      will-change: transform, border-color;
    }

    .avatar-fallback:hover {
      transform: scale(1.05);
      border-color: var(--primary);
    }

    .avatar-upload {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      flex: 1;
      min-width: 250px;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .role-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-left: auto;
    }

    .role-student {
      background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
      color: white;
    }

    .role-teacher {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
      color: white;
    }

    .role-admin {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      color: white;
    }

    @media (max-width: 768px) {
      .container {
        padding: 1rem;
      }
      
      .form-grid {
        grid-template-columns: 1fr;
      }
      
      .avatar-section {
        flex-direction: column;
        text-align: center;
      }
      
      .avatar-upload {
        min-width: auto;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <!-- Ortak gezinme çubuğu (navbar): rol bazlı bağlantılar ve kullanıcı menüsü içerir -->
  <?php include 'navbar.php'; ?>
  
  <div class="container">
    <div class="page-header">
      <h1 class="page-title">
        <i class="fas fa-cog"></i>
        Ayarlar
      </h1>
      <p class="page-subtitle">Profil bilgilerinizi ve hesap ayarlarınızı yönetin</p>
    </div>

    <?php if($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>
    
    <?php if($error): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Avatar Bölümü: Kullanıcı profil fotoğrafını görüntüleme ve güncelleme -->
    <section class="settings-card">
      <div class="card-header">
        <i class="fas fa-user-circle"></i>
        <h3>Profil Fotoğrafı</h3>
        <span class="role-badge <?= 'role-' . $role ?>">
          <i class="fas fa-<?= $role === 'student' ? 'graduation-cap' : ($role === 'teacher' ? 'chalkboard-teacher' : 'crown') ?>"></i>
          <?= ucfirst($role === 'student' ? 'Öğrenci' : ($role === 'teacher' ? 'Öğretmen' : 'Admin')) ?>
        </span>
      </div>
      <div class="card-body">
        <div class="avatar-section">
          <div class="avatar-container">
            <?php if($avatarPath): ?>
              <img class="avatar" src="<?= htmlspecialchars($avatarPath) ?>?v=<?= time() ?>" alt="Avatar" />
            <?php else: ?>
              <div class="avatar-fallback"><?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?></div>
            <?php endif; ?>
            <span style="color: var(--muted); font-size: 0.9rem;">Mevcut Avatar</span>
          </div>
          
          <div class="avatar-upload">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="form" value="avatar" />
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-upload"></i>
                  Yeni Avatar Seçin
                </label>
                <input 
                  type="file" 
                  name="avatar" 
                  accept="image/png,image/jpeg,image/webp" 
                  required 
                  class="form-file"
                />
                <small style="color: var(--muted); margin-top: 0.5rem; display: block;">
                  Desteklenen formatlar: JPG, PNG, WebP (Maksimum 2MB)
                </small>
              </div>
              <div class="form-actions">
                <button class="btn" type="submit">
                  <i class="fas fa-cloud-upload-alt"></i>
                  Avatar Yükle
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- Profil Bilgileri Bölümü: İsim, e‑posta, telefon ve rol bazlı ek alanlar -->
    <section class="settings-card">
      <div class="card-header">
        <i class="fas fa-user-edit"></i>
        <h3>Profil Bilgileri</h3>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="form" value="profile" />
          
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-user"></i>
                Ad
              </label>
              <input 
                name="first_name" 
                value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" 
                required 
                class="form-input"
                placeholder="Adınızı girin"
              />
            </div>
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-user"></i>
                Soyad
              </label>
              <input 
                name="last_name" 
                value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" 
                class="form-input"
                placeholder="Soyadınızı girin"
              />
            </div>
          </div>
          
          <div class="form-group full-width" style="margin-top: 1.5rem;">
            <label class="form-label">
              <i class="fas fa-envelope"></i>
              E‑posta
            </label>
            <input 
              name="email" 
              type="email" 
              value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
              required 
              class="form-input"
              placeholder="E-posta adresinizi girin"
            />
          </div>

          <div class="form-group full-width" style="margin-top: 0.5rem;">
            <label class="form-label">
              <i class="fas fa-phone"></i>
              Telefon
            </label>
            <input 
              name="phone"
              type="tel"
              pattern="^[0-9+\-()\s]{7,20}$"
              value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
              class="form-input"
              placeholder="Telefon numaranızı girin"
            />
          </div>

          <?php if($role === 'student'): ?>
          <div class="form-grid" style="margin-top: 1.5rem;">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-school"></i>
                Okul
              </label>
              <input 
                name="school" 
                value="<?= htmlspecialchars($profile['school'] ?? '') ?>" 
                class="form-input"
                placeholder="Okul adınızı girin"
              />
            </div>
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-graduation-cap"></i>
                Sınıf
              </label>
              <select name="grade" class="form-select">
                <?php $grades = ['','4','5','6','7','8']; $current = $profile['grade'] ?? ''; foreach ($grades as $g): ?>
                  <option value="<?= $g ?>" <?= ($current === $g ? 'selected' : '') ?>>
                    <?= $g === '' ? 'Sınıf seçiniz' : $g . '. Sınıf' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <?php elseif($role === 'teacher'): ?>
          <div class="form-grid" style="margin-top: 1.5rem;">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-school"></i>
                Okul
              </label>
              <input 
                name="school" 
                value="<?= htmlspecialchars($profile['school'] ?? '') ?>" 
                class="form-input"
                placeholder="Okul adınızı girin"
              />
            </div>
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-chalkboard"></i>
                Bölüm
              </label>
              <input 
                name="department" 
                value="<?= htmlspecialchars($profile['department'] ?? '') ?>" 
                class="form-input"
                placeholder="Bölümünüzü girin"
              />
            </div>
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-clock"></i>
                Deneyim (yıl)
              </label>
              <input 
                type="number" 
                min="0" 
                name="experience_years" 
                value="<?= htmlspecialchars((string)($profile['experience_years'] ?? 0)) ?>" 
                class="form-input"
                placeholder="Deneyim yılınızı girin"
              />
            </div>
          </div>
          <?php elseif($role === 'admin'): ?>
          <div class="form-grid" style="margin-top: 1.5rem;">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-shield-alt"></i>
                Admin Seviyesi
              </label>
              <select name="admin_level" class="form-select" disabled>
                <option value="super">Süper Admin</option>
              </select>
              <small style="color: var(--muted); margin-top: 0.5rem; display: block;">
                Admin seviyesi sistem yöneticisi tarafından belirlenir
              </small>
            </div>
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-key"></i>
                2FA Durumu
              </label>
              <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2);">
                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                <span style="color: var(--success); font-weight: 500;">Yakında Aktif Olacak</span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-clock"></i>
                Son Admin İşlemi
              </label>
              <input 
                type="text" 
                value="<?= date('d.m.Y H:i') ?>" 
                class="form-input"
                disabled
                style="background: var(--light); color: var(--muted);"
              />
            </div>
          </div>
          <?php endif; ?>

          <div class="form-actions">
            <button class="btn" type="submit">
              <i class="fas fa-save"></i>
              Profili Güncelle
            </button>
            <?php if($role === 'admin'): ?>
              <a href="admin.php" class="btn" style="background: linear-gradient(135deg, var(--secondary), var(--primary)); text-decoration: none;">
                <i class="fas fa-shield-alt"></i>
                Admin Paneline Git
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </section>

    <!-- Şifre Değiştir Bölümü: Mevcut şifre doğrulaması + yeni şifre belirleme -->
    <section class="settings-card">
      <div class="card-header">
        <i class="fas fa-lock"></i>
        <h3>Şifre Değiştir</h3>
      </div>
      <div class="card-body">
        <form method="POST" autocomplete="on">
          <input type="hidden" name="form" value="password" />
          <!-- Password managers and a11y: include an associated username field -->
          <div style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;">
            <label for="pw-username">Kullanıcı adı (e‑posta)</label>
            <input 
              type="email" 
              id="pw-username" 
              name="username" 
              value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
              autocomplete="username"
              tabindex="-1"
            />
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-key"></i>
                Mevcut Şifre
              </label>
              <input 
                type="password" 
                name="current_password" 
                required 
                class="form-input"
                autocomplete="current-password"
                placeholder="Mevcut şifrenizi girin"
              />
            </div>
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-lock"></i>
                Yeni Şifre
              </label>
              <input 
                type="password" 
                name="new_password" 
                required 
                class="form-input"
                autocomplete="new-password"
                placeholder="Yeni şifrenizi girin"
              />
            </div>
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-check-circle"></i>
                Yeni Şifre (Tekrar)
              </label>
              <input 
                type="password" 
                name="confirm_password" 
                required 
                class="form-input"
                autocomplete="new-password"
                placeholder="Yeni şifrenizi tekrar girin"
              />
            </div>
          </div>
          
          <div class="form-actions">
            <button class="btn" type="submit">
              <i class="fas fa-key"></i>
              Şifreyi Güncelle
            </button>
          </div>
        </form>
      </div>
    </section>
  </div>
</body>
</html>


