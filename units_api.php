<?php
/**
 * =============================================================
 * Üniteler API (units_api.php)
 * -------------------------------------------------------------
 * Amaç:
 *  - İstemciden gelen ders (subject) ve sınıf (grade) parametrelerine göre
 *    aktif ünite listesini JSON formatında döndürmek.
 * Erişim:
 *  - Projede genel kullanım amaçlıdır; mevcutta yalnızca giriş yapılmış
 *    kullanıcılar bu endpointi tetiklemektedir (örn. öğretmen panelinde
 *    video düzenleme talebi formu). Ek rol kontrolü burada yapılmaz.
 * Girdi Parametreleri (GET):
 *  - subject: Şu an sadece 'math' desteklenir.
 *  - grade:   '5', '6', '7', '8' değerlerinden biri olmalıdır.
 * Çıktı:
 *  - JSON: { subject, grade, units: [{ id, order, name }] }
 * Notlar:
 *  - mysqlnd olmayan ortamlarda get_result kullanımı sorun çıkarabileceği için
 *    bind_result + fetch yöntemi tercih edilmiştir.
 *  - Bu düzenleme SADECE AÇIKLAMA ekler; işleyiş değişmemiştir.
 * =============================================================
 */

// Oturum başlat (loglanmış kullanıcı varsayımı ile çalışır) ve DB bağlantısını yükle
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/config.php';

// Yanıtın JSON olacağını belirt
header('Content-Type: application/json; charset=utf-8');

// Girdi parametrelerini al (varsayılan ders: math)
$subject = $_GET['subject'] ?? 'math';
$grade   = $_GET['grade'] ?? '';

// Ders doğrulaması: şu an sadece math destekleniyor
$allowedSubjects = ['math'];
if (!in_array($subject, $allowedSubjects, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Geçersiz ders']);
  exit;
}

// Sınıf doğrulaması: 5/6/7/8 dışındaysa 400 döndür
if (!in_array($grade, ['5','6','7','8'], true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Geçersiz sınıf']);
  exit;
}

// mysqlnd olmayan ortamlara uyumluluk için bind_result kullanıyoruz
$sql = "SELECT id, unit_order, unit_name FROM units WHERE subject = ? AND grade = ? AND is_active = 1 ORDER BY unit_order ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['error' => 'Sorgu hazırlanamadı', 'detail' => $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

// Güvenli parametre bağlama (SQL injection önleme)
if (!$stmt->bind_param('ss', $subject, $grade)) {
  http_response_code(500);
  echo json_encode(['error' => 'Parametre bağlanamadı', 'detail' => $stmt->error], JSON_UNESCAPED_UNICODE);
  $stmt->close();
  exit;
}

// Sorguyu çalıştır
if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(['error' => 'Sorgu çalıştırılamadı', 'detail' => $stmt->error], JSON_UNESCAPED_UNICODE);
  $stmt->close();
  exit;
}

// Sonuç kolonlarını değişkenlere bağla
if (!$stmt->bind_result($id, $unit_order, $unit_name)) {
  http_response_code(500);
  echo json_encode(['error' => 'Sonuç bağlanamadı', 'detail' => $stmt->error], JSON_UNESCAPED_UNICODE);
  $stmt->close();
  exit;
}

// Kayıtları döngü ile al ve JSON'a uygun bir diziye dönüştür
$units = [];
while ($stmt->fetch()) {
  $units[] = [
    'id' => (int)$id,
    'order' => (int)$unit_order,
    'name' => (string)$unit_name,
  ];
}
$stmt->close();

// Nihai JSON çıktısı: istemci tarafı select doldurma vs. için kullanılır
echo json_encode(['subject' => $subject, 'grade' => $grade, 'units' => $units], JSON_UNESCAPED_UNICODE);
exit;
?>


