-- =====================================================
-- MESAJLAŞMA SİSTEMİ TABLOSU (BASİT VERSİYON)
-- Foreign Key kısıtlamaları olmadan, daha kolay kurulum
-- =====================================================

-- Önce eski tablo varsa sil (dikkatli kullanın!)
-- DROP TABLE IF EXISTS `messages`;

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
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Kullanıcılar arası mesajlaşma tablosu';

-- Test verisi (opsiyonel - admin'den admin'e test mesajı)
-- INSERT INTO messages (sender_id, receiver_id, subject, message) 
-- VALUES (1, 1, 'Test Mesajı', 'Bu bir test mesajıdır. Sistem çalışıyor!');
