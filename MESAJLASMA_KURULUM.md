# 📬 Mesajlaşma Sistemi Kurulum Rehberi

## 🎯 Özellikler

✅ **Rol Tabanlı Mesajlaşma:**
- Öğrenci → Öğretmen mesaj gönderebilir
- Öğretmen ↔ Öğrenci, Öğretmen ↔ Öğretmen mesajlaşabilir
- Admin → Tüm kullanıcılara mesaj gönderebilir ve tüm mesajları görebilir

✅ **Gelişmiş Özellikler:**
- Okunmamış mesajlar kalın yazı ile gösterilir
- Mesaj silme işlemi AJAX ile yapılır (sayfa yenilenmez)
- Soft delete (is_deleted = 1, fiziksel silme yok)
- Okunmamış mesajlar inbox açıldığında otomatik okundu yapılır
- Navbar'da okunmamış mesaj rozeti
- Modern, responsive ve temiz CSS tasarımı

## 📋 Kurulum Adımları

### 1️⃣ Veritabanı Tablosunu Oluşturun

phpMyAdmin'e gidin ve SQL sekmesinden aşağıdaki komutu çalıştırın:

```sql
-- Alternatif olarak: sql/messages_table.sql dosyasını import edin
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL COMMENT 'Mesajı gönderen kullanıcı ID',
  `receiver_id` int(11) NOT NULL COMMENT 'Mesajı alan kullanıcı ID',
  `subject` varchar(255) NOT NULL COMMENT 'Mesaj konusu',
  `message` text NOT NULL COMMENT 'Mesaj içeriği',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: okunmamış, 1: okundu',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: aktif, 1: silinmiş (soft delete)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Mesaj gönderim tarihi',
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'Mesajın okunma tarihi',
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `is_read` (`is_read`),
  KEY `is_deleted` (`is_deleted`),
  CONSTRAINT `messages_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_receiver_fk` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Kullanıcılar arası mesajlaşma tablosu';
```

**NOT:** `sql/messages_table.sql` dosyasını doğrudan phpMyAdmin'den import edebilirsiniz.

### 2️⃣ Dosya Yapısını Kontrol Edin

Aşağıdaki dosyaların doğru konumlarda olduğundan emin olun:

```
login_system/
├── inbox.php                    # Gelen kutusu sayfası
├── compose.php                  # Yeni mesaj oluşturma sayfası
├── message_actions.php          # AJAX işlemleri (silme, getirme vb.)
├── navbar.php                   # Güncellenmiş navbar (mesaj rozeti ile)
├── admin.php                    # Güncellenmiş admin paneli (mesaj linki)
├── student_panel.php            # Güncellenmiş öğrenci paneli (mesaj linki)
├── teacher_panel.php            # Güncellenmiş öğretmen paneli (mesaj linki)
├── assets/
│   └── css/
│       └── messaging.css        # Mesajlaşma sistem CSS'i
└── sql/
    └── messages_table.sql       # Veritabanı şeması
```

### 3️⃣ CSS Dosyasının Yüklendiğini Doğrulayın

`assets/css/messaging.css` dosyasının mevcut olduğundan emin olun. Bu dosya `inbox.php` ve `compose.php` sayfalarında otomatik olarak yüklenir.

### 4️⃣ Sistemi Test Edin

1. **Admin olarak giriş yapın:**
   - `inbox.php` adresine gidin
   - Navbar'da "Mesajlar" linkini görmelisiniz
   - "Yeni Mesaj" butonuna tıklayın
   - Bir kullanıcı seçin ve mesaj gönderin

2. **Öğretmen olarak giriş yapın:**
   - Sidebar'da "Mesajlar" linkine tıklayın
   - Öğrencilere mesaj gönderebilirsiniz

3. **Öğrenci olarak giriş yapın:**
   - Gelen kutusunda mesajları görün
   - Sadece öğretmenlere mesaj gönderebilirsiniz

## 🎨 CSS Özellikleri

- **Arka plan:** #f5f5f5 (açık gri)
- **Yazı rengi:** #333 (koyu gri)
- **Butonlar:** Mavi gradyan (#4285f4 → #357ae8)
- **Hover efektleri:** Yumuşak geçişler ve gölgelendirme
- **Okunmamış mesajlar:** Açık mavi arka plan (#e8f0fe) ve kalın yazı
- **Responsive:** Mobil cihazlarda otomatik uyum

## 🔒 Güvenlik Özellikleri

✅ **SQL Injection Koruması:**
- Tüm sorgularda prepared statements kullanılır
- Parametre binding ile güvenli veri işleme

✅ **XSS Koruması:**
- `htmlspecialchars()` ile tüm kullanıcı girdileri temizlenir
- JavaScript tarafında `escapeHtml()` fonksiyonu

✅ **Yetki Kontrolü:**
- Her istekte oturum kontrolü yapılır
- Kullanıcılar sadece kendi yetkisi dahilindeki işlemleri yapabilir
- Admin tüm mesajları görebilir, diğerleri sadece kendilerine gelenleri

✅ **CSRF Koruması:**
- Session tabanlı kimlik doğrulama
- AJAX isteklerinde oturum kontrolü

## 📱 Responsive Tasarım

Mobil cihazlarda (<=768px):
- Tablo otomatik kaydırılabilir
- Butonlar ve form elemanları daha büyük tap target'lar
- Modal tam ekran genişliğinde
- Hamburger menü ile kolay navigasyon

## 🛠️ Sorun Giderme

### Problem: Mesaj tablosu oluşturulamadı
**Çözüm:** 
- phpMyAdmin'de `users` tablosunun var olduğundan emin olun
- Foreign key hatası alıyorsanız, önce var olan mesajları silin
- InnoDB engine kullandığınızdan emin olun

### Problem: CSS yüklenmiyor
**Çözüm:**
- `assets/css/messaging.css` dosyasının var olduğunu kontrol edin
- Dosya yollarının doğru olduğunu kontrol edin
- Tarayıcı önbelleğini temizleyin (Ctrl+F5)

### Problem: AJAX çalışmıyor
**Çözüm:**
- Tarayıcı konsolunda hata mesajlarını kontrol edin (F12)
- `message_actions.php` dosyasının doğru konumda olduğunu kontrol edin
- PHP hata raporlamayını açın: `ini_set('display_errors', 1);`

### Problem: Okunmamış mesaj rozeti görünmüyor
**Çözüm:**
- `navbar.php` dosyasının güncel olduğundan emin olun
- Mesajlar tablosunda `is_read` ve `is_deleted` sütunlarının olduğunu kontrol edin
- Sayfa yenilendikten sonra rozetin güncellenip güncellenmediğini test edin

## 📞 Destek

Herhangi bir sorun yaşarsanız:
1. Tarayıcı konsolunu kontrol edin (F12 → Console)
2. PHP hata loglarını kontrol edin
3. Veritabanı bağlantısının çalıştığından emin olun

## 🎉 Tamamlandı!

Mesajlaşma sistemi başarıyla kuruldu! Artık kullanıcılarınız birbirleriyle güvenli bir şekilde mesajlaşabilir.

**Önemli Notlar:**
- Düzenli olarak veritabanı yedeklemesi yapın
- Silinen mesajları (is_deleted = 1) periyodik olarak temizleyebilirsiniz
- Üretim ortamında HTTPS kullanın

---

**Versiyon:** 1.0  
**Uyumluluk:** PHP 7.0+, MySQL 5.7+  
**Tarih:** <?= date('Y-m-d') ?>
