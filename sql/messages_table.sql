-- =====================================================
-- MESAJLAŞMA SİSTEMİ TABLOSU
-- Amaç: Kullanıcılar arası mesajlaşma sistemi için gerekli tablo
-- Özellikler: Soft delete, okundu/okunmadı takibi
-- =====================================================

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

-- İndeksler için açıklama:
-- sender_id, receiver_id: Mesaj listelerken hızlı sorgulama için
-- is_read: Okunmamış mesajları hızlı filtrelemek için
-- is_deleted: Aktif mesajları filtrelemek için
