-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 31 Ağu 2025, 10:21:54
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `login_system`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `admin_audit_logs`
--

CREATE TABLE `admin_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `admin_audit_logs`
--

INSERT INTO `admin_audit_logs` (`id`, `admin_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 12:52:23\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 13:52:23'),
(2, 1, 'user_registration', '{\"new_user_id\":3,\"email\":\"teacher@example.com\",\"role\":\"teacher\",\"registration_time\":\"2025-08-21 12:52:57\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 13:52:57'),
(3, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 12:54:35\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 13:54:35'),
(4, 1, 'bulk_user_operation', '{\"operation\":\"activate\",\"user_ids\":[3],\"data\":[]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 13:57:32'),
(5, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 12:57:34\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 13:57:34'),
(6, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 13:21:29\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 14:21:29'),
(7, 1, 'user_registration', '{\"new_user_id\":4,\"email\":\"student@example.com\",\"role\":\"student\",\"registration_time\":\"2025-08-21 13:21:57\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 14:21:57'),
(8, 1, 'bulk_user_operation', '{\"operation\":\"activate\",\"user_ids\":[4],\"data\":[]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 14:22:10'),
(9, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 13:22:13\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 14:22:13'),
(10, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 13:25:03\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 14:25:03'),
(11, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 13:27:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 14:27:52'),
(12, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 13:33:14\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 14:33:14'),
(13, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 13:36:16\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 14:36:16'),
(14, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 13:51:39\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 14:51:39'),
(15, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:03:07\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:03:07'),
(16, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:07:29\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:07:29'),
(17, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:15:32\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:15:32'),
(18, 1, 'bulk_video_operation', '{\"operation\":\"delete\",\"video_ids\":[7],\"data\":[]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:20:49'),
(19, 1, 'bulk_video_operation', '{\"operation\":\"delete\",\"video_ids\":[6],\"data\":[]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:21:09'),
(20, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:21:31\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:21:31'),
(21, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:35:14\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:35:14'),
(22, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:39:54\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:39:54'),
(23, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:48:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:48:52'),
(24, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:50:31\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:50:31'),
(25, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:52:03\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:52:03'),
(26, 1, 'bulk_video_operation', '{\"operation\":\"delete\",\"video_ids\":[9],\"data\":[]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:58:17'),
(27, 1, 'bulk_video_operation', '{\"operation\":\"delete\",\"video_ids\":[8],\"data\":[]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:58:20'),
(28, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 14:59:27\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 15:59:27'),
(29, 1, 'bulk_video_operation', '{\"operation\":\"approve\",\"video_ids\":[10],\"data\":[]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 16:00:49'),
(30, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 15:00:57\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 16:00:57'),
(31, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 15:01:03\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 16:01:03'),
(32, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 15:07:11\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 16:07:11'),
(33, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 15:18:21\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-21 16:18:21'),
(34, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-21 17:00:35\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 18:00:35'),
(35, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-23 20:09:35\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:09:35'),
(36, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-23 20:25:26\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:25:26'),
(37, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-23 20:31:55\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:31:55'),
(38, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-23 20:54:56\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:54:56'),
(39, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-23 20:55:01\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:55:01'),
(40, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-23 21:24:29\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-23 22:24:29'),
(41, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-24 12:03:24\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 13:03:24'),
(42, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-24 14:29:28\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-24 15:29:28'),
(43, 1, 'user_registration', '{\"new_user_id\":5,\"email\":\"g@xn--gma-uza\",\"role\":\"student\",\"registration_time\":\"2025-08-24 14:33:32\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-24 15:33:32'),
(44, 1, 'bulk_user_operation', '{\"operation\":\"activate\",\"user_ids\":[5],\"data\":[]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 15:34:24'),
(45, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-24 14:35:34\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-24 15:35:34'),
(46, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-24 14:52:48\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 15:52:48'),
(47, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-24 15:50:25\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-24 16:50:25'),
(48, 1, 'video_edit_request_rejected', '{\"request_id\":1,\"admin_note\":\"ytrew\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-24 17:00:47'),
(49, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-24 16:00:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-24 17:00:52'),
(50, 1, 'video_edit_request_approved', '{\"request_id\":2,\"video_id\":10,\"request_type\":\"edit\",\"admin_note\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-24 17:11:09'),
(51, 1, 'bulk_user_operation', '{\"operation\":\"deactivate\",\"user_ids\":[5],\"data\":[]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:25:05'),
(52, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 08:25:26\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:25:26'),
(53, 1, 'bulk_video_operation', '{\"operation\":\"approve\",\"video_ids\":[11],\"data\":[]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:27:33'),
(54, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 08:27:48\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:27:48'),
(55, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 08:29:22\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:29:22'),
(56, 1, 'video_edit_request_approved', '{\"request_id\":3,\"video_id\":10,\"request_type\":\"delete\",\"admin_note\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 09:53:28'),
(57, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 08:57:49\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 09:57:49'),
(58, 1, 'video_edit_request_approved', '{\"request_id\":4,\"video_id\":11,\"request_type\":\"delete\",\"admin_note\":\"olmaz\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 09:58:29'),
(59, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 08:59:00\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 09:59:00'),
(60, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 09:01:08\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:01:08'),
(61, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 09:09:27\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:09:27'),
(62, 1, 'bulk_video_operation', '{\"operation\":\"approve\",\"video_ids\":[12],\"data\":[]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:10:24'),
(63, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 09:10:27\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:10:27'),
(64, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 09:11:15\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:11:15'),
(65, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 09:12:49\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:12:49'),
(66, 1, 'video_edit_request_approved', '{\"request_id\":8,\"video_id\":12,\"request_type\":\"edit\",\"admin_note\":\"c\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:40:00'),
(67, 1, 'video_edit_request_approved', '{\"request_id\":7,\"video_id\":12,\"request_type\":\"delete\",\"admin_note\":\"c\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:40:03'),
(68, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 09:40:10\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:40:10'),
(69, 1, 'bulk_video_operation', '{\"operation\":\"approve\",\"video_ids\":[13],\"data\":[]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:41:01'),
(70, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 09:41:03\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:41:03'),
(71, 1, 'video_edit_request_approved', '{\"request_id\":9,\"video_id\":13,\"request_type\":\"edit\",\"admin_note\":\"tamam\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:41:55'),
(72, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 09:41:58\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 6.0; Nexus 5 Build\\/MRA58N) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Mobile Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 10:41:58'),
(73, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 15:49:09\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 16:49:09'),
(74, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 15:58:18\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 16:58:18'),
(75, 1, 'chat_send_message', '{\"conversation_id\":4,\"message_id\":4,\"length\":7}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:08:56'),
(76, 1, 'chat_send_message', '{\"conversation_id\":4,\"message_id\":5,\"length\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:09:00'),
(77, 1, 'chat_send_message', '{\"conversation_id\":25,\"message_id\":6,\"length\":7}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:16:27'),
(78, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 16:17:20\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:17:20'),
(79, 3, 'chat_send_message', '{\"conversation_id\":36,\"message_id\":7,\"length\":6}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:27:43'),
(80, 3, 'chat_send_message', '{\"conversation_id\":40,\"message_id\":8,\"length\":6}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:27:52'),
(81, 1, 'chat_send_message', '{\"conversation_id\":30,\"message_id\":9,\"length\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:28:07'),
(82, 1, 'chat_send_message', '{\"conversation_id\":29,\"message_id\":10,\"length\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:28:10'),
(83, 1, 'chat_send_message', '{\"conversation_id\":30,\"message_id\":11,\"length\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:28:13'),
(84, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 16:29:26\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:29:26'),
(85, 4, 'chat_send_message', '{\"conversation_id\":30,\"message_id\":12,\"length\":21}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:29:40'),
(86, 1, 'chat_send_message', '{\"conversation_id\":30,\"message_id\":13,\"length\":16}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:30:07'),
(87, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 16:33:20\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:33:20'),
(88, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 16:34:04\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:34:04'),
(89, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 16:34:18\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:34:18'),
(90, 1, 'logout', '{\"user_id\":1,\"email\":\"admin@example.com\",\"logout_time\":\"2025-08-25 16:37:10\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:37:10'),
(91, 4, 'chat_send_message', '{\"conversation_id\":40,\"message_id\":19,\"length\":108}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 17:49:36'),
(92, 3, 'chat_send_message', '{\"conversation_id\":36,\"message_id\":20,\"length\":7}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 13:04:34');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('general','video','test') NOT NULL DEFAULT 'general',
  `video_id` int(10) UNSIGNED DEFAULT NULL,
  `quiz_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `conversations`
--

INSERT INTO `conversations` (`id`, `type`, `video_id`, `quiz_id`, `created_at`, `updated_at`) VALUES
(1, 'general', NULL, NULL, '2025-08-25 17:08:41', '2025-08-25 17:08:41'),
(2, 'general', NULL, NULL, '2025-08-25 17:08:42', '2025-08-25 17:08:42'),
(3, 'general', NULL, NULL, '2025-08-25 17:08:42', '2025-08-25 17:08:42'),
(4, 'general', NULL, NULL, '2025-08-25 17:08:44', '2025-08-25 17:09:00'),
(5, 'general', NULL, NULL, '2025-08-25 17:09:05', '2025-08-25 17:09:05'),
(6, 'general', NULL, NULL, '2025-08-25 17:09:05', '2025-08-25 17:09:05'),
(7, 'general', NULL, NULL, '2025-08-25 17:09:06', '2025-08-25 17:09:06'),
(8, 'general', NULL, NULL, '2025-08-25 17:09:06', '2025-08-25 17:09:06'),
(9, 'general', NULL, NULL, '2025-08-25 17:09:06', '2025-08-25 17:09:06'),
(10, 'general', NULL, NULL, '2025-08-25 17:09:07', '2025-08-25 17:09:07'),
(11, 'general', NULL, NULL, '2025-08-25 17:09:07', '2025-08-25 17:09:07'),
(12, 'general', NULL, NULL, '2025-08-25 17:16:12', '2025-08-25 17:16:12'),
(13, 'general', NULL, NULL, '2025-08-25 17:16:12', '2025-08-25 17:16:12'),
(14, 'general', NULL, NULL, '2025-08-25 17:16:13', '2025-08-25 17:16:13'),
(15, 'general', NULL, NULL, '2025-08-25 17:16:13', '2025-08-25 17:16:13'),
(16, 'general', NULL, NULL, '2025-08-25 17:16:14', '2025-08-25 17:16:14'),
(17, 'general', NULL, NULL, '2025-08-25 17:16:15', '2025-08-25 17:16:15'),
(18, 'general', NULL, NULL, '2025-08-25 17:16:15', '2025-08-25 17:16:15'),
(19, 'general', NULL, NULL, '2025-08-25 17:16:17', '2025-08-25 17:16:17'),
(20, 'general', NULL, NULL, '2025-08-25 17:16:17', '2025-08-25 17:16:17'),
(21, 'general', NULL, NULL, '2025-08-25 17:16:18', '2025-08-25 17:16:18'),
(22, 'general', NULL, NULL, '2025-08-25 17:16:21', '2025-08-25 17:16:21'),
(23, 'general', NULL, NULL, '2025-08-25 17:16:21', '2025-08-25 17:16:21'),
(24, 'general', NULL, NULL, '2025-08-25 17:16:22', '2025-08-25 17:16:22'),
(25, 'general', NULL, NULL, '2025-08-25 17:16:22', '2025-08-25 17:16:27'),
(26, 'general', NULL, NULL, '2025-08-25 17:16:30', '2025-08-25 17:16:30'),
(27, 'general', NULL, NULL, '2025-08-25 17:16:30', '2025-08-25 17:16:30'),
(28, 'general', NULL, NULL, '2025-08-25 17:16:31', '2025-08-25 17:16:31'),
(29, 'general', NULL, NULL, '2025-08-25 17:16:31', '2025-08-25 17:28:10'),
(30, 'general', NULL, NULL, '2025-08-25 17:16:32', '2025-08-25 17:30:07'),
(31, 'general', NULL, NULL, '2025-08-25 17:17:27', '2025-08-25 17:17:27'),
(32, 'general', NULL, NULL, '2025-08-25 17:17:28', '2025-08-25 17:17:28'),
(33, 'general', NULL, NULL, '2025-08-25 17:17:28', '2025-08-25 17:17:28'),
(34, 'general', NULL, NULL, '2025-08-25 17:17:30', '2025-08-25 17:17:30'),
(35, 'general', NULL, NULL, '2025-08-25 17:17:30', '2025-08-25 17:17:30'),
(36, 'general', NULL, NULL, '2025-08-25 17:17:31', '2025-08-28 13:04:34'),
(37, 'general', NULL, NULL, '2025-08-25 17:17:31', '2025-08-25 17:17:31'),
(38, 'general', NULL, NULL, '2025-08-25 17:17:31', '2025-08-25 17:17:31'),
(39, 'general', NULL, NULL, '2025-08-25 17:17:31', '2025-08-25 17:17:31'),
(40, 'general', NULL, NULL, '2025-08-25 17:17:32', '2025-08-25 17:49:36');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `conversation_participants`
--

INSERT INTO `conversation_participants` (`id`, `conversation_id`, `user_id`, `role`, `last_read_at`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'teacher', NULL, '2025-08-25 17:08:41', '2025-08-25 17:08:41'),
(2, 1, 1, 'admin', '2025-08-25 17:09:02', '2025-08-25 17:08:41', '2025-08-25 17:09:02'),
(3, 2, 5, 'student', NULL, '2025-08-25 17:08:42', '2025-08-25 17:08:42'),
(4, 2, 1, 'admin', '2025-08-25 17:09:04', '2025-08-25 17:08:42', '2025-08-25 17:09:04'),
(5, 3, 4, 'student', NULL, '2025-08-25 17:08:42', '2025-08-25 17:08:42'),
(6, 3, 1, 'admin', '2025-08-25 17:09:04', '2025-08-25 17:08:42', '2025-08-25 17:09:04'),
(7, 4, 3, 'teacher', NULL, '2025-08-25 17:08:44', '2025-08-25 17:08:44'),
(8, 4, 1, 'admin', '2025-08-25 17:09:01', '2025-08-25 17:08:44', '2025-08-25 17:09:01'),
(11, 5, 4, 'student', NULL, '2025-08-25 17:09:05', '2025-08-25 17:09:05'),
(12, 5, 1, 'admin', '2025-08-25 17:09:05', '2025-08-25 17:09:05', '2025-08-25 17:09:05'),
(13, 6, 5, 'student', NULL, '2025-08-25 17:09:05', '2025-08-25 17:09:05'),
(14, 6, 1, 'admin', '2025-08-25 17:09:05', '2025-08-25 17:09:05', '2025-08-25 17:09:05'),
(15, 7, 3, 'teacher', NULL, '2025-08-25 17:09:06', '2025-08-25 17:09:06'),
(16, 7, 1, 'admin', '2025-08-25 17:09:06', '2025-08-25 17:09:06', '2025-08-25 17:09:06'),
(17, 8, 5, 'student', NULL, '2025-08-25 17:09:06', '2025-08-25 17:09:06'),
(18, 8, 1, 'admin', '2025-08-25 17:09:06', '2025-08-25 17:09:06', '2025-08-25 17:09:06'),
(19, 9, 4, 'student', NULL, '2025-08-25 17:09:06', '2025-08-25 17:09:06'),
(20, 9, 1, 'admin', '2025-08-25 17:09:07', '2025-08-25 17:09:06', '2025-08-25 17:09:07'),
(21, 10, 3, 'teacher', NULL, '2025-08-25 17:09:07', '2025-08-25 17:09:07'),
(22, 10, 1, 'admin', '2025-08-25 17:09:07', '2025-08-25 17:09:07', '2025-08-25 17:09:07'),
(23, 11, 3, 'teacher', NULL, '2025-08-25 17:09:07', '2025-08-25 17:09:07'),
(24, 11, 1, 'admin', '2025-08-25 17:16:11', '2025-08-25 17:09:07', '2025-08-25 17:16:11'),
(25, 12, 4, 'student', NULL, '2025-08-25 17:16:12', '2025-08-25 17:16:12'),
(26, 12, 1, 'admin', '2025-08-25 17:16:12', '2025-08-25 17:16:12', '2025-08-25 17:16:12'),
(27, 13, 5, 'student', NULL, '2025-08-25 17:16:12', '2025-08-25 17:16:12'),
(28, 13, 1, 'admin', '2025-08-25 17:16:12', '2025-08-25 17:16:12', '2025-08-25 17:16:12'),
(29, 14, 3, 'teacher', NULL, '2025-08-25 17:16:13', '2025-08-25 17:16:13'),
(30, 14, 1, 'admin', '2025-08-25 17:16:13', '2025-08-25 17:16:13', '2025-08-25 17:16:13'),
(31, 15, 4, 'student', NULL, '2025-08-25 17:16:13', '2025-08-25 17:16:13'),
(32, 15, 1, 'admin', '2025-08-25 17:16:13', '2025-08-25 17:16:13', '2025-08-25 17:16:13'),
(33, 16, 5, 'student', NULL, '2025-08-25 17:16:14', '2025-08-25 17:16:14'),
(34, 16, 1, 'admin', '2025-08-25 17:16:14', '2025-08-25 17:16:14', '2025-08-25 17:16:14'),
(35, 17, 5, 'student', NULL, '2025-08-25 17:16:15', '2025-08-25 17:16:15'),
(36, 17, 1, 'admin', '2025-08-25 17:16:15', '2025-08-25 17:16:15', '2025-08-25 17:16:15'),
(37, 18, 3, 'teacher', NULL, '2025-08-25 17:16:15', '2025-08-25 17:16:15'),
(38, 18, 1, 'admin', '2025-08-25 17:16:17', '2025-08-25 17:16:15', '2025-08-25 17:16:17'),
(39, 19, 4, 'student', NULL, '2025-08-25 17:16:17', '2025-08-25 17:16:17'),
(40, 19, 1, 'admin', '2025-08-25 17:16:17', '2025-08-25 17:16:17', '2025-08-25 17:16:17'),
(41, 20, 5, 'student', NULL, '2025-08-25 17:16:17', '2025-08-25 17:16:17'),
(42, 20, 1, 'admin', '2025-08-25 17:16:17', '2025-08-25 17:16:17', '2025-08-25 17:16:17'),
(43, 21, 5, 'student', NULL, '2025-08-25 17:16:18', '2025-08-25 17:16:18'),
(44, 21, 1, 'admin', '2025-08-25 17:16:20', '2025-08-25 17:16:18', '2025-08-25 17:16:20'),
(45, 22, 5, 'student', NULL, '2025-08-25 17:16:21', '2025-08-25 17:16:21'),
(46, 22, 1, 'admin', '2025-08-25 17:16:21', '2025-08-25 17:16:21', '2025-08-25 17:16:21'),
(47, 23, 4, 'student', NULL, '2025-08-25 17:16:21', '2025-08-25 17:16:21'),
(48, 23, 1, 'admin', '2025-08-25 17:16:23', '2025-08-25 17:16:21', '2025-08-25 17:16:23'),
(49, 24, 5, 'student', NULL, '2025-08-25 17:16:22', '2025-08-25 17:16:22'),
(50, 24, 1, 'admin', '2025-08-25 17:16:29', '2025-08-25 17:16:22', '2025-08-25 17:16:29'),
(51, 25, 3, 'teacher', NULL, '2025-08-25 17:16:22', '2025-08-25 17:16:22'),
(52, 25, 1, 'admin', '2025-08-25 17:16:27', '2025-08-25 17:16:22', '2025-08-25 17:16:27'),
(54, 26, 4, 'student', NULL, '2025-08-25 17:16:30', '2025-08-25 17:16:30'),
(55, 26, 1, 'admin', '2025-08-25 17:16:30', '2025-08-25 17:16:30', '2025-08-25 17:16:30'),
(56, 27, 5, 'student', NULL, '2025-08-25 17:16:30', '2025-08-25 17:16:30'),
(57, 27, 1, 'admin', '2025-08-25 17:16:31', '2025-08-25 17:16:30', '2025-08-25 17:16:31'),
(58, 28, 3, 'teacher', '2025-08-25 17:17:26', '2025-08-25 17:16:31', '2025-08-25 17:17:26'),
(59, 28, 1, 'admin', '2025-08-25 17:17:16', '2025-08-25 17:16:31', '2025-08-25 17:17:16'),
(60, 29, 5, 'student', NULL, '2025-08-25 17:16:31', '2025-08-25 17:16:31'),
(61, 29, 1, 'admin', '2025-08-25 17:37:08', '2025-08-25 17:16:31', '2025-08-25 17:37:08'),
(62, 30, 4, 'student', '2025-08-25 17:37:17', '2025-08-25 17:16:32', '2025-08-25 17:37:17'),
(63, 30, 1, 'admin', '2025-08-25 17:35:10', '2025-08-25 17:16:32', '2025-08-25 17:35:10'),
(64, 31, 4, 'student', NULL, '2025-08-25 17:17:27', '2025-08-25 17:17:27'),
(65, 31, 3, 'teacher', '2025-08-25 17:17:30', '2025-08-25 17:17:27', '2025-08-25 17:17:30'),
(66, 32, 5, 'student', NULL, '2025-08-25 17:17:28', '2025-08-25 17:17:28'),
(67, 32, 3, 'teacher', '2025-08-25 17:17:30', '2025-08-25 17:17:28', '2025-08-25 17:17:30'),
(68, 33, 1, 'admin', NULL, '2025-08-25 17:17:28', '2025-08-25 17:17:28'),
(69, 33, 3, 'teacher', '2025-08-25 17:17:29', '2025-08-25 17:17:28', '2025-08-25 17:17:29'),
(70, 34, 4, 'student', NULL, '2025-08-25 17:17:30', '2025-08-25 17:17:30'),
(71, 34, 3, 'teacher', '2025-08-25 17:17:30', '2025-08-25 17:17:30', '2025-08-25 17:17:30'),
(72, 35, 1, 'admin', NULL, '2025-08-25 17:17:30', '2025-08-25 17:17:30'),
(73, 35, 3, 'teacher', '2025-08-25 17:17:30', '2025-08-25 17:17:30', '2025-08-25 17:17:30'),
(74, 36, 1, 'admin', '2025-08-25 17:35:11', '2025-08-25 17:17:31', '2025-08-25 17:35:11'),
(75, 36, 3, 'teacher', '2025-08-28 13:04:37', '2025-08-25 17:17:31', '2025-08-28 13:04:37'),
(76, 37, 1, 'admin', NULL, '2025-08-25 17:17:31', '2025-08-25 17:17:31'),
(77, 37, 3, 'teacher', '2025-08-25 17:17:31', '2025-08-25 17:17:31', '2025-08-25 17:17:31'),
(78, 38, 5, 'student', NULL, '2025-08-25 17:17:31', '2025-08-25 17:17:31'),
(79, 38, 3, 'teacher', '2025-08-28 13:04:39', '2025-08-25 17:17:31', '2025-08-28 13:04:39'),
(80, 39, 5, 'student', NULL, '2025-08-25 17:17:31', '2025-08-25 17:17:31'),
(81, 39, 3, 'teacher', '2025-08-25 17:17:31', '2025-08-25 17:17:31', '2025-08-25 17:17:31'),
(82, 40, 4, 'student', '2025-08-25 17:49:43', '2025-08-25 17:17:32', '2025-08-25 17:49:43'),
(83, 40, 3, 'teacher', '2025-08-28 13:04:38', '2025-08-25 17:17:32', '2025-08-28 13:04:38');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED DEFAULT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `receiver_id`, `topic`, `message`, `context`, `is_read`, `created_at`) VALUES
(1, NULL, 1, 3, 'video_edit_request', 'Video düzenleme talebiniz onaylandı. Talep ID: #8. Not: c', '{}', 0, '2025-08-25 10:40:00'),
(2, NULL, 1, 3, 'video_edit_request', 'Video düzenleme talebiniz onaylandı. Talep ID: #7. Not: c', '{}', 0, '2025-08-25 10:40:03'),
(3, NULL, 1, 3, 'video_edit_request', 'Video düzenleme talebiniz onaylandı. Talep ID: #9. Not: tamam', '{}', 0, '2025-08-25 10:41:55'),
(4, 4, 1, NULL, NULL, 'merhaba', NULL, 1, '2025-08-25 17:08:56'),
(5, 4, 1, NULL, NULL, 's', NULL, 1, '2025-08-25 17:09:00'),
(6, 25, 1, NULL, NULL, 'mhabaer', NULL, 1, '2025-08-25 17:16:27'),
(7, 36, 3, NULL, NULL, 'sdfgbn', NULL, 1, '2025-08-25 17:27:43'),
(8, 40, 3, NULL, NULL, 'fdsfds', NULL, 1, '2025-08-25 17:27:52'),
(9, 30, 1, NULL, NULL, 'ds', NULL, 1, '2025-08-25 17:28:07'),
(10, 29, 1, NULL, NULL, 'ds', NULL, 1, '2025-08-25 17:28:10'),
(11, 30, 1, NULL, NULL, 'fds', NULL, 1, '2025-08-25 17:28:13'),
(12, 30, 4, NULL, NULL, 'tamam abi ne kızıyn', NULL, 1, '2025-08-25 17:29:40'),
(13, 30, 1, NULL, NULL, 'kızmıyom sakin', NULL, 1, '2025-08-25 17:30:07'),
(19, 40, 4, NULL, NULL, 'Merhaba hocam, bu video hakkında bir mesajım var: gfew \nhttp://localhost/login-system/video_view.php?id=13', NULL, 1, '2025-08-25 17:49:36'),
(20, 36, 3, NULL, NULL, 'merhaba', NULL, 1, '2025-08-28 13:04:34');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `owner_teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `total_points` int(11) DEFAULT 0,
  `status` enum('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
  `version` int(11) NOT NULL DEFAULT 1,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `status` enum('in_progress','submitted','graded','expired') NOT NULL DEFAULT 'in_progress',
  `score` int(11) DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `time_spent_seconds` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quiz_attempt_answers`
--

CREATE TABLE `quiz_attempt_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `option_id` int(10) UNSIGNED DEFAULT NULL,
  `text_value` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_awarded` int(11) DEFAULT NULL,
  `answered_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quiz_options`
--

CREATE TABLE `quiz_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_no` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `type` enum('mcq','multi','truefalse','short','numeric') NOT NULL DEFAULT 'mcq',
  `explanation` text DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 1,
  `order_no` int(11) NOT NULL DEFAULT 1,
  `text` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quiz_reviews`
--

CREATE TABLE `quiz_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `reviewer_admin_id` int(10) UNSIGNED NOT NULL,
  `decision` enum('approved','rejected') NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `students`
--

CREATE TABLE `students` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `school` varchar(150) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `students`
--

INSERT INTO `students` (`user_id`, `school`, `grade`) VALUES
(4, 'innnn', '5'),
(5, NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `system_cache`
--

CREATE TABLE `system_cache` (
  `cache_key` varchar(190) NOT NULL,
  `cache_data` mediumtext NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `system_cache`
--

INSERT INTO `system_cache` (`cache_key`, `cache_data`, `expires_at`) VALUES
('admin_dashboard_stats', '{\"users\":{\"total_users\":\"4\",\"active_users\":\"3\",\"inactive_users\":\"1\",\"teachers\":\"1\",\"students\":\"2\",\"active_24h\":\"3\"},\"videos\":{\"total_videos\":\"1\",\"pending_videos\":\"0\",\"approved_videos\":\"1\",\"featured_videos\":\"0\",\"new_24h\":\"1\"},\"system\":{\"total_messages\":0,\"unread_messages\":0,\"new_messages_24h\":0},\"performance\":{\"database_size\":\"1.23\",\"cache_hit_rate\":0,\"avg_response_time\":125},\"generated_at\":\"2025-08-25 16:33:30\"}', '2025-08-25 17:38:30'),
('admin_users_de6428babc12f7a60b37046715ff690e', '{\"users\":[{\"id\":5,\"full_name\":\"gg \",\"first_name\":\"gg\",\"last_name\":\"\",\"email\":\"g@xn--gma-uza\",\"phone\":\"\",\"role\":\"student\",\"status\":\"rejected\",\"created_at\":\"2025-08-24 15:33:32\",\"last_login\":null},{\"id\":4,\"full_name\":\"öğrenci acaa\",\"first_name\":\"öğrenci\",\"last_name\":\"acaa\",\"email\":\"student@example.com\",\"phone\":null,\"role\":\"student\",\"status\":\"approved\",\"created_at\":\"2025-08-21 14:21:57\",\"last_login\":\"2025-08-25 17:29:29\"},{\"id\":3,\"full_name\":\"öğretmen ben\",\"first_name\":\"öğretmen\",\"last_name\":\"ben\",\"email\":\"teacher@example.com\",\"phone\":\"555555555555\",\"role\":\"teacher\",\"status\":\"approved\",\"created_at\":\"2025-08-21 13:52:57\",\"last_login\":\"2025-08-25 17:29:46\"},{\"id\":1,\"full_name\":\"Admin User\",\"first_name\":\"Admin\",\"last_name\":\"User\",\"email\":\"admin@example.com\",\"phone\":null,\"role\":\"admin\",\"status\":\"approved\",\"created_at\":\"2025-08-21 13:26:11\",\"last_login\":\"2025-08-25 17:29:55\"}],\"total\":4,\"page\":1,\"limit\":20,\"pages\":1}', '2025-08-25 17:37:45'),
('admin_videos_09dcebf3c0bcfd1708f2ebeb799e0022', '{\"videos\":[{\"id\":13,\"user_id\":null,\"uploaded_by\":3,\"subject\":\"math\",\"grade\":\"8\",\"unit_id\":26,\"unit\":\"Sayılar ve Nicelikler (1)\",\"topic\":\"f\",\"title\":\"gfew\",\"description\":\"gf\",\"file_path\":\"video_68ac1371eaadf.mp4\",\"youtube_url\":\"https:\\/\\/www.youtube.com\\/embed\\/x5gre8Pf-ZY\",\"filename\":null,\"status\":\"approved\",\"featured\":0,\"is_featured\":0,\"approved_by\":1,\"rejection_reason\":null,\"view_count\":0,\"created_at\":\"2025-08-25 10:40:33\",\"updated_at\":\"2025-08-25 10:41:55\",\"uploader_name\":\"öğretmen ben\",\"completion_rate\":0,\"average_rating\":0}],\"total\":1,\"page\":1,\"limit\":20,\"pages\":1}', '2025-08-25 17:38:14'),
('bookmark_1_10', 'false', '2025-08-24 15:02:46'),
('bookmark_1_6', 'false', '2025-08-21 14:30:01'),
('bookmark_1_7', 'true', '2025-08-21 14:29:54'),
('bookmark_1_8', 'false', '2025-08-21 14:59:02'),
('bookmark_3_10', 'false', '2025-08-24 15:37:43'),
('bookmark_3_11', 'false', '2025-08-25 08:39:46'),
('bookmark_3_12', 'false', '2025-08-25 09:21:27'),
('bookmark_3_13', 'false', '2025-08-25 09:52:18'),
('bookmark_4_10', 'false', '2025-08-21 15:28:56'),
('bookmark_4_13', 'false', '2025-08-25 16:47:24'),
('message_stats', '{\"total_messages\":\"1\",\"unread_messages\":\"1\",\"messages_24h\":\"0\",\"messages_7d\":\"1\",\"active_senders\":\"1\",\"active_receivers\":\"0\"}', '2025-08-23 22:04:42'),
('pending_video_edit_requests_1_1', '{\"requests\":[],\"total\":0,\"page\":1,\"limit\":1,\"pages\":0}', '2025-08-25 17:38:01'),
('pending_video_edit_requests_1_20', '{\"requests\":[],\"total\":0,\"page\":1,\"limit\":20,\"pages\":0}', '2025-08-25 17:13:36'),
('quiz_exists_10', 'false', '2025-08-24 15:57:43'),
('quiz_exists_11', 'false', '2025-08-25 08:59:46'),
('quiz_exists_12', 'false', '2025-08-25 09:41:27'),
('quiz_exists_13', 'false', '2025-08-25 17:07:24'),
('quiz_exists_6', 'false', '2025-08-21 14:50:01'),
('quiz_exists_7', 'false', '2025-08-21 14:49:54'),
('quiz_exists_8', 'false', '2025-08-21 15:19:02'),
('teacher_dashboard_stats_2', '{\"videos\":{\"total_videos\":0,\"pending_videos\":null,\"approved_videos\":null,\"rejected_videos\":null,\"videos_this_week\":null,\"videos_this_month\":null},\"engagement\":{\"total_views\":\"0\",\"avg_completion_rate\":\"0.000000\",\"total_likes\":\"0\",\"unique_viewers\":0},\"activity\":{\"edit_requests_week\":0,\"pending_requests\":0,\"messages_week\":0},\"generated_at\":\"2025-08-25 08:52:39\"}', '2025-08-25 09:57:39'),
('teacher_dashboard_stats_3', '{\"videos\":{\"total_videos\":1,\"pending_videos\":\"0\",\"approved_videos\":\"1\",\"rejected_videos\":\"0\",\"videos_this_week\":\"1\",\"videos_this_month\":\"1\"},\"engagement\":{\"total_views\":\"0\",\"avg_completion_rate\":\"0.000000\",\"total_likes\":\"0\",\"unique_viewers\":0},\"activity\":{\"edit_requests_week\":1,\"pending_requests\":0,\"messages_week\":0},\"generated_at\":\"2025-08-28 12:04:04\"}', '2025-08-28 13:09:04'),
('teacher_video_analytics_3_30', '{\"most_viewed\":[],\"grade_distribution\":[{\"grade\":\"8\",\"count\":1,\"avg_views\":null}],\"upload_trends\":[{\"date\":\"2025-08-25\",\"count\":1}]}', '2025-08-25 17:28:54'),
('teacher_videos_2_51cefd0fc8cb89713c1ec019d1073fa1', '{\"videos\":[],\"total\":0,\"page\":1,\"limit\":5,\"pages\":0}', '2025-08-25 09:57:39'),
('teacher_videos_3_51cefd0fc8cb89713c1ec019d1073fa1', '{\"videos\":[{\"id\":13,\"user_id\":null,\"uploaded_by\":3,\"subject\":\"math\",\"grade\":\"8\",\"unit_id\":26,\"unit\":\"Sayılar ve Nicelikler (1)\",\"topic\":\"f\",\"title\":\"gfew\",\"description\":\"gf\",\"file_path\":\"video_68ac1371eaadf.mp4\",\"youtube_url\":\"https:\\/\\/www.youtube.com\\/embed\\/x5gre8Pf-ZY\",\"filename\":null,\"status\":\"approved\",\"featured\":0,\"is_featured\":0,\"approved_by\":1,\"rejection_reason\":null,\"view_count\":null,\"created_at\":\"2025-08-25 10:40:33\",\"updated_at\":\"2025-08-25 10:41:55\",\"completion_rate\":null,\"like_count\":null,\"last_change_summary\":null,\"last_change_at\":null,\"editor_first\":null,\"editor_last\":null,\"progress_count\":0}],\"total\":1,\"page\":1,\"limit\":5,\"pages\":1}', '2025-08-28 13:09:04'),
('teacher_videos_3_ec7bd32d6427dc51c8f394b363149534', '{\"videos\":[{\"id\":13,\"user_id\":null,\"uploaded_by\":3,\"subject\":\"math\",\"grade\":\"8\",\"unit_id\":26,\"unit\":\"Sayılar ve Nicelikler (1)\",\"topic\":\"f\",\"title\":\"gfew\",\"description\":\"gf\",\"file_path\":\"video_68ac1371eaadf.mp4\",\"youtube_url\":\"https:\\/\\/www.youtube.com\\/embed\\/x5gre8Pf-ZY\",\"filename\":null,\"status\":\"approved\",\"featured\":0,\"is_featured\":0,\"approved_by\":1,\"rejection_reason\":null,\"view_count\":null,\"created_at\":\"2025-08-25 10:40:33\",\"updated_at\":\"2025-08-25 10:41:55\",\"completion_rate\":null,\"like_count\":null,\"last_change_summary\":null,\"last_change_at\":null,\"editor_first\":null,\"editor_last\":null,\"progress_count\":0}],\"total\":1,\"page\":1,\"limit\":20,\"pages\":1}', '2025-08-25 10:47:07'),
('units_grade_5', '[{\"id\":1,\"unit_order\":1,\"unit_name\":\"Say\\u0131lar ve Nicelikler (1)\",\"description\":null,\"video_count\":0},{\"id\":2,\"unit_order\":2,\"unit_name\":\"Say\\u0131lar ve Nicelikler (2)\",\"description\":null,\"video_count\":0},{\"id\":3,\"unit_order\":3,\"unit_name\":\"\\u0130\\u015flemlerle Cebirsel D\\u00fc\\u015f\\u00fcnme\",\"description\":null,\"video_count\":0},{\"id\":4,\"unit_order\":4,\"unit_name\":\"Geometrik \\u015eekiller\",\"description\":null,\"video_count\":0},{\"id\":5,\"unit_order\":5,\"unit_name\":\"Geometrik Nicelikler\",\"description\":null,\"video_count\":0},{\"id\":6,\"unit_order\":6,\"unit_name\":\"\\u0130statistiksel Ara\\u015ft\\u0131rma S\\u00fcreci ve Veriden Olas\\u0131l\\u0131\\u011fa\",\"description\":null,\"video_count\":0}]', '2025-08-25 16:51:01'),
('units_grade_6', '[{\"id\":7,\"unit_order\":1,\"unit_name\":\"Say\\u0131lar ve Nicelikler\",\"description\":null,\"video_count\":0},{\"id\":8,\"unit_order\":2,\"unit_name\":\"\\u0130\\u015flemlerle Cebirsel D\\u00fc\\u015f\\u00fcnme ve De\\u011fi\\u015fimler\",\"description\":null,\"video_count\":0},{\"id\":9,\"unit_order\":3,\"unit_name\":\"Geometrik \\u015eekiller\",\"description\":null,\"video_count\":0},{\"id\":10,\"unit_order\":4,\"unit_name\":\"Geometrik Nicelikler\",\"description\":null,\"video_count\":0},{\"id\":11,\"unit_order\":5,\"unit_name\":\"\\u0130statistiksel Ara\\u015ft\\u0131rma S\\u00fcreci\",\"description\":null,\"video_count\":0},{\"id\":12,\"unit_order\":6,\"unit_name\":\"Veriden Olas\\u0131l\\u0131\\u011fa\",\"description\":null,\"video_count\":0}]', '2025-08-28 13:04:57'),
('units_grade_8', '[{\"id\":20,\"unit_order\":1,\"unit_name\":\"Say\\u0131lar ve Nicelikler\",\"description\":null,\"video_count\":0},{\"id\":21,\"unit_order\":2,\"unit_name\":\"Cebirsel D\\u00fc\\u015f\\u00fcnme ve De\\u011fi\\u015fimler\",\"description\":null,\"video_count\":0},{\"id\":22,\"unit_order\":3,\"unit_name\":\"Geometrik \\u015eekiller\",\"description\":null,\"video_count\":0},{\"id\":23,\"unit_order\":4,\"unit_name\":\"Geometrik Nicelikler\",\"description\":null,\"video_count\":0},{\"id\":24,\"unit_order\":5,\"unit_name\":\"D\\u00f6n\\u00fc\\u015f\\u00fcm\",\"description\":null,\"video_count\":0},{\"id\":25,\"unit_order\":6,\"unit_name\":\"\\u0130statistiksel Ara\\u015ft\\u0131rma S\\u00fcreci\",\"description\":null,\"video_count\":0},{\"id\":26,\"unit_order\":7,\"unit_name\":\"Veriden Olas\\u0131l\\u0131\\u011fa\",\"description\":null,\"video_count\":1}]', '2025-08-25 17:37:24'),
('user_progress_1_10', 'null', '2025-08-24 14:57:46'),
('user_progress_1_6', 'null', '2025-08-21 14:25:01'),
('user_progress_1_7', 'null', '2025-08-21 14:24:54'),
('user_progress_1_8', 'null', '2025-08-21 14:54:02'),
('user_progress_3_10', 'null', '2025-08-24 15:32:43'),
('user_progress_3_11', 'null', '2025-08-25 08:34:46'),
('user_progress_3_12', 'null', '2025-08-25 09:16:27'),
('user_progress_3_13', 'null', '2025-08-25 09:47:18'),
('user_progress_4_10', 'null', '2025-08-21 15:23:56'),
('user_progress_4_13', 'null', '2025-08-25 16:42:24'),
('videos_grade_5_55a3aaeecb24fcc40c56df453c91c4b1', '[]', '2025-08-25 16:06:01'),
('videos_grade_6_e9bdae76660778049d9a7c5b98f9787b', '[]', '2025-08-28 12:19:57'),
('videos_grade_8_143c3e6cd344c76e25e51c2d05d06d19', '[{\"id\":13,\"user_id\":null,\"title\":\"gfew\",\"description\":\"gf\",\"youtube_url\":\"https:\\/\\/www.youtube.com\\/embed\\/x5gre8Pf-ZY\",\"filename\":null,\"status\":\"approved\",\"unit\":\"Say\\u0131lar ve Nicelikler (1)\",\"unit_id\":26,\"topic\":\"f\",\"created_at\":\"2025-08-25 10:40:33\",\"featured\":0,\"unit_name\":\"Veriden Olas\\u0131l\\u0131\\u011fa\",\"unit_order\":7,\"total_views\":null,\"completion_rate\":\"0.00\",\"like_count\":null,\"view_count\":\"0\",\"is_bookmarked\":false,\"user_progress\":null,\"quiz_available\":false}]', '2025-08-25 16:06:12'),
('videos_grade_8_55a3aaeecb24fcc40c56df453c91c4b1', '[{\"id\":13,\"user_id\":null,\"title\":\"gfew\",\"description\":\"gf\",\"youtube_url\":\"https:\\/\\/www.youtube.com\\/embed\\/x5gre8Pf-ZY\",\"filename\":null,\"status\":\"approved\",\"unit\":\"Say\\u0131lar ve Nicelikler (1)\",\"unit_id\":26,\"topic\":\"f\",\"created_at\":\"2025-08-25 10:40:33\",\"featured\":0,\"unit_name\":\"Veriden Olas\\u0131l\\u0131\\u011fa\",\"unit_order\":7,\"total_views\":null,\"completion_rate\":\"0.00\",\"like_count\":null,\"view_count\":\"0\",\"is_bookmarked\":false,\"user_progress\":null,\"quiz_available\":false}]', '2025-08-25 16:52:24'),
('videos_grade_8_e9bdae76660778049d9a7c5b98f9787b', '[{\"id\":13,\"user_id\":null,\"title\":\"gfew\",\"description\":\"gf\",\"youtube_url\":\"https:\\/\\/www.youtube.com\\/embed\\/x5gre8Pf-ZY\",\"filename\":null,\"status\":\"approved\",\"unit\":\"Say\\u0131lar ve Nicelikler (1)\",\"unit_id\":26,\"topic\":\"f\",\"created_at\":\"2025-08-25 10:40:33\",\"featured\":0,\"unit_name\":\"Veriden Olas\\u0131l\\u0131\\u011fa\",\"unit_order\":7,\"total_views\":null,\"completion_rate\":\"0.00\",\"like_count\":null,\"view_count\":\"0\",\"is_bookmarked\":false,\"user_progress\":null,\"quiz_available\":false}]', '2025-08-25 09:57:18');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `teachers`
--

CREATE TABLE `teachers` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `school` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `teachers`
--

INSERT INTO `teachers` (`user_id`, `school`, `department`, `experience_years`) VALUES
(3, 'imö', 'matematik', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `units`
--

CREATE TABLE `units` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(50) NOT NULL,
  `grade` varchar(10) NOT NULL,
  `unit_order` int(11) NOT NULL,
  `unit_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `units`
--

INSERT INTO `units` (`id`, `subject`, `grade`, `unit_order`, `unit_name`, `description`, `is_active`) VALUES
(1, 'math', '5', 1, 'Sayılar ve Nicelikler (1)', NULL, 1),
(2, 'math', '5', 2, 'Sayılar ve Nicelikler (2)', NULL, 1),
(3, 'math', '5', 3, 'İşlemlerle Cebirsel Düşünme', NULL, 1),
(4, 'math', '5', 4, 'Geometrik Şekiller', NULL, 1),
(5, 'math', '5', 5, 'Geometrik Nicelikler', NULL, 1),
(6, 'math', '5', 6, 'İstatistiksel Araştırma Süreci ve Veriden Olasılığa', NULL, 1),
(7, 'math', '6', 1, 'Sayılar ve Nicelikler', NULL, 1),
(8, 'math', '6', 2, 'İşlemlerle Cebirsel Düşünme ve Değişimler', NULL, 1),
(9, 'math', '6', 3, 'Geometrik Şekiller', NULL, 1),
(10, 'math', '6', 4, 'Geometrik Nicelikler', NULL, 1),
(11, 'math', '6', 5, 'İstatistiksel Araştırma Süreci', NULL, 1),
(12, 'math', '6', 6, 'Veriden Olasılığa', NULL, 1),
(13, 'math', '7', 1, 'Sayılar ve Nicelikler (2)', NULL, 1),
(14, 'math', '7', 2, 'İşlemlerle Cebirsel Düşünme ve Değişimler', NULL, 1),
(15, 'math', '7', 3, 'Dönüşüm', NULL, 1),
(16, 'math', '7', 4, 'Geometrik Nicelikler', NULL, 1),
(17, 'math', '7', 5, 'Geometrik Şekiller', NULL, 1),
(18, 'math', '7', 6, 'İstatistiksel Araştırma Süreci', NULL, 1),
(19, 'math', '7', 7, 'Veriden Olasılığa', NULL, 1),
(20, 'math', '8', 1, 'Sayılar ve Nicelikler', NULL, 1),
(21, 'math', '8', 2, 'Cebirsel Düşünme ve Değişimler', NULL, 1),
(22, 'math', '8', 3, 'Geometrik Şekiller', NULL, 1),
(23, 'math', '8', 4, 'Geometrik Nicelikler', NULL, 1),
(24, 'math', '8', 5, 'Dönüşüm', NULL, 1),
(25, 'math', '8', 6, 'İstatistiksel Araştırma Süreci', NULL, 1),
(26, 'math', '8', 7, 'Veriden Olasılığa', NULL, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `status` enum('pending','approved','rejected','banned') NOT NULL DEFAULT 'pending',
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `email`, `phone`, `password`, `role`, `status`, `first_name`, `last_name`, `full_name`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin@example.com', NULL, '$2y$10$e11T5iFzIqYClpqeJwSxLuTy0o6uCTPcYs1X9TAETxhcxgqq0Pkzm', 'admin', 'approved', 'Admin', 'User', 'Admin User', 1, '2025-08-25 17:35:01', '2025-08-21 13:26:11', '2025-08-25 17:35:01'),
(3, 'teacher@example.com', '555555555555', '$2y$10$BbswuSEXdui8YiR0LWA.VuPZQnDkNfSQdP9rp9L71VQdS.JiRSOHK', 'teacher', 'approved', 'öğretmen', 'ben', NULL, 1, '2025-08-28 13:04:04', '2025-08-21 13:52:57', '2025-08-28 13:04:04'),
(4, 'student@example.com', NULL, '$2y$10$dn5lS34PryWZoIsGCJ2AyuOXBxh5fGk65NYujcI4bM/J7zkPcCLbC', 'student', 'approved', 'öğrenci', 'acaa', NULL, 1, '2025-08-25 17:37:12', '2025-08-21 14:21:57', '2025-08-25 17:37:12'),
(5, 'g@xn--gma-uza', '', '$2y$10$dn5lS34PryWZoIsGCJ2AyuOXBxh5fGk65NYujcI4bM/J7zkPcCLbC', 'student', 'pending', 'gg', '', NULL, 1, NULL, '2025-08-24 15:33:32', '2025-08-25 17:33:59');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `videos`
--

CREATE TABLE `videos` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `subject` varchar(50) NOT NULL DEFAULT 'math',
  `grade` varchar(10) DEFAULT NULL,
  `unit_id` int(10) UNSIGNED DEFAULT NULL,
  `unit` varchar(200) DEFAULT NULL,
  `topic` varchar(200) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','draft') NOT NULL DEFAULT 'pending',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `videos`
--

INSERT INTO `videos` (`id`, `user_id`, `uploaded_by`, `subject`, `grade`, `unit_id`, `unit`, `topic`, `title`, `description`, `file_path`, `youtube_url`, `filename`, `status`, `featured`, `is_featured`, `approved_by`, `rejection_reason`, `view_count`, `created_at`, `updated_at`) VALUES
(13, NULL, 3, 'math', '8', 26, 'Sayılar ve Nicelikler (1)', 'f', 'gfew', 'gf', 'video_68ac1371eaadf.mp4', 'https://www.youtube.com/embed/x5gre8Pf-ZY', NULL, 'approved', 0, 0, 1, NULL, 0, '2025-08-25 10:40:33', '2025-08-25 10:41:55');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `video_bookmarks`
--

CREATE TABLE `video_bookmarks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `video_edit_requests`
--

CREATE TABLE `video_edit_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_type` enum('edit','delete') NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `requester_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `review_note` text DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `proposed_subject` varchar(50) DEFAULT NULL,
  `proposed_grade` varchar(10) DEFAULT NULL,
  `proposed_unit_id` int(10) UNSIGNED DEFAULT NULL,
  `proposed_title` varchar(255) DEFAULT NULL,
  `proposed_description` text DEFAULT NULL,
  `proposed_topic` varchar(200) DEFAULT NULL,
  `proposed_youtube_url` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `video_edit_requests`
--

INSERT INTO `video_edit_requests` (`id`, `request_type`, `video_id`, `requester_id`, `status`, `review_note`, `decided_at`, `proposed_subject`, `proposed_grade`, `proposed_unit_id`, `proposed_title`, `proposed_description`, `proposed_topic`, `proposed_youtube_url`, `created_at`) VALUES
(9, 'edit', 13, 3, 'approved', 'tamam', '2025-08-25 10:41:55', 'math', '8', 26, 'gfew', 'gf', 'f', 'https://www.youtube.com/embed/x5gre8Pf-ZY', '2025-08-25 10:41:29');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `video_progress`
--

CREATE TABLE `video_progress` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `watch_duration` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_duration` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `watch_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_watched_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `video_ratings`
--

CREATE TABLE `video_ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `video_statistics`
--

CREATE TABLE `video_statistics` (
  `video_id` int(10) UNSIGNED NOT NULL,
  `total_views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `completion_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `like_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_audit_admin_time` (`admin_id`,`created_at`),
  ADD KEY `ix_audit_action` (`action`);

--
-- Tablo için indeksler `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversations_type` (`type`),
  ADD KEY `idx_conversations_video` (`video_id`),
  ADD KEY `idx_conversations_quiz` (`quiz_id`),
  ADD KEY `idx_conversations_updated` (`updated_at`);

--
-- Tablo için indeksler `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_conv_user` (`conversation_id`,`user_id`),
  ADD KEY `idx_participants_user` (`user_id`);

--
-- Tablo için indeksler `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_messages_conv_created` (`conversation_id`,`created_at`),
  ADD KEY `idx_messages_sender_created` (`sender_id`,`created_at`),
  ADD KEY `idx_messages_is_read` (`is_read`),
  ADD KEY `fk_messages_receiver` (`receiver_id`);

--
-- Tablo için indeksler `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_quizzes_video_active` (`video_id`,`is_active`),
  ADD KEY `ix_quizzes_active` (`is_active`),
  ADD KEY `idx_quizzes_status_video` (`status`,`video_id`),
  ADD KEY `idx_quizzes_owner` (`owner_teacher_id`);

--
-- Tablo için indeksler `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempts_quiz_user` (`quiz_id`,`student_id`),
  ADD KEY `idx_attempts_status` (`status`);

--
-- Tablo için indeksler `quiz_attempt_answers`
--
ALTER TABLE `quiz_attempt_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `idx_answers_attempt` (`attempt_id`),
  ADD KEY `idx_answers_question` (`question_id`),
  ADD KEY `fk_answers_option` (`option_id`);

--
-- Tablo için indeksler `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_options_question` (`question_id`,`order_no`),
  ADD KEY `idx_options_correct` (`question_id`,`is_correct`);

--
-- Tablo için indeksler `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_questions_quiz` (`quiz_id`),
  ADD KEY `idx_questions_quiz` (`quiz_id`),
  ADD KEY `idx_questions_quiz_order` (`quiz_id`,`order_no`);

--
-- Tablo için indeksler `quiz_reviews`
--
ALTER TABLE `quiz_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reviews_quiz` (`quiz_id`,`decision`);

--
-- Tablo için indeksler `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`user_id`);

--
-- Tablo için indeksler `system_cache`
--
ALTER TABLE `system_cache`
  ADD PRIMARY KEY (`cache_key`),
  ADD KEY `ix_cache_expiry` (`expires_at`);

--
-- Tablo için indeksler `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`user_id`);

--
-- Tablo için indeksler `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_units_subject_grade_order` (`subject`,`grade`,`unit_order`),
  ADD KEY `ix_units_subject_grade_active` (`subject`,`grade`,`is_active`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_users_email` (`email`),
  ADD KEY `ix_users_role_status` (`role`,`status`),
  ADD KEY `ix_users_last_login` (`last_login`),
  ADD KEY `ix_users_created_at` (`created_at`),
  ADD KEY `idx_users_phone` (`phone`);

--
-- Tablo için indeksler `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_videos_uploaded_by` (`uploaded_by`),
  ADD KEY `fk_videos_approved_by` (`approved_by`),
  ADD KEY `ix_videos_status` (`status`),
  ADD KEY `ix_videos_grade_subject` (`grade`,`subject`),
  ADD KEY `ix_videos_unit_id` (`unit_id`),
  ADD KEY `ix_videos_user` (`user_id`),
  ADD KEY `ix_videos_created` (`created_at`),
  ADD KEY `ix_videos_featured` (`is_featured`,`featured`);

--
-- Tablo için indeksler `video_bookmarks`
--
ALTER TABLE `video_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_bookmark_user_video` (`user_id`,`video_id`),
  ADD KEY `ix_bookmarks_video` (`video_id`);

--
-- Tablo için indeksler `video_edit_requests`
--
ALTER TABLE `video_edit_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ver_unit` (`proposed_unit_id`),
  ADD KEY `ix_ver_status` (`status`),
  ADD KEY `ix_ver_video` (`video_id`),
  ADD KEY `ix_ver_requester` (`requester_id`);

--
-- Tablo için indeksler `video_progress`
--
ALTER TABLE `video_progress`
  ADD PRIMARY KEY (`user_id`,`video_id`),
  ADD KEY `fk_progress_video` (`video_id`);

--
-- Tablo için indeksler `video_ratings`
--
ALTER TABLE `video_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ratings_user` (`user_id`),
  ADD KEY `ix_ratings_video` (`video_id`),
  ADD KEY `ix_ratings_video_user` (`video_id`,`user_id`);

--
-- Tablo için indeksler `video_statistics`
--
ALTER TABLE `video_statistics`
  ADD PRIMARY KEY (`video_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- Tablo için AUTO_INCREMENT değeri `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Tablo için AUTO_INCREMENT değeri `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- Tablo için AUTO_INCREMENT değeri `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `quiz_attempt_answers`
--
ALTER TABLE `quiz_attempt_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `quiz_options`
--
ALTER TABLE `quiz_options`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `quiz_reviews`
--
ALTER TABLE `quiz_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `units`
--
ALTER TABLE `units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `video_bookmarks`
--
ALTER TABLE `video_bookmarks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `video_edit_requests`
--
ALTER TABLE `video_edit_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `video_ratings`
--
ALTER TABLE `video_ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conversations_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_conversations_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `fk_participants_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_participants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quiz_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `fk_attempts_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `quiz_attempt_answers`
--
ALTER TABLE `quiz_attempt_answers`
  ADD CONSTRAINT `fk_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answers_option` FOREIGN KEY (`option_id`) REFERENCES `quiz_options` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD CONSTRAINT `fk_options_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `fk_quiz_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `quiz_reviews`
--
ALTER TABLE `quiz_reviews`
  ADD CONSTRAINT `fk_reviews_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `fk_videos_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_videos_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_videos_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_videos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `video_bookmarks`
--
ALTER TABLE `video_bookmarks`
  ADD CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookmarks_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `video_edit_requests`
--
ALTER TABLE `video_edit_requests`
  ADD CONSTRAINT `fk_ver_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ver_unit` FOREIGN KEY (`proposed_unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ver_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `video_progress`
--
ALTER TABLE `video_progress`
  ADD CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progress_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `video_ratings`
--
ALTER TABLE `video_ratings`
  ADD CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ratings_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `video_statistics`
--
ALTER TABLE `video_statistics`
  ADD CONSTRAINT `fk_video_stats_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
