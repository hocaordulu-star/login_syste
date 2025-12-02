-- =====================================================
-- MESAJLAŞMA SİSTEMİ - KONTROL VE TAMİR SQL'LERİ
-- Bu dosyayı kullanarak sorunları tespit edip düzeltebilirsiniz
-- =====================================================

-- 1. KONTROL: Messages tablosu var mı?
SELECT 
    TABLE_NAME, 
    ENGINE, 
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'login_system' 
  AND TABLE_NAME = 'messages';
-- Sonuç boşsa tablo yok, oluşturmalısınız
-- Sonuç varsa tablo zaten mevcut

-- 2. KONTROL: Users tablosu var mı ve yapısı doğru mu?
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    COLUMN_KEY,
    IS_NULLABLE
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'login_system' 
  AND TABLE_NAME = 'users'
ORDER BY ORDINAL_POSITION;
-- 'id' sütunu PRIMARY KEY olmalı

-- 3. TAMİR: Eğer messages tablosu varsa ama sütunlar eksikse
-- (Bu sorguyu çalıştırmadan önce yedek alın!)
/*
ALTER TABLE `messages` 
ADD COLUMN `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Okundu/okunmadı',
ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Soft delete',
ADD COLUMN `read_at` timestamp NULL DEFAULT NULL COMMENT 'Okunma tarihi';
*/

-- 4. TAMİR: Messages tablosunu tamamen sil ve yeniden oluştur
-- (DİKKAT: Tüm mesajlar silinir!)
/*
DROP TABLE IF EXISTS `messages`;
-- Sonra messages_table_simple.sql dosyasını import edin
*/

-- 5. TEST: Mesaj gönderme testi
/*
INSERT INTO messages (sender_id, receiver_id, subject, message) 
VALUES (1, 1, 'Test', 'Test mesajı');

SELECT * FROM messages WHERE id = LAST_INSERT_ID();
*/

-- 6. KONTROL: Foreign key constraint'ler
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'login_system'
  AND TABLE_NAME = 'messages'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
