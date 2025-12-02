<?php
/**
 * =====================================================
 * KAYIT SAYFASI (register.php)
 * Bu sayfa yeni kullanıcı kaydı alır. POST ile gelen form verilerini doğrular,
 * rol/sınıf beyaz listelerini uygular, şifreyi güvenli şekilde hash'ler ve
 * ilgili tablolara (users + role'e göre students/teachers) kayıt ekler.
 * Başlangıç seviyesi için açıklamalar eklendi; davranış DEĞİŞTİRİLMEDİ.
 * =====================================================
 */
// Start output buffering to prevent headers already sent errors
if (ob_get_level() === 0) {
    ob_start();
}

include 'config.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $school = trim($_POST['school'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $department = trim($_POST['department'] ?? '');
    $experience_years = (int)($_POST['experience_years'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');

    // Rol beyaz listesi
    $allowed_roles = ['student','teacher'];
    if (!in_array($role, $allowed_roles, true)) {
        $role = 'student';
    }

    // Grade doğrulama (opsiyonel) – yalnızca izin verilen sınıf değerlerine izin verilir
    $allowed_grades = ['4','5','6','7','8',''];
    if (!in_array($grade, $allowed_grades, true)) {
        $grade = '';
    }

    // Öğretmen için telefon zorunlu
    if ($role === 'teacher' && $phone === '') {
        $message = "<div class='error'>Öğretmen kaydı için telefon zorunludur.</div>";
    }

    if ($first_name !== '' && $email !== '' && $password !== '' && !($role === 'teacher' && $phone === '')) {
        // Şifre HASH'LENİR: PASSWORD_DEFAULT güncel ve güvenli algoritmayı kullanır (örn. bcrypt/argon)
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Temel kullanıcı kaydı (status=pending: admin onayı beklenir)
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $hashed, $role, $phone);

        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;

            // Rol'e özel ek kayıtlar (öğrenci/öğretmen tabloları)
            if ($role === 'student') {
                $s = $conn->prepare("INSERT INTO students (user_id, school, grade) VALUES (?, ?, ?)");
                $gradeParam = ($grade === '') ? null : $grade;
                $schoolParam = ($school === '') ? null : $school;
                $s->bind_param("iss", $new_user_id, $schoolParam, $gradeParam);
                $s->execute();
                $s->close();
            } elseif ($role === 'teacher') {
                $t = $conn->prepare("INSERT INTO teachers (user_id, school, department, experience_years) VALUES (?, ?, ?, ?)");
                $schoolParam = ($school === '') ? null : $school;
                $deptParam = ($department === '') ? null : $department;
                $t->bind_param("issi", $new_user_id, $schoolParam, $deptParam, $experience_years);
                $t->execute();
                $t->close();
            }

            // Admin bildirim sistemi için kayıt
            // Not: try/catch ile log başarısız olsa bile kayıt akışı bozulmaz
            try {
                require_once 'classes/AdminManager.php';
                $adminManager = new AdminManager($conn, 1); // System user
                $adminManager->logAuditAction('user_registration', [
                    'new_user_id' => $new_user_id,
                    'email' => $email,
                    'role' => $role,
                    'registration_time' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            } catch (Exception $e) {
                error_log('Registration audit log failed: ' . $e->getMessage());
            }

            $message = "<div class='success'>Kayıt isteğiniz alındı! Admin onayladıktan sonra giriş yapabilirsiniz.</div>"; // Kullanıcıya dost mesaj
        } else {
            // Benzersiz e-posta hatasını anlaşılır göster
            $errorText = 'Kayıt sırasında bir hata oluştu.';
            if (isset($conn->errno) && $conn->errno === 1062) {
                $errorText = 'Bu e-posta zaten kayıtlı.';
            }
            $message = "<div class='error'>" . htmlspecialchars($errorText) . "</div>";
        }
        $stmt->close();
    } else {
        if ($message === "") {
            $message = "<div class='error'>Zorunlu alanları doldurunuz.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eğitim Sistemi - Kayıt</title>
    <script>
        // Force dark theme ASAP
        (function(){
            try { localStorage.setItem('theme','dark'); } catch(e) {}
            var root = document.documentElement;
            root.setAttribute('data-theme','dark');
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/main-styles.css">
    <link rel="stylesheet" href="assets/css/register-styles.css">
    <style>
        .giris_yap {
            padding-top: 10px  ;
            text-decoration: none;
        }

        .navbarr {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;   
        }
    </style>
</head>
<body>
<header class="navbarr">
<?php include 'navbar.php'; ?>
</header>
<div class="register-container">
    <div class="header-section">
        <div class="header-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <h1 class="header-title">Hesap Oluştur</h1>
        <p class="header-subtitle">Eğitim yolculuğunuza başlayın</p>
    </div>
    
    <div class="form-section">
        <form method="POST" action="" autocomplete="on">
            <div class="role-toggle">
                <div class="role-option active" data-role="student">Öğrenci</div>
                <div class="role-option" data-role="teacher">Öğretmen</div>
            </div>
            
            <input type="hidden" name="role" id="role" value="student">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Adınız *</label>
                    <input id="first_name" type="text" name="first_name" class="form-input" placeholder="Adınız" required autocomplete="given-name">
                </div>
                <div class="form-group">
                    <label for="last_name">Soyadınız</label>
                    <input id="last_name" type="text" name="last_name" class="form-input" placeholder="Soyadınız" autocomplete="family-name">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="school">Okulunuz</label>
                    <input id="school" type="text" name="school" class="form-input" placeholder="Okul adı">
                </div>
                <div class="form-group student-field">
                    <label for="grade">Sınıfınız</label>
                    <select id="grade" name="grade" class="form-select">
                        <option value="">Sınıf seçiniz</option>
                        <option value="4">4. Sınıf</option>
                        <option value="5">5. Sınıf</option>
                        <option value="6">6. Sınıf</option>
                        <option value="7">7. Sınıf</option>
                        <option value="8">8. Sınıf</option>
                    </select>
                </div>
            </div>
            
            <div id="teacher-fields" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Bölüm</label>
                        <input id="department" type="text" name="department" class="form-input" placeholder="Bölüm" autocomplete="organization-title">
                    </div>
                    <div class="form-group">
                        <label for="experience_years">Deneyim Yılı</label>
                        <input id="experience_years" type="number" name="experience_years" class="form-input" placeholder="Deneyim yılı" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="teacherPhone">Telefon (Öğretmenler için zorunlu)</label>
                        <input type="tel" name="phone" id="teacherPhone" class="form-input" placeholder="5xx xxx xx xx" pattern="^[0-9+\-()\s]{7,20}$" autocomplete="tel">
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">E-posta *</label>
                    <input id="email" type="email" name="email" class="form-input" placeholder="E-posta adresiniz" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">Şifre *</label>
                    <input id="password" type="password" name="password" class="form-input" placeholder="Şifreniz" required autocomplete="new-password">
                </div>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-user-plus"></i> Hesap Oluştur
            </button>
        </form>
        
        <div class="nav-link">
            Zaten hesabınız var mı? <a href="index.php" class="giris_yap">Giriş yapın</a>
        </div>
        
        <?php echo $message; ?>
    </div>
</div>

<script>
const roleOptions = document.querySelectorAll('.role-option');
const roleInput = document.getElementById('role');
const teacherFields = document.getElementById('teacher-fields');
const studentFields = document.querySelectorAll('.student-field');
const teacherPhone = document.getElementById('teacherPhone');

function updateRole(selectedRole) {
    roleInput.value = selectedRole;
    
    // Update active state
    roleOptions.forEach(option => {
        option.classList.remove('active');
        if (option.dataset.role === selectedRole) {
            option.classList.add('active');
        }
    });
    
    // Show/hide fields
    if (selectedRole === 'teacher') {
        teacherFields.style.display = 'block';
        studentFields.forEach(field => field.style.display = 'none');
        if (teacherPhone) teacherPhone.setAttribute('required', 'required');
    } else {
        teacherFields.style.display = 'none';
        studentFields.forEach(field => field.style.display = 'block');
        if (teacherPhone) teacherPhone.removeAttribute('required');
    }
}

roleOptions.forEach(option => {
    option.addEventListener('click', () => {
        updateRole(option.dataset.role);
    });
});

// Initialize
updateRole('student');
</script>
<script src="assets/js/ui.js"></script>
</body>
</html>
