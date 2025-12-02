<?php
/**
 * contact_submit.php (Devre Dışı Bırakıldı)
 *
 * Durum: 410 Gone
 * - Bu endpoint artık kullanılmıyor ve kalıcı olarak devre dışı.
 * - Amaç: Eski iletişim formu gönderimlerini net bir şekilde sonlandırmak.
 *
 * Neden 410? (Kısa bilgi)
 * - 404: Kaynak bulunamadı (geçici/kalıcı belirsiz)
 * - 410: Kaynak kalıcı olarak kaldırıldı (istemcilere açık sinyal)
 *
 * Güvenlik / Gelecek için öneriler:
 * - Yeni bir iletişim API'si gerekiyorsa `contact_api.php` gibi ayrı bir dosyada
 *   reCAPTCHA, rate limit ve sunucu tarafı validasyon ile kurun.
 * - E-posta gönderimi için güvenli bir SMTP (örn. PHPMailer) ya da bir e-posta
 *   sağlayıcı API'si (SendGrid, Mailgun) kullanın. Kimlik bilgilerini .env'den okuyun.
 *
 * Not: Bu dosyada yalnızca açıklama eklendi; çalışma mantığı değişmedi.
 */

http_response_code(410); // 410 Gone: Kaynak artık mevcut değil
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'message' => 'İletişim sistemi kaldırıldı. Bu endpoint devre dışıdır.',
]);
?>
