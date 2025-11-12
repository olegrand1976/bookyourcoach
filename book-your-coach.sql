-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : mysql-dae24fb8-odf582313.database.cloud.ovh.net:20184
-- Généré le : lun. 03 nov. 2025 à 20:56
-- Version du serveur : 8.0.35
-- Version de PHP : 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `book-your-coach`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_types`
--

CREATE TABLE `activity_types` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#6B7280',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `activity_types`
--

INSERT INTO `activity_types` (`id`, `name`, `slug`, `description`, `icon`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Équitation', 'equestrian', 'Hippothérapie', '🐎', '#8B4513', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40'),
(2, 'Natation', 'swimming', 'Centre de natation avec bassins et activités aquatiques', '🏊‍♂️', '#0066CC', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40');

-- --------------------------------------------------------

--
-- Structure de la table `app_settings`
--

CREATE TABLE `app_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `app_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activibe',
  `primary_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#2563eb',
  `secondary_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#1e40af',
  `accent_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3b82f6',
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_description` text COLLATE utf8mb4_unicode_ci,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_links` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `app_settings`
--

INSERT INTO `app_settings` (`id`, `app_name`, `primary_color`, `secondary_color`, `accent_color`, `logo_url`, `logo_path`, `app_description`, `contact_email`, `contact_phone`, `social_links`, `is_active`, `created_at`, `updated_at`, `key`, `value`, `type`, `group`) VALUES
(1, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, 'Plateforme de réservation de cours avec des coaches professionnels', 'contact@activibe.com', '+32 2 123 45 67', '{\"facebook\": \"https://facebook.com/activibe\", \"linkedin\": \"https://linkedin.com/company/activibe\", \"instagram\": \"https://instagram.com/activibe\"}', 1, '2025-09-14 16:48:22', '2025-09-14 16:48:22', NULL, NULL, 'string', NULL),
(2, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-14 16:48:22', '2025-09-14 16:48:22', 'contract_parameters', '{\"volunteer\":{\"name\":\"B\\u00e9n\\u00e9vole\",\"annual_ceiling\":3900,\"daily_ceiling\":42.31,\"mileage_allowance\":0.4,\"max_annual_mileage\":2000}}', 'json', NULL),
(3, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 11:38:15', '2025-09-17 11:38:15', 'general.platform_name', 'Activibe', 'string', 'general'),
(4, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 11:38:15', '2025-09-17 11:38:15', 'general.contact_email', 'info@activibe.be', 'string', 'general'),
(5, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 11:38:15', '2025-09-17 11:38:15', 'general.contact_phone', '0478031906', 'string', 'general'),
(6, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 11:38:15', '2025-09-17 11:38:15', 'general.company_address', 'Rue de la Résistance, 92 /A\n7131 Waudrez', 'string', 'general'),
(7, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 11:38:15', '2025-09-17 11:38:15', 'general.timezone', 'Europe/Brussels', 'string', 'general'),
(8, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 11:38:15', '2025-09-17 11:38:15', 'general.logo_url', '/logo-activibe.svg', 'string', 'general'),
(9, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 11:38:15', '2025-09-17 11:38:15', 'general.favicon_url', '/favicon.ico', 'string', 'general'),
(10, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 16:04:40', '2025-09-17 16:04:40', 'general.company_street', 'Rue test', 'string', 'general'),
(11, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 16:04:40', '2025-09-17 16:04:40', 'general.company_street_number', '1', 'string', 'general'),
(12, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 16:04:40', '2025-09-17 16:04:40', 'general.company_postal_code', '1000', 'string', 'general'),
(13, 'activibe', '#2563eb', '#1e40af', '#3b82f6', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-17 16:04:40', '2025-09-17 16:04:40', 'general.company_city', 'Bruxelles', 'string', 'general');

-- --------------------------------------------------------

--
-- Structure de la table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint UNSIGNED DEFAULT NULL,
  `data` json DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `model_type`, `model_id`, `data`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-14T18:35:43.623008Z\\\"}\"', '87.64.157.116', 'curl/7.81.0', '2025-09-14 18:35:43', '2025-09-14 18:35:43'),
(2, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-14T19:23:50.807518Z\\\"}\"', '87.64.157.116', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-14 19:23:50', '2025-09-14 19:23:50'),
(3, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-14T20:14:30.298893Z\\\"}\"', '87.64.157.116', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-14 20:14:30', '2025-09-14 20:14:30'),
(4, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T06:59:40.086972Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 06:59:40', '2025-09-15 06:59:40'),
(5, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T07:22:56.835020Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 07:22:56', '2025-09-15 07:22:56'),
(6, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T07:30:52.521030Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 07:30:52', '2025-09-15 07:30:52'),
(7, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T07:32:59.460116Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 07:32:59', '2025-09-15 07:32:59'),
(8, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T07:35:30.025751Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 07:35:30', '2025-09-15 07:35:30'),
(9, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T07:48:43.066210Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 07:48:43', '2025-09-15 07:48:43'),
(10, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T07:56:59.924368Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 07:56:59', '2025-09-15 07:56:59'),
(11, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T08:15:12.215022Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 08:15:12', '2025-09-15 08:15:12'),
(12, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T08:19:49.598908Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 08:19:49', '2025-09-15 08:19:49'),
(13, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T08:23:48.368443Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 08:23:48', '2025-09-15 08:23:48'),
(14, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T08:56:26.417830Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 08:56:26', '2025-09-15 08:56:26'),
(15, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:03:17.543009Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:03:17', '2025-09-15 09:03:17'),
(16, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:09:05.060825Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:09:05', '2025-09-15 09:09:05'),
(17, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:15:00.978361Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:15:00', '2025-09-15 09:15:00'),
(18, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:33:30.897360Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:33:30', '2025-09-15 09:33:30'),
(19, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:33:58.167824Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:33:58', '2025-09-15 09:33:58'),
(20, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:52:31.812667Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:52:31', '2025-09-15 09:52:31'),
(21, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:53:01.748212Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:53:01', '2025-09-15 09:53:01'),
(22, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:55:43.529493Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:55:43', '2025-09-15 09:55:43'),
(23, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:57:38.952661Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 09:57:38', '2025-09-15 09:57:38'),
(24, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T09:58:40.473066Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 09:58:40', '2025-09-15 09:58:40'),
(25, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T10:00:01.734153Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 10:00:01', '2025-09-15 10:00:01'),
(26, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T10:13:04.152499Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 10:13:04', '2025-09-15 10:13:04'),
(27, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T10:26:03.719018Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 10:26:03', '2025-09-15 10:26:03'),
(28, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T10:52:41.640020Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 10:52:41', '2025-09-15 10:52:41'),
(29, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T13:02:18.644575Z\\\"}\"', '109.88.72.108', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 13:02:18', '2025-09-15 13:02:18'),
(30, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T13:45:16.470071Z\\\"}\"', '79.132.229.38', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 13:45:16', '2025-09-15 13:45:16'),
(31, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T13:48:41.415268Z\\\"}\"', '79.132.229.38', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 13:48:41', '2025-09-15 13:48:41'),
(32, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T13:55:17.037657Z\\\"}\"', '79.132.229.38', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 13:55:17', '2025-09-15 13:55:17'),
(33, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T15:14:19.840432Z\\\"}\"', '79.132.229.38', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 15:14:19', '2025-09-15 15:14:19'),
(34, 1, 'login_success', NULL, NULL, '\"{\\\"email\\\":\\\"admin@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T15:22:40.930608Z\\\"}\"', '79.132.229.38', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', '2025-09-15 15:22:40', '2025-09-15 15:22:40'),
(35, NULL, 'login_failed', NULL, NULL, '\"{\\\"email\\\":\\\"admin.secours@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T15:30:08.053722Z\\\"}\"', '127.0.0.1', 'curl/8.14.1', '2025-09-15 15:30:08', '2025-09-15 15:30:08'),
(36, NULL, 'login_failed', NULL, NULL, '\"{\\\"email\\\":\\\"admin.secours@activibe.com\\\",\\\"timestamp\\\":\\\"2025-09-15T15:30:11.631712Z\\\"}\"', '127.0.0.1', 'curl/8.14.1', '2025-09-15 15:30:11', '2025-09-15 15:30:11'),
(37, 1, 'user_updated', 'User', 76, '{\"new\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-12-02T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T17:43:30.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}, \"old\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-12-03T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T17:24:11.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}}', NULL, NULL, '2025-10-26 18:43:30', '2025-10-26 18:43:30'),
(38, 1, 'user_updated', 'User', 76, '{\"new\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-12-01T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:03:26.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}, \"old\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-12-02T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T17:43:30.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}}', NULL, NULL, '2025-10-26 19:03:26', '2025-10-26 19:03:26'),
(39, 1, 'user_updated', 'User', 76, '{\"new\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-11-30T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:05:44.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}, \"old\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-12-01T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:03:26.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}}', NULL, NULL, '2025-10-26 19:05:44', '2025-10-26 19:05:44'),
(40, 1, 'user_updated', 'User', 76, '{\"new\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-11-29T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:06:57.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}, \"old\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-11-30T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:05:44.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}}', NULL, NULL, '2025-10-26 19:06:57', '2025-10-26 19:06:57'),
(41, 1, 'user_updated', 'User', 76, '{\"new\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"student\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-11-28T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:08:49.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}, \"old\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-11-29T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:06:57.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}}', NULL, NULL, '2025-10-26 19:08:49', '2025-10-26 19:08:49'),
(42, 1, 'user_updated', 'User', 76, '{\"new\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"club\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-11-27T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:08:54.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}, \"old\": {\"id\": 76, \"city\": \"Waudrez\", \"name\": \"Barbara MURGO\", \"role\": \"student\", \"email\": \"b.murgo1976@gmail.com\", \"phone\": \"0478023377\", \"status\": \"active\", \"street\": \"Rue de la Résistance,\", \"country\": \"Belgium\", \"qr_code\": null, \"is_active\": true, \"last_name\": \"MURGO\", \"birth_date\": \"1976-11-28T23:00:00.000000Z\", \"created_at\": \"2025-10-26T17:24:11.000000Z\", \"first_name\": \"Barbara\", \"updated_at\": \"2025-10-26T18:08:49.000000Z\", \"postal_code\": \"7131\", \"street_number\": \"92 / A\", \"email_verified_at\": null, \"qr_code_generated_at\": null}}', NULL, NULL, '2025-10-26 19:08:54', '2025-10-26 19:08:54'),
(43, 1, 'club_created_for_user', 'Club', 11, '{\"user_id\": 76, \"user_name\": \"Barbara MURGO\"}', NULL, NULL, '2025-10-26 19:53:52', '2025-10-26 19:53:52');

-- --------------------------------------------------------

--
-- Structure de la table `availabilities`
--

CREATE TABLE `availabilities` (
  `id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `lesson_id` bigint UNSIGNED NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `booked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('activibe-cache-predictive_analysis_11_2025-11-03-21', 'N;', 1762206873);

-- --------------------------------------------------------

--
-- Structure de la table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cash_registers`
--

CREATE TABLE `cash_registers` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `current_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `last_closing_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `certifications`
--

CREATE TABLE `certifications` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issuing_authority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('official','federation','continuing_education','specialized') COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_type_id` bigint UNSIGNED DEFAULT NULL,
  `validity_years` int DEFAULT NULL,
  `requirements` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clubs`
--

CREATE TABLE `clubs` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legal_representative_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legal_representative_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_rc_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_rc_policy_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_additional_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_additional_policy_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_additional_details` text COLLATE utf8mb4_unicode_ci,
  `expense_reimbursement_type` enum('forfait','reel','aucun') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aucun',
  `expense_reimbursement_details` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_box` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code_generated_at` timestamp NULL DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'France',
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facilities` json DEFAULT NULL,
  `disciplines` json DEFAULT NULL,
  `activity_types` json DEFAULT NULL,
  `discipline_settings` json DEFAULT NULL COMMENT 'Configuration des paramètres par discipline (durée, prix, participants)',
  `schedule_config` json DEFAULT NULL,
  `max_students` int DEFAULT NULL,
  `subscription_price` decimal(8,2) DEFAULT NULL,
  `default_subscription_total_lessons` int DEFAULT '10',
  `default_subscription_free_lessons` int DEFAULT '1',
  `default_subscription_price` decimal(8,2) DEFAULT '180.00',
  `default_subscription_validity_value` int DEFAULT '12',
  `default_subscription_validity_unit` enum('weeks','months') COLLATE utf8mb4_unicode_ci DEFAULT 'weeks',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `activity_type_id` bigint UNSIGNED DEFAULT NULL,
  `seasonal_variation` decimal(5,2) NOT NULL DEFAULT '0.00',
  `weather_dependency` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `company_number`, `legal_representative_name`, `legal_representative_role`, `insurance_rc_company`, `insurance_rc_policy_number`, `insurance_additional_company`, `insurance_additional_policy_number`, `insurance_additional_details`, `expense_reimbursement_type`, `expense_reimbursement_details`, `description`, `email`, `phone`, `street`, `street_number`, `street_box`, `qr_code`, `qr_code_generated_at`, `address`, `city`, `postal_code`, `country`, `website`, `facilities`, `disciplines`, `activity_types`, `discipline_settings`, `schedule_config`, `max_students`, `subscription_price`, `default_subscription_total_lessons`, `default_subscription_free_lessons`, `default_subscription_price`, `default_subscription_validity_value`, `default_subscription_validity_unit`, `is_active`, `terms_and_conditions`, `created_at`, `updated_at`, `activity_type_id`, `seasonal_variation`, `weather_dependency`) VALUES
(11, 'ACTI\'VIBE', '1029759225', 'Barbara MURGO', 'Administratrice', 'P&V', '1', NULL, NULL, '-', 'forfait', 'Défraiement forfaite sur base du diplôme et/ou brevet obtenu par le volontaire.', 'Acti\'Vibe, centre multi-sports pour enfants et adultes.\nUn accès à tous au sport!', 'b.murgo1976@gmail.com', '0478023377', 'Rue de la Résistance,', '92 / A', NULL, NULL, NULL, 'Rue de la Résistance, 92 / A', 'Waudrez', '7131', 'Belgium', 'https://www.activibe.be', NULL, '[2, 11]', '[2]', '{\"2\": {\"notes\": null, \"price\": 18, \"duration\": 20, \"max_participants\": 1, \"min_participants\": 1}, \"11\": {\"notes\": null, \"price\": 18, \"duration\": 20, \"max_participants\": 1, \"min_participants\": 1}}', NULL, NULL, NULL, 10, 1, 180.00, 15, 'weeks', 1, NULL, '2025-10-26 19:53:52', '2025-11-03 21:02:27', NULL, 0.00, 0);

-- --------------------------------------------------------

--
-- Structure de la table `club_activity_types`
--

CREATE TABLE `club_activity_types` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `activity_type_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `club_custom_specialties`
--

CREATE TABLE `club_custom_specialties` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `activity_type_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int NOT NULL DEFAULT '60',
  `base_price` decimal(8,2) NOT NULL DEFAULT '0.00',
  `skill_levels` json DEFAULT NULL,
  `min_participants` int NOT NULL DEFAULT '1',
  `max_participants` int NOT NULL DEFAULT '8',
  `equipment_required` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `club_disciplines`
--

CREATE TABLE `club_disciplines` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `discipline_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `club_managers`
--

CREATE TABLE `club_managers` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `role` enum('owner','manager','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manager',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `club_open_slots`
--

CREATE TABLE `club_open_slots` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `day_of_week` tinyint UNSIGNED NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `discipline_id` bigint UNSIGNED DEFAULT NULL,
  `max_capacity` smallint UNSIGNED NOT NULL DEFAULT '1',
  `max_slots` smallint UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Nombre de créneaux parallèles possibles (ex: 5 couloirs = 5 cours simultanés)',
  `duration` smallint UNSIGNED NOT NULL DEFAULT '60',
  `price` decimal(8,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `club_open_slots`
--

INSERT INTO `club_open_slots` (`id`, `club_id`, `day_of_week`, `start_time`, `end_time`, `discipline_id`, `max_capacity`, `max_slots`, `duration`, `price`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 11, 3, '14:00:00', '21:00:00', 11, 1, 4, 20, 18.00, 1, '2025-11-03 16:02:00', '2025-11-03 21:01:12'),
(2, 11, 6, '09:00:00', '18:00:00', 11, 1, 4, 20, 18.00, 1, '2025-11-03 16:02:19', '2025-11-03 21:01:18');

-- --------------------------------------------------------

--
-- Structure de la table `club_open_slot_course_types`
--

CREATE TABLE `club_open_slot_course_types` (
  `id` bigint UNSIGNED NOT NULL,
  `club_open_slot_id` bigint UNSIGNED NOT NULL,
  `course_type_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `club_open_slot_course_types`
--

INSERT INTO `club_open_slot_course_types` (`id`, `club_open_slot_id`, `course_type_id`, `created_at`, `updated_at`) VALUES
(1, 1, 17, NULL, NULL),
(2, 2, 17, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `club_settings`
--

CREATE TABLE `club_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `feature_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feature_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feature_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `configuration` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `club_students`
--

CREATE TABLE `club_students` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `goals` text COLLATE utf8mb4_unicode_ci,
  `medical_info` text COLLATE utf8mb4_unicode_ci,
  `preferred_disciplines` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `club_students`
--

INSERT INTO `club_students` (`id`, `club_id`, `student_id`, `level`, `goals`, `medical_info`, `preferred_disciplines`, `is_active`, `joined_at`, `created_at`, `updated_at`) VALUES
(43, 11, 53, NULL, NULL, NULL, NULL, 0, '2025-11-02 22:14:47', '2025-11-02 22:14:47', '2025-11-02 23:13:54'),
(44, 11, 54, NULL, NULL, NULL, NULL, 1, '2025-11-02 23:14:32', '2025-11-02 23:14:32', '2025-11-02 23:14:32'),
(45, 11, 55, NULL, NULL, NULL, NULL, 1, '2025-11-03 14:53:48', '2025-11-03 14:53:48', '2025-11-03 14:53:48'),
(46, 11, 56, NULL, NULL, NULL, NULL, 1, '2025-11-03 14:56:06', '2025-11-03 14:56:06', '2025-11-03 14:56:06'),
(47, 11, 57, NULL, NULL, NULL, NULL, 1, '2025-11-03 14:57:48', '2025-11-03 14:57:48', '2025-11-03 14:57:48'),
(48, 11, 58, NULL, NULL, NULL, NULL, 1, '2025-11-03 14:58:56', '2025-11-03 14:58:56', '2025-11-03 14:58:56'),
(49, 11, 59, NULL, NULL, NULL, NULL, 1, '2025-11-03 14:59:48', '2025-11-03 14:59:48', '2025-11-03 14:59:48'),
(50, 11, 60, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:00:30', '2025-11-03 15:00:30', '2025-11-03 15:00:30'),
(51, 11, 61, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:03:28', '2025-11-03 15:03:28', '2025-11-03 15:03:28'),
(52, 11, 62, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:06:21', '2025-11-03 15:06:21', '2025-11-03 15:06:21'),
(53, 11, 63, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:07:18', '2025-11-03 15:07:18', '2025-11-03 15:07:18'),
(54, 11, 64, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:09:06', '2025-11-03 15:09:06', '2025-11-03 15:09:06'),
(55, 11, 65, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:10:17', '2025-11-03 15:10:17', '2025-11-03 15:10:17'),
(56, 11, 66, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:14:41', '2025-11-03 15:14:41', '2025-11-03 15:14:41'),
(57, 11, 67, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:17:05', '2025-11-03 15:17:05', '2025-11-03 15:17:05'),
(58, 11, 68, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:21:58', '2025-11-03 15:21:58', '2025-11-03 15:21:58'),
(59, 11, 69, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:25:15', '2025-11-03 15:25:15', '2025-11-03 15:25:15'),
(60, 11, 70, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:27:26', '2025-11-03 15:27:26', '2025-11-03 15:27:26'),
(61, 11, 71, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:28:06', '2025-11-03 15:28:06', '2025-11-03 15:28:06'),
(62, 11, 72, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:28:53', '2025-11-03 15:28:53', '2025-11-03 15:28:53'),
(63, 11, 73, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:29:37', '2025-11-03 15:29:37', '2025-11-03 15:29:37'),
(64, 11, 74, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:30:18', '2025-11-03 15:30:18', '2025-11-03 15:30:18'),
(65, 11, 75, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:30:51', '2025-11-03 15:30:51', '2025-11-03 15:30:51'),
(66, 11, 76, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:31:30', '2025-11-03 15:31:30', '2025-11-03 15:31:30'),
(67, 11, 77, NULL, NULL, NULL, NULL, 1, '2025-11-03 15:32:00', '2025-11-03 15:32:00', '2025-11-03 15:32:00'),
(68, 11, 78, NULL, NULL, NULL, NULL, 1, '2025-11-03 16:31:33', '2025-11-03 16:31:33', '2025-11-03 16:31:33');

-- --------------------------------------------------------

--
-- Structure de la table `club_teachers`
--

CREATE TABLE `club_teachers` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NOT NULL,
  `allowed_disciplines` json DEFAULT NULL,
  `restricted_disciplines` json DEFAULT NULL,
  `hourly_rate` decimal(8,2) DEFAULT NULL,
  `contract_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'freelance' COMMENT 'Type of contract: freelance, salaried, volunteer, student, article_17',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `club_teachers`
--

INSERT INTO `club_teachers` (`id`, `club_id`, `teacher_id`, `allowed_disciplines`, `restricted_disciplines`, `hourly_rate`, `contract_type`, `is_active`, `joined_at`, `created_at`, `updated_at`) VALUES
(9, 11, 16, NULL, NULL, 24.00, 'volunteer', 1, '2025-10-27 19:59:08', '2025-10-27 19:59:08', '2025-11-02 11:05:56'),
(10, 11, 17, NULL, NULL, 24.00, 'volunteer', 1, '2025-10-30 18:25:55', '2025-10-30 18:25:55', '2025-10-30 18:29:21'),
(11, 11, 18, NULL, NULL, 24.00, 'volunteer', 1, '2025-10-30 18:30:13', '2025-10-30 18:30:13', '2025-10-30 18:30:13'),
(12, 11, 19, NULL, NULL, 23.00, 'volunteer', 1, '2025-11-02 11:05:32', '2025-11-02 11:05:32', '2025-11-02 11:08:27'),
(13, 11, 20, NULL, NULL, 18.00, 'volunteer', 1, '2025-11-02 11:08:04', '2025-11-02 11:08:04', '2025-11-02 11:08:16'),
(14, 11, 21, NULL, NULL, 23.00, 'volunteer', 1, '2025-11-02 11:10:08', '2025-11-02 11:10:08', '2025-11-02 11:11:17'),
(15, 11, 22, NULL, NULL, 24.00, 'volunteer', 1, '2025-11-02 11:11:00', '2025-11-02 11:11:00', '2025-11-02 11:11:00'),
(16, 11, 23, NULL, NULL, 24.00, 'volunteer', 1, '2025-11-02 11:13:16', '2025-11-02 11:13:16', '2025-11-02 11:13:16');

-- --------------------------------------------------------

--
-- Structure de la table `club_user`
--

CREATE TABLE `club_user` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `role` enum('owner','manager','member','teacher','student') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `club_user`
--

INSERT INTO `club_user` (`id`, `club_id`, `user_id`, `role`, `is_admin`, `joined_at`, `created_at`, `updated_at`) VALUES
(10, 11, 76, 'owner', 1, '2025-10-26 19:53:52', '2025-10-26 19:53:52', '2025-10-26 19:53:52');

-- --------------------------------------------------------

--
-- Structure de la table `course_types`
--

CREATE TABLE `course_types` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `duration` int DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `is_individual` tinyint(1) NOT NULL DEFAULT '0',
  `max_participants` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `price` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `discipline_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_types`
--

INSERT INTO `course_types` (`id`, `club_id`, `name`, `description`, `duration`, `duration_minutes`, `is_individual`, `max_participants`, `is_active`, `price`, `created_at`, `updated_at`, `discipline_id`) VALUES
(5, NULL, 'Cours particulier  natation', 'Cours de natation individuel de 20 minutes.', NULL, 20, 1, 1, 1, NULL, '2025-09-14 16:48:22', '2025-09-14 16:48:22', 11),
(6, NULL, 'Aquagym', 'Cours d\'aquagym collectif d\'une heure.', NULL, 60, 0, 12, 1, NULL, '2025-09-14 16:48:22', '2025-09-14 16:48:22', 11),
(17, NULL, 'Natation - Cours standard', 'Cours standard de Natation', NULL, 60, 0, 10, 1, 25.00, '2025-11-02 22:14:05', '2025-11-02 22:14:05', 2);

-- --------------------------------------------------------

--
-- Structure de la table `disciplines`
--

CREATE TABLE `disciplines` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `activity_type_id` bigint UNSIGNED DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_participants` int NOT NULL DEFAULT '1',
  `max_participants` int NOT NULL DEFAULT '8',
  `duration_minutes` int NOT NULL DEFAULT '60',
  `equipment_required` json DEFAULT NULL,
  `skill_levels` json DEFAULT NULL,
  `base_price` decimal(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `disciplines`
--

INSERT INTO `disciplines` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`, `activity_type_id`, `slug`, `min_participants`, `max_participants`, `duration_minutes`, `equipment_required`, `skill_levels`, `base_price`) VALUES
(2, 'Natation', 'Discipline aquatique comprenant cours particuliers et aquagym.', 1, '2025-09-14 16:48:22', '2025-09-14 16:48:22', NULL, NULL, 1, 8, 60, NULL, NULL, NULL),
(8, 'Aquagym', 'Gymnastique dans l\'eau', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40', 2, 'aquagym', 12, 20, 45, '[\"frites\", \"planches\", \"haltères aquatiques\"]', '[\"débutant\", \"intermédiaire\"]', 20.00),
(11, 'Natation individuel', 'Cours de Natation individuel', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40', 2, 'natation individuel', 12, 20, 45, '[\"planches\"]', '[\"débutant\", \"intermédiaire\"]', 20.00);

-- --------------------------------------------------------

--
-- Structure de la table `facilities`
--

CREATE TABLE `facilities` (
  `id` bigint UNSIGNED NOT NULL,
  `activity_type_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'indoor',
  `capacity` int NOT NULL DEFAULT '1',
  `dimensions` json DEFAULT NULL,
  `equipment` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `facilities`
--

INSERT INTO `facilities` (`id`, `activity_type_id`, `name`, `type`, `capacity`, `dimensions`, `equipment`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Manège Principal', 'indoor', 4, '{\"width\": 40, \"height\": 4, \"length\": 20}', '[\"obstacles\", \"miroirs\", \"sonorisation\"]', 'Manège couvert principal pour cours et compétitions', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40'),
(2, 1, 'Carrière A', 'outdoor', 6, '{\"width\": 60, \"length\": 30}', '[\"obstacles fixes\", \"obstacles mobiles\", \"arrosage\"]', 'Carrière extérieure pour saut d\'obstacles', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40'),
(3, 1, 'Carrière B', 'outdoor', 4, '{\"width\": 40, \"length\": 20}', '[\"lettres de dressage\", \"barres de dressage\"]', 'Carrière extérieure pour dressage', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40'),
(4, 1, 'Paddock', 'outdoor', 8, '{\"width\": 25, \"length\": 15}', '[\"barrières\", \"abreuvoirs\"]', 'Zone de détente pour les chevaux', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40'),
(5, 2, 'Bassin 25m', 'indoor', 16, '{\"depth\": 1.5, \"width\": 12.5, \"length\": 25}', '[\"chronomètres\", \"starting blocks\", \"lignes de nage\"]', 'Bassin principal de 25 mètres pour natation sportive', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40'),
(6, 2, 'Bassin 50m', 'indoor', 24, '{\"depth\": 2, \"width\": 25, \"length\": 50}', '[\"chronomètres\", \"starting blocks\", \"lignes de nage\", \"plongeoir\"]', 'Bassin olympique de 50 mètres', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40'),
(7, 2, 'Piscine Enfants', 'indoor', 12, '{\"depth\": 0.8, \"width\": 8, \"length\": 10}', '[\"jouets aquatiques\", \"toboggan\", \"fontaines\"]', 'Piscine spécialement conçue pour les enfants', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40'),
(8, 2, 'Jacuzzi', 'indoor', 8, '{\"depth\": 1.2, \"width\": 3, \"length\": 4}', '[\"jets hydromassants\", \"éclairage LED\", \"sièges\"]', 'Zone de relaxation avec jacuzzi', 1, '2025-09-14 16:48:40', '2025-09-14 16:48:40');

-- --------------------------------------------------------

--
-- Structure de la table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `google_calendar_tokens`
--

CREATE TABLE `google_calendar_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_info` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `calendars` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lessons`
--

CREATE TABLE `lessons` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED DEFAULT NULL,
  `course_type_id` bigint UNSIGNED NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled','no_show','available') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `price` decimal(8,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `teacher_feedback` text COLLATE utf8mb4_unicode_ci,
  `rating` int DEFAULT NULL,
  `review` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lesson_replacements`
--

CREATE TABLE `lesson_replacements` (
  `id` bigint UNSIGNED NOT NULL,
  `lesson_id` bigint UNSIGNED NOT NULL,
  `original_teacher_id` bigint UNSIGNED NOT NULL,
  `replacement_teacher_id` bigint UNSIGNED NOT NULL,
  `status` enum('pending','accepted','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `requested_at` timestamp NULL DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lesson_student`
--

CREATE TABLE `lesson_student` (
  `id` bigint UNSIGNED NOT NULL,
  `lesson_id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `status` enum('confirmed','pending','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `price` decimal(8,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `locations`
--

CREATE TABLE `locations` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `facilities` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_08_10_201702_create_profiles_table', 1),
(5, '2025_08_10_201707_create_teachers_table', 1),
(6, '2025_08_10_201712_create_students_table', 1),
(7, '2025_08_10_201717_create_course_types_table', 1),
(8, '2025_08_10_201722_create_locations_table', 1),
(9, '2025_08_10_201727_create_lessons_table', 1),
(10, '2025_08_10_201731_create_payments_table', 1),
(11, '2025_08_10_201735_create_invoices_table', 1),
(12, '2025_08_10_201740_create_subscriptions_table', 1),
(13, '2025_08_10_201744_create_availabilities_table', 1),
(14, '2025_08_10_201749_create_time_blocks_table', 1),
(15, '2025_08_10_201754_create_payouts_table', 1),
(16, '2025_08_10_201834_add_role_and_fields_to_users_table', 1),
(17, '2025_08_10_210550_create_personal_access_tokens_table', 1),
(18, '2025_08_11_041124_create_app_settings_table', 1),
(19, '2025_08_11_045535_add_payment_status_to_lessons_table', 1),
(20, '2025_08_11_142438_add_is_active_to_users_table', 1),
(21, '2025_08_12_043806_create_clubs_table', 1),
(22, '2025_08_12_043910_add_club_role_and_relationships', 1),
(23, '2025_08_12_203917_add_key_value_system_to_app_settings_table', 1),
(24, '2025_08_31_062955_add_preference_columns_to_students_table', 1),
(25, '2025_09_01_122803_allow_null_student_id_in_lessons_table', 1),
(26, '2025_09_01_131559_create_lesson_student_table', 1),
(27, '2025_09_01_172855_add_available_status_to_lessons_table', 1),
(28, '2025_09_05_192730_create_disciplines_table', 1),
(29, '2025_09_05_192818_modify_course_types_table_for_disciplines', 1),
(30, '2025_09_06_131412_create_audit_logs_table', 1),
(31, '2025_09_07_061339_create_club_user_table', 1),
(32, '2025_09_07_110546_create_activity_types_table', 1),
(33, '2025_09_07_110550_create_facilities_table', 1),
(34, '2025_09_07_110556_add_activity_type_to_clubs_table', 1),
(35, '2025_09_07_111716_create_cash_registers_table', 1),
(36, '2025_09_07_111720_create_product_categories_table', 1),
(37, '2025_09_07_111723_create_products_table', 1),
(38, '2025_09_07_111727_create_transactions_table', 1),
(39, '2025_09_07_111731_create_transaction_items_table', 1),
(40, '2025_09_07_112018_add_columns_to_disciplines_table', 1),
(41, '2025_09_07_112949_create_club_settings_table', 1),
(42, '2025_09_07_113147_create_skills_table', 1),
(43, '2025_09_07_113153_create_certifications_table', 1),
(44, '2025_09_07_113201_create_teacher_skills_table', 1),
(45, '2025_09_07_113208_create_teacher_certifications_table', 1),
(46, '2025_09_08_071425_create_club_activity_types_table', 1),
(47, '2025_09_08_071432_create_club_disciplines_table', 1),
(48, '2025_09_08_110132_create_student_disciplines_table', 1),
(49, '2025_09_08_110133_create_student_medical_documents_table', 1),
(50, '2025_09_08_130505_modify_students_level_column', 1),
(51, '2025_09_08_150012_create_club_teachers_table', 1),
(52, '2025_09_08_150021_create_club_students_table', 1),
(53, '2025_09_08_150026_create_teacher_disciplines_table', 1),
(54, '2025_09_08_150215_add_qr_code_to_users_table', 1),
(55, '2025_09_08_152713_add_qr_code_to_clubs_table', 1),
(56, '2025_09_08_171200_create_club_custom_specialties_table', 1),
(57, '2025_09_09_075103_add_contract_type_to_club_teacher_table', 1),
(58, '2025_09_09_142031_update_users_table_add_detailed_fields', 1),
(59, '2025_09_10_141508_create_student_preferences_table', 1),
(60, '2025_09_17_144552_add_street_fields_to_clubs_table', 2),
(61, '2025_09_17_190334_add_street_box_to_users_table', 3),
(62, '2025_09_20_090812_create_google_calendar_tokens_table', 3),
(63, '2025_09_20_100000_create_bookings_table', 3),
(64, '2025_09_26_175014_add_discipline_settings_to_clubs_table', 3),
(65, '2025_10_01_052132_add_activity_types_and_settings_to_clubs_table', 3),
(66, '2025_10_01_083928_create_club_open_slots_table', 3),
(67, '2025_10_04_140000_create_subscriptions_table', 4),
(68, '2025_10_06_201808_create_course_types_table_if_not_exists', 4),
(69, '2025_10_10_100000_create_club_open_slot_course_types_table', 4),
(70, '2025_10_10_100001_assign_default_course_types_to_existing_slots', 4),
(71, '2025_01_15_000000_update_subscriptions_for_course_types_and_validity', 5),
(72, '2025_01_16_000000_create_subscription_templates_table', 6),
(73, '2025_08_13_000000_update_subscriptions_for_course_types_and_validity', 6),
(74, '2025_08_13_100000_create_subscription_templates_table', 7),
(75, '2025_10_19_000000_add_club_id_to_lessons_table', 8),
(76, '2025_10_23_044356_add_max_slots_to_club_open_slots_table', 8),
(77, '2025_10_24_150000_create_lesson_replacements_table', 8),
(78, '2025_10_24_200000_add_date_of_birth_to_students_table', 8),
(79, '2025_10_25_142251_create_notifications_table', 8),
(80, '2025_10_28_205730_add_company_number_to_clubs_table', 8),
(81, '2025_10_28_210643_add_legal_fields_to_clubs_table', 8),
(82, '2025_10_28_210644_add_address_fields_to_users_table', 8),
(83, '2025_10_28_212731_create_volunteer_letter_sends_table', 8),
(84, '2025_10_28_214000_create_volunteer_expense_limits_table', 8),
(85, '2025_11_01_124929_add_subscription_defaults_to_clubs_table', 8),
(86, '2025_11_02_083136_add_validity_value_and_unit_to_subscription_templates', 8),
(87, '2025_11_02_111356_add_niss_bank_account_and_experience_start_to_users_table', 8),
(88, '2025_11_02_161402_add_performance_indexes_to_lessons_table', 8),
(89, '2025_11_02_180000_make_user_id_nullable_in_students_table', 8),
(90, '2025_11_03_114819_add_first_name_last_name_to_students_table', 9),
(91, '2025_11_03_161612_add_club_id_to_subscriptions_table_if_missing', 10),
(92, '2025_11_03_200000_create_subscription_recurring_slots_table', 11),
(93, '2025_11_03_212042_add_club_id_to_course_types_table', 12);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `type` enum('replacement_request','replacement_accepted','replacement_rejected','replacement_cancelled','club_replacement_accepted') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('a.lapaglia16@gmail.com', '$2y$12$jV2nneX3DnDwy9eHBNuYAugEZHX7QX5vOFpM6qM6yD4EVmIUUcW7e', '2025-10-30 18:30:13'),
('fannyvogels@gmail.com', '$2y$12$oaY8W.glw6ReeJFYLn7v5OYmWKD41H/5IpHqksLuDGX6oLKpFww5e', '2025-11-02 11:13:17'),
('info@activibe.be', '$2y$12$2yP8wuubBe8s.QNBhanKJecef9Fz36SAVXKUNEkupvZAdRA1HZbgC', '2025-11-02 23:14:31'),
('j.feincour02@gmail.com', '$2y$12$IbItFhNv2wlY4hPPs9t2.Ozs9C3TPQXHUz0v8eNJNiSfVxLcd.ZB.', '2025-10-30 18:25:56');

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` bigint UNSIGNED NOT NULL,
  `lesson_id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `payment_method` enum('card','bank_transfer','cash','paypal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','succeeded','failed','canceled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `stripe_payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payouts`
--

CREATE TABLE `payouts` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'auth_token', '5390fe764beab1548c89ae9864e4a7e14bc6b0087c6f55ea01efca388be592cc', '[\"*\"]', NULL, NULL, '2025-09-14 18:35:43', '2025-09-14 18:35:43'),
(2, 'App\\Models\\User', 1, 'auth_token', 'fd529b4cb4814fb7f1b0a1edb124c4e6896851606c14130fb79fe403bd3d1d03', '[\"*\"]', NULL, NULL, '2025-09-14 19:23:50', '2025-09-14 19:23:50'),
(3, 'App\\Models\\User', 1, 'auth_token', '4bf0af6fc1e3d9e00b760fcab920407682db54ea847fcb48b4d613164be10a35', '[\"*\"]', NULL, NULL, '2025-09-14 20:14:30', '2025-09-14 20:14:30'),
(4, 'App\\Models\\User', 1, 'auth_token', 'e9dcce8069220d72bfb7af1390f79d601c1112d234638710ed570defa87c5a47', '[\"*\"]', NULL, NULL, '2025-09-15 06:59:40', '2025-09-15 06:59:40'),
(5, 'App\\Models\\User', 1, 'auth_token', '81deda17abaa285a50b6e53747709ba5a82846207ced856b7d5f499a9788e7c8', '[\"*\"]', NULL, NULL, '2025-09-15 07:22:56', '2025-09-15 07:22:56'),
(6, 'App\\Models\\User', 1, 'auth_token', '886468cafae532257818e0920d403a75a2bf2d61b2ef82ed99356519cc35d8b3', '[\"*\"]', NULL, NULL, '2025-09-15 07:30:52', '2025-09-15 07:30:52'),
(7, 'App\\Models\\User', 1, 'auth_token', 'ef215efa657f635675a5e59dbe48648615ee0c91ef5bca421ed0588fd00e0309', '[\"*\"]', NULL, NULL, '2025-09-15 07:32:59', '2025-09-15 07:32:59'),
(8, 'App\\Models\\User', 1, 'auth_token', '6c8ba201c4b8c478a178777d5adc881053b2927a5ea75a4b3125e6c71ae0a8ca', '[\"*\"]', NULL, NULL, '2025-09-15 07:35:30', '2025-09-15 07:35:30'),
(9, 'App\\Models\\User', 1, 'auth_token', '4ded4f0e40bd4d404400c5fcf4190f63b0fbd7f8cc57d8bf16d2023f6de2d5a1', '[\"*\"]', NULL, NULL, '2025-09-15 07:48:43', '2025-09-15 07:48:43'),
(10, 'App\\Models\\User', 1, 'auth_token', '984a1c083a6391216f281b556790843723806776ede6c8769d7e1a5ebab6a040', '[\"*\"]', NULL, NULL, '2025-09-15 07:56:59', '2025-09-15 07:56:59'),
(11, 'App\\Models\\User', 1, 'auth_token', 'd6c7e9590c94dfeab8a43b2f5deb4783def3e2864238d286dcf5a2f85ec9995f', '[\"*\"]', NULL, NULL, '2025-09-15 08:15:12', '2025-09-15 08:15:12'),
(12, 'App\\Models\\User', 1, 'auth_token', 'ce732d495ee0b5bc512564b11ec01c278d0cb854000ee959e8dbfa193040f92f', '[\"*\"]', NULL, NULL, '2025-09-15 08:19:49', '2025-09-15 08:19:49'),
(13, 'App\\Models\\User', 1, 'auth_token', 'e4b68fb63ced07ef42a7739fdba7960b43ec33fac9f2ab83d67ed305c185f115', '[\"*\"]', NULL, NULL, '2025-09-15 08:23:48', '2025-09-15 08:23:48'),
(14, 'App\\Models\\User', 1, 'auth_token', '60123d24e3ae37b15b46ed561f8fbcbd658630cdef2c93995916860f438f97de', '[\"*\"]', NULL, NULL, '2025-09-15 08:56:26', '2025-09-15 08:56:26'),
(15, 'App\\Models\\User', 1, 'auth_token', '951abe1ac7c59d116560d2d03b44873a57f81e4d1875ba0e4c2569c05393b63c', '[\"*\"]', NULL, NULL, '2025-09-15 09:03:17', '2025-09-15 09:03:17'),
(16, 'App\\Models\\User', 1, 'auth_token', '61caa61625608f3cd00ce536562e51206b6f786eea79d208fb44fca5c7411ba3', '[\"*\"]', NULL, NULL, '2025-09-15 09:09:05', '2025-09-15 09:09:05'),
(17, 'App\\Models\\User', 1, 'auth_token', '0392bcbee73336d2bb3a17037a894b64afe832889f0519a9413bb3775758c66a', '[\"*\"]', NULL, NULL, '2025-09-15 09:15:00', '2025-09-15 09:15:00'),
(18, 'App\\Models\\User', 1, 'auth_token', 'e1b6619cc76833470e042bef0f632b8d180f875e0e7914f045807bfd333127a1', '[\"*\"]', NULL, NULL, '2025-09-15 09:33:30', '2025-09-15 09:33:30'),
(19, 'App\\Models\\User', 1, 'auth_token', '6c62e8527a95b5a697d9cf2c6dec76493bfe432ed47130b277a202f040459a85', '[\"*\"]', NULL, NULL, '2025-09-15 09:33:58', '2025-09-15 09:33:58'),
(20, 'App\\Models\\User', 1, 'auth_token', 'f1215fa8fdba7d0c898019add1eb013645bcfcbbac04e33f4cb50de753c0462a', '[\"*\"]', NULL, NULL, '2025-09-15 09:52:31', '2025-09-15 09:52:31'),
(21, 'App\\Models\\User', 1, 'auth_token', 'ec033b611dd93262f9bba8a2302e3e1b8893c394a73c504757f91cd2e21f8d21', '[\"*\"]', NULL, NULL, '2025-09-15 09:53:01', '2025-09-15 09:53:01'),
(22, 'App\\Models\\User', 1, 'auth_token', '20872886f19cd895dafc4374a7c8561c1e376dfbb578c060e8acb0b86ddae5e3', '[\"*\"]', NULL, NULL, '2025-09-15 09:55:43', '2025-09-15 09:55:43'),
(23, 'App\\Models\\User', 1, 'auth_token', '2cf3d7183abeb1da29be03c12dc6689a863f8390ba09ce7a3b9dabb20dac2024', '[\"*\"]', NULL, NULL, '2025-09-15 09:57:38', '2025-09-15 09:57:38'),
(24, 'App\\Models\\User', 1, 'auth_token', '6082c9f703e60bcf2a0d4b6c653cf5d132ae082d824170e5c9613fcc1914ba90', '[\"*\"]', NULL, NULL, '2025-09-15 09:58:40', '2025-09-15 09:58:40'),
(25, 'App\\Models\\User', 1, 'auth_token', '107420a0db41a2e2de200f9a5bc0852fdd7d5f930e2b2144a0f25584c11b234b', '[\"*\"]', NULL, NULL, '2025-09-15 10:00:01', '2025-09-15 10:00:01'),
(26, 'App\\Models\\User', 1, 'auth_token', '53755dbdcc3bddfe5a2d892de87a950136862913a5a570d6f7e3fe478a03ae59', '[\"*\"]', NULL, NULL, '2025-09-15 10:13:04', '2025-09-15 10:13:04'),
(27, 'App\\Models\\User', 1, 'auth_token', 'ef236ed9886a8afc267fa2f6d68c2a23372213da37fb4739ae6779c769fb9b29', '[\"*\"]', NULL, NULL, '2025-09-15 10:26:03', '2025-09-15 10:26:03'),
(28, 'App\\Models\\User', 1, 'auth_token', '1ea17531f65b6497004610b29e7c4e31dfe61e7da9df30e2122f2279d1b1b2f7', '[\"*\"]', NULL, NULL, '2025-09-15 10:52:41', '2025-09-15 10:52:41'),
(29, 'App\\Models\\User', 1, 'auth_token', 'a4cd099420d7336c545e29486c265c129ee413fca014667bf9628856016f0ef6', '[\"*\"]', NULL, NULL, '2025-09-15 13:02:18', '2025-09-15 13:02:18'),
(30, 'App\\Models\\User', 1, 'auth_token', 'da9e7779dae1bc5257d44262b21251bfc15cb24345ca19e48a1a8037df71eabd', '[\"*\"]', NULL, NULL, '2025-09-15 13:45:16', '2025-09-15 13:45:16'),
(31, 'App\\Models\\User', 1, 'auth_token', 'fe6547c35bf905aca9ad91f080c7fe1b70a46923bb9ab6e123bd96e02ca207d1', '[\"*\"]', NULL, NULL, '2025-09-15 13:48:41', '2025-09-15 13:48:41'),
(32, 'App\\Models\\User', 1, 'auth_token', '1c8923f28e2f5f85a355450b9a9513db451a58054b346296a8eeb25fc72dba20', '[\"*\"]', NULL, NULL, '2025-09-15 13:55:17', '2025-09-15 13:55:17'),
(33, 'App\\Models\\User', 1, 'auth_token', 'd1514a5b73147060385e7cee810b810fc1df8e1d62da55cf590ac73f44170af2', '[\"*\"]', NULL, NULL, '2025-09-15 15:14:19', '2025-09-15 15:14:19'),
(34, 'App\\Models\\User', 1, 'auth_token', '80dbe50b454f2be503224f0a199a6b308b42fcf4ee42b42d06f89be31ab91349', '[\"*\"]', NULL, NULL, '2025-09-15 15:22:40', '2025-09-15 15:22:40'),
(35, 'App\\Models\\User', 1, 'test-token', 'e123aa9e6b6639b15a1f379fcca6b0be114cf97a66befdbe1ac4c32734de218b', '[\"*\"]', NULL, NULL, '2025-09-16 08:47:39', '2025-09-16 08:47:39'),
(36, 'App\\Models\\User', 1, 'test-token', 'c85ce2993cc78bf0842a4f05d6fed3fe4f438960309309a6fec69eea03102181', '[\"*\"]', NULL, NULL, '2025-09-16 09:36:38', '2025-09-16 09:36:38'),
(37, 'App\\Models\\User', 1, 'test-token', 'd3d1d909491c515373521348ca109b5bb88592959dc099160f27211d7526f0a7', '[\"*\"]', NULL, NULL, '2025-09-16 10:12:40', '2025-09-16 10:12:40'),
(38, 'App\\Models\\User', 1, 'test-token', '443297e62bc505b973dbbb182b1f0f11372ce9bd18e90dc98b3d3d19b0493ef5', '[\"*\"]', NULL, NULL, '2025-09-16 11:37:45', '2025-09-16 11:37:45'),
(39, 'App\\Models\\User', 1, 'test-token', 'e6b2670ec1e52fec225bcbb7948642ec6a9c6c355991992f6af546de44f15cc6', '[\"*\"]', NULL, NULL, '2025-09-16 11:37:50', '2025-09-16 11:37:50'),
(40, 'App\\Models\\User', 1, 'test-token', '0f6db8265d4811dc9d7718b9c63f372ab9422e0f59a93816170990ec4391b5bc', '[\"*\"]', NULL, NULL, '2025-09-16 11:59:09', '2025-09-16 11:59:09'),
(41, 'App\\Models\\User', 1, 'test-token', '92462583b1a51c9b945db60e0f11fe774e2964579527d3cf0c640e17f9c16ae3', '[\"*\"]', NULL, NULL, '2025-09-16 12:07:12', '2025-09-16 12:07:12'),
(42, 'App\\Models\\User', 1, 'test-token', '3134e25b14c233cbd5c75faf2f0fac3d3d2982cfd84bd2c783f4b5c61d011273', '[\"*\"]', NULL, NULL, '2025-09-16 12:49:59', '2025-09-16 12:49:59'),
(43, 'App\\Models\\User', 1, 'test-token', 'c25d3eb46375701bd987e02fc924e2707a524cf1903f74ff579a5e18153aa7b3', '[\"*\"]', NULL, NULL, '2025-09-16 13:51:59', '2025-09-16 13:51:59'),
(44, 'App\\Models\\User', 1, 'test-token', '4ed8783c07704f964e0c22444eed3de62a9531f497a8ee311a116346fb98343f', '[\"*\"]', NULL, NULL, '2025-09-16 13:52:06', '2025-09-16 13:52:06'),
(45, 'App\\Models\\User', 1, 'test-token', '0b86c017e0c4d4b251a01f9bf65aaca00f43540fc6f9ec6613bb5face21f71b1', '[\"*\"]', NULL, NULL, '2025-09-16 14:11:31', '2025-09-16 14:11:31'),
(46, 'App\\Models\\User', 1, 'test-token', 'c83daddf88311158650eb35c9c48be0a8d52361d37df2ea58fa9bf20cff553b2', '[\"*\"]', NULL, NULL, '2025-09-16 14:32:17', '2025-09-16 14:32:17'),
(47, 'App\\Models\\User', 1, 'test-token', '79fe8e24fcc241da1327265a215b7187a89fdff2398f90a216c7fc185713964c', '[\"*\"]', NULL, NULL, '2025-09-16 14:48:50', '2025-09-16 14:48:50'),
(48, 'App\\Models\\User', 1, 'test-token', '7bf3bf0605667ed98c436ccd42c7839638167c938effe487b79e37cc45366c4d', '[\"*\"]', NULL, NULL, '2025-09-16 14:52:31', '2025-09-16 14:52:31'),
(49, 'App\\Models\\User', 1, 'test-token', '097cb7a8c9a9c8b2ae8f8113cbb151ac532b7f7fbb020584ee910d558b784d68', '[\"*\"]', NULL, NULL, '2025-09-16 20:42:49', '2025-09-16 20:42:49'),
(50, 'App\\Models\\User', 1, 'test-token', '3d021072153d368f6552f5b7e667d73a0f3e0b6ace97f1afd123f8d0479cab0a', '[\"*\"]', NULL, NULL, '2025-09-17 04:53:46', '2025-09-17 04:53:46'),
(51, 'App\\Models\\User', 1, 'test-token', '76174a163294dcb9d3f6ac6d831909d94347a71c4610ded1f73b1843e7e26c4a', '[\"*\"]', NULL, NULL, '2025-09-17 05:11:29', '2025-09-17 05:11:29'),
(52, 'App\\Models\\User', 1, 'test-token', '5ba71f45d53b2ce4165e359aa44278080313536f20289540808aaa98f4c6230d', '[\"*\"]', NULL, NULL, '2025-09-17 06:36:42', '2025-09-17 06:36:42'),
(53, 'App\\Models\\User', 1, 'test-token', '2a2cfe87f533cf2f6fb6a06e177beb8e686b3318a72670501efeaefccd1a99f8', '[\"*\"]', NULL, NULL, '2025-09-17 06:36:46', '2025-09-17 06:36:46'),
(54, 'App\\Models\\User', 1, 'test-token', '6d569058762258a84997f19c2ba2702ecb2cabbba9ed49788f42d7ff5401cc63', '[\"*\"]', NULL, NULL, '2025-09-17 07:05:28', '2025-09-17 07:05:28'),
(55, 'App\\Models\\User', 1, 'test-token', '817ce0c6a39cf65f65138b88bc38d43634c87b9b7fdc98f61b572a10a485549b', '[\"*\"]', NULL, NULL, '2025-09-17 11:37:18', '2025-09-17 11:37:18'),
(56, 'App\\Models\\User', 1, 'test-token', 'f290a0a13ab4e0cdb2d83ddba390ae078d776a031201e14def22953227e4e518', '[\"*\"]', NULL, NULL, '2025-09-17 11:53:34', '2025-09-17 11:53:34'),
(57, 'App\\Models\\User', 1, 'test-token', '503cb68e498b7bc3f971eb7c16820990e9c3819de606cfde70b88372b440a69f', '[\"*\"]', NULL, NULL, '2025-09-17 12:16:45', '2025-09-17 12:16:45'),
(58, 'App\\Models\\User', 1, 'test-token', '4ab33a2a482e0f78083233899e0cda8f14b18f641578d07b4cc13772ddf1666a', '[\"*\"]', NULL, NULL, '2025-09-17 12:54:16', '2025-09-17 12:54:16'),
(59, 'App\\Models\\User', 1, 'test-token', '1f86ed24cb3d89f92e8a17fab8b3a961e02b7961fa2bcef826cc12421eadd3fd', '[\"*\"]', NULL, NULL, '2025-09-17 13:28:44', '2025-09-17 13:28:44'),
(60, 'App\\Models\\User', 1, 'test-token', '392813b1f41aa7f66fb55ea4599282fe3ccd240733b939cfa0ac0aa19228404e', '[\"*\"]', NULL, NULL, '2025-09-17 13:28:53', '2025-09-17 13:28:53'),
(61, 'App\\Models\\User', 1, 'test-token', '89ab0e0bb755c7984a41782a4da25ddd826647a202b55c5bc20c0d95d6cef51b', '[\"*\"]', NULL, NULL, '2025-09-17 13:59:18', '2025-09-17 13:59:18'),
(62, 'App\\Models\\User', 1, 'test-token', '828a58718c10b55a568107df1af6351bdb88c99effe9523958d3c02dd7d8511a', '[\"*\"]', NULL, NULL, '2025-09-17 13:59:37', '2025-09-17 13:59:37'),
(63, 'App\\Models\\User', 1, 'test-token', '9adcb25bd39b1fa8d4a71224a6c766c34ef2e083ab8c2b74a1a7c52f216df73b', '[\"*\"]', NULL, NULL, '2025-09-17 14:44:01', '2025-09-17 14:44:01'),
(64, 'App\\Models\\User', 1, 'test-token', '35934fc5f88df5ab388921852d2905dc6990379b63f64f46629d7b7f605d6276', '[\"*\"]', NULL, NULL, '2025-09-17 15:09:26', '2025-09-17 15:09:26'),
(65, 'App\\Models\\User', 1, 'test-token', '1e9b7d07468d161cb942600adc715e5e100b7ce8b4833c578fb5516c24d8b0af', '[\"*\"]', NULL, NULL, '2025-09-17 15:15:21', '2025-09-17 15:15:21'),
(70, 'App\\Models\\User', 2, 'test-token', 'b5d4cf7b5ca4ee9e8ef4d2d2c9f3ac526dfa31fa3836f7b9c81913a10b54efe2', '[\"*\"]', NULL, NULL, '2025-09-17 18:27:26', '2025-09-17 18:27:26'),
(71, 'App\\Models\\User', 2, 'test-token', '5ff5ce6339ab65c292d2a5111fea35d372db38cf75506c4bfcb568c00b876205', '[\"*\"]', NULL, NULL, '2025-09-17 18:29:42', '2025-09-17 18:29:42'),
(72, 'App\\Models\\User', 2, 'test-token', '32fde2354254f21becec0ec2840482acd563f88fd8606835f58fa0e8b93ed964', '[\"*\"]', NULL, NULL, '2025-09-17 18:31:41', '2025-09-17 18:31:41'),
(73, 'App\\Models\\User', 2, 'test-token', '684dd5ccc49343941f70bcbef19dbad2e40ef9a0a290abff54eddb4b1ef83998', '[\"*\"]', NULL, NULL, '2025-09-17 18:33:09', '2025-09-17 18:33:09'),
(75, 'App\\Models\\User', 1, 'test-token', '8039ce3c459b44d0d72aad195dfe98e8f0afecc228570a4aaed7defc9d67a039', '[\"*\"]', NULL, NULL, '2025-09-17 18:43:48', '2025-09-17 18:43:48'),
(76, 'App\\Models\\User', 1, 'test-token', 'ebfff85591357848205801395de477e36d27f101d81b67f2ad610991f6935e7d', '[\"*\"]', NULL, NULL, '2025-09-17 18:47:30', '2025-09-17 18:47:30'),
(77, 'App\\Models\\User', 1, 'test-token', '645afbe6d6d9063cded3b8213360cf4355a7837b617b781e2450b92d723e37ac', '[\"*\"]', NULL, NULL, '2025-09-17 19:08:32', '2025-09-17 19:08:32'),
(78, 'App\\Models\\User', 2, 'test-token', 'dd5e678d35a8fa1c643f8ffc3e95c43d29ec5bf64e68320e2eb33d0d1b04e402', '[\"*\"]', NULL, NULL, '2025-09-17 19:19:02', '2025-09-17 19:19:02'),
(79, 'App\\Models\\User', 1, 'test-token', 'bb03186c2679de2521d8823f59140597d81fc5ee9255323bb66aeb1c9963859d', '[\"*\"]', NULL, NULL, '2025-09-17 19:27:51', '2025-09-17 19:27:51'),
(80, 'App\\Models\\User', 2, 'test-token', 'c102e7d8c0607b71d3e7004be9b638fce7a480834d3043efe5307a6b50f1c62b', '[\"*\"]', NULL, NULL, '2025-09-17 19:37:29', '2025-09-17 19:37:29'),
(81, 'App\\Models\\User', 2, 'test-token', '8c3bab868493f9b3f1fed32bf823f5b66aa1383b8a015974e2928d6a69dede86', '[\"*\"]', NULL, NULL, '2025-09-17 19:50:57', '2025-09-17 19:50:57'),
(83, 'App\\Models\\User', 2, 'test-token', '88734dd51613be83e890f0ac991b5b34e4fb036b6567b1fd4a12e1b9b877dbeb', '[\"*\"]', '2025-09-20 07:07:25', NULL, '2025-09-17 20:00:56', '2025-09-20 07:07:25'),
(85, 'App\\Models\\User', 1, 'api-token', '4541614cdf7b88664f5d00379dab6a48a3c8fa3118c3b9e08638998bfbac6d17', '[\"*\"]', '2025-09-19 13:09:41', NULL, '2025-09-19 12:59:40', '2025-09-19 13:09:41'),
(86, 'App\\Models\\User', 1, 'api-token', '22c3654ae4cec1088ea1d281d48ad6b06f51cc71f7fc802053476d1c2ed4db47', '[\"*\"]', '2025-09-19 13:09:45', NULL, '2025-09-19 13:09:45', '2025-09-19 13:09:45'),
(87, 'App\\Models\\User', 1, 'api-token', '2cdab3758f16920efd22e50a375117f3068fe29766982264c4c5372b73361683', '[\"*\"]', '2025-09-19 13:19:45', NULL, '2025-09-19 13:09:48', '2025-09-19 13:19:45'),
(88, 'App\\Models\\User', 1, 'api-token', 'b1ce7b8bc0113f3fd0d1f9bfad0e0a1da57f8ec9a25a193556b7d33f29cae126', '[\"*\"]', '2025-09-19 13:22:36', NULL, '2025-09-19 13:22:36', '2025-09-19 13:22:36'),
(91, 'App\\Models\\User', 2, 'api-token', 'ac021a8e8f711e2d1c83a63d87db938052d5fba6e1332b85dfcbd434e6066dff', '[\"*\"]', '2025-09-19 16:11:59', NULL, '2025-09-19 16:11:57', '2025-09-19 16:11:59'),
(92, 'App\\Models\\User', 2, 'api-token', '0b7db334f65ad7efe5caa69e74c98a0991b229c7692e6ea843431975ae855dcc', '[\"*\"]', '2025-09-19 16:12:25', NULL, '2025-09-19 16:12:00', '2025-09-19 16:12:25'),
(93, 'App\\Models\\User', 2, 'api-token', 'f03c2c3eeab04621ceb829ba6f28308cf55edace6a8cff906d8004ff5131c1c4', '[\"*\"]', '2025-09-19 16:21:59', NULL, '2025-09-19 16:12:41', '2025-09-19 16:21:59'),
(94, 'App\\Models\\User', 2, 'api-token', '31606ae97f6055371c65c10115919faf4d9d14da5d2bc68579f4858963de0892', '[\"*\"]', '2025-09-19 16:44:25', NULL, '2025-09-19 16:44:25', '2025-09-19 16:44:25'),
(96, 'App\\Models\\User', 2, 'api-token', '5a4d51d7a9a5bb151a08f0f5a0ee7e38f03f87da4bd1936449f4da2d3e9eac14', '[\"*\"]', '2025-09-19 22:31:05', NULL, '2025-09-19 22:31:04', '2025-09-19 22:31:05'),
(97, 'App\\Models\\User', 2, 'api-token', '4eb4fb7a8ab4e4d786c75312eec61d6cd7fc237a72d7f5ff04a98190be4c6a75', '[\"*\"]', '2025-09-19 22:31:08', NULL, '2025-09-19 22:31:08', '2025-09-19 22:31:08'),
(98, 'App\\Models\\User', 2, 'api-token', 'b2ce3546c13f362c120d550aeed97d594e60752001e776854065865a92372570', '[\"*\"]', '2025-09-20 07:17:30', NULL, '2025-09-20 07:07:29', '2025-09-20 07:17:30'),
(99, 'App\\Models\\User', 2, 'api-token', '7d7fabf2750a69f8551a5b121a7b985ab40049d5a75134d41097fe07cd89fb05', '[\"*\"]', NULL, NULL, '2025-09-20 08:15:44', '2025-09-20 08:15:44'),
(101, 'App\\Models\\User', 2, 'api-token', 'e14544993c984a4966d4e03f1b8af087a77f6368caae4e1ce84333f87c3dfe14', '[\"*\"]', NULL, NULL, '2025-09-20 08:28:41', '2025-09-20 08:28:41'),
(102, 'App\\Models\\User', 2, 'api-token', '1c1923ad95490f9b3a440f12281dcb517dd7de4ce920b8a701a5393b3c0ab034', '[\"*\"]', NULL, NULL, '2025-09-20 08:28:50', '2025-09-20 08:28:50'),
(103, 'App\\Models\\User', 2, 'api-token', '016233bb23cf7461bc2515e107052659c530f02decaba350334dac8ac064042a', '[\"*\"]', '2025-09-22 18:27:31', NULL, '2025-09-20 08:58:02', '2025-09-22 18:27:31'),
(104, 'App\\Models\\User', 2, 'api-token', 'f453e245b9f1e720c17d247aa1560c15e4496623e1956a50485b558009ebd568', '[\"*\"]', NULL, NULL, '2025-09-20 08:59:33', '2025-09-20 08:59:33'),
(105, 'App\\Models\\User', 2, 'api-token', '27049811166f5e0e1bfec0ea73c65c368241254d67d46e9b14b0b4835c863f18', '[\"*\"]', '2025-09-20 11:14:31', NULL, '2025-09-20 09:20:03', '2025-09-20 11:14:31'),
(106, 'App\\Models\\User', 2, 'api-token', '697fdb328da90ba4604cc3c68e2dd81ee014bb7cffbfedbabbf9093d20f0e3d0', '[\"*\"]', '2025-09-21 09:31:29', NULL, '2025-09-20 19:01:34', '2025-09-21 09:31:29'),
(107, 'App\\Models\\User', 2, 'api-token', '90dc0e8f0bf5353f6bf081a0fe236d2ddf1d2dc8f1e71abb7a5c36f49615c8d1', '[\"*\"]', '2025-09-22 19:07:38', NULL, '2025-09-22 18:57:32', '2025-09-22 19:07:38'),
(114, 'App\\Models\\User', 11, 'api-token', 'a17588ec193111807b6d76066ab09f12243514fc3e6c2f8a7dd16f09e3d687ca', '[\"*\"]', NULL, NULL, '2025-09-23 19:52:33', '2025-09-23 19:52:33'),
(118, 'App\\Models\\User', 23, 'api-token', '7f97c5e7c5c140861c4d3a9f7801c728e66d2177101d2cb0bc6d31d9d63073c0', '[\"*\"]', '2025-09-26 13:19:00', NULL, '2025-09-24 19:04:46', '2025-09-26 13:19:00'),
(120, 'App\\Models\\User', 23, 'api-token', '80fdae6c17195c41a2772ca630dfe7d7259a65e7f218f9aa794f97173958a12a', '[\"*\"]', '2025-09-26 05:23:38', NULL, '2025-09-25 18:50:55', '2025-09-26 05:23:38'),
(126, 'App\\Models\\User', 23, 'api-token', 'f1a0934194757b59b61e4e6fb89ca888a98cc3a5e0283ba52cbbe209755f084a', '[\"*\"]', '2025-09-25 19:25:08', NULL, '2025-09-25 19:25:08', '2025-09-25 19:25:08'),
(133, 'App\\Models\\User', 23, 'api-token', 'f772b3f8df96314a04c0d720e6a8fb9466841c006e858b0dba6f33969f80b6a9', '[\"*\"]', '2025-09-26 09:11:08', NULL, '2025-09-26 09:11:08', '2025-09-26 09:11:08'),
(135, 'App\\Models\\User', 23, 'auth_token', 'ec94d0053b29cf1f053bb8e83e82102afd693e19e084472fbea9dd3485acce17', '[\"*\"]', '2025-09-26 16:23:47', NULL, '2025-09-26 16:13:46', '2025-09-26 16:23:47'),
(136, 'App\\Models\\User', 23, 'auth_token', 'de794521d2920f5b8f77e06407d828809caa9265353df46072ad2819728df3c8', '[\"*\"]', '2025-09-27 07:44:21', NULL, '2025-09-26 16:24:13', '2025-09-27 07:44:21'),
(137, 'App\\Models\\User', 23, 'auth_token', '410b61ee3f53cdd4fbd54079d7feb2d66a9ded20e1f13fda3ab5660e6adfa344', '[\"*\"]', '2025-09-26 20:10:28', NULL, '2025-09-26 20:10:19', '2025-09-26 20:10:28'),
(138, 'App\\Models\\User', 23, 'auth_token', 'a57539953cad5a8269a49ed6bcd237489ddae6c031cf1aafe82c78d5120bb0fd', '[\"*\"]', '2025-09-26 20:11:16', NULL, '2025-09-26 20:11:10', '2025-09-26 20:11:16'),
(139, 'App\\Models\\User', 23, 'auth_token', '65c05995b2e2fdb50e45150d190acdd8c5fd2e4bd0c516c7ad638ba9bfe86611', '[\"*\"]', '2025-09-26 20:11:38', NULL, '2025-09-26 20:11:32', '2025-09-26 20:11:38'),
(140, 'App\\Models\\User', 23, 'auth_token', '2889e87378bc654ad762db7c74018b2ed4e67d6f8b53611d6f9e0ea198e9df78', '[\"*\"]', '2025-09-26 21:02:36', NULL, '2025-09-26 21:02:33', '2025-09-26 21:02:36'),
(141, 'App\\Models\\User', 23, 'auth_token', '139b559d96349320467a33567da1cee67f3727a814dd324e944759a130c5abe2', '[\"*\"]', '2025-09-27 03:30:55', NULL, '2025-09-27 03:29:28', '2025-09-27 03:30:55'),
(142, 'App\\Models\\User', 23, 'auth_token', '0478d0a4e588eec6854556233d56d9cbfb2b4b73a8dc4f367ce68922fff89d81', '[\"*\"]', '2025-09-27 03:31:10', NULL, '2025-09-27 03:31:10', '2025-09-27 03:31:10'),
(143, 'App\\Models\\User', 23, 'auth_token', 'a0198543ad4c89c001d4621a3a24e281a964bfea94d533dc7dd85499a306de50', '[\"*\"]', '2025-09-27 03:32:14', NULL, '2025-09-27 03:32:14', '2025-09-27 03:32:14'),
(144, 'App\\Models\\User', 23, 'auth_token', '94e0f07bdff5f9561a2a6edfacb901e9aa3593685c8eb23c17bc7485b9b81a98', '[\"*\"]', '2025-09-27 20:15:41', NULL, '2025-09-27 20:14:53', '2025-09-27 20:15:41'),
(147, 'App\\Models\\User', 23, 'auth_token', 'f98b7fdd242f6f0226d3a437116caac1971101834ff65208669fc125bfec5cdd', '[\"*\"]', '2025-09-29 19:35:45', NULL, '2025-09-29 19:35:08', '2025-09-29 19:35:45'),
(148, 'App\\Models\\User', 23, 'auth_token', '13d4511ba06a875b1702923a83c4606e54e156a8782f5e0a4888f8c92c3251ca', '[\"*\"]', '2025-09-29 19:41:40', NULL, '2025-09-29 19:41:38', '2025-09-29 19:41:40'),
(161, 'App\\Models\\User', 23, 'auth_token', 'cba71f8737cb122c7c7746d6152524c3d4992a878d6bb32a92cd7502d7f69619', '[\"*\"]', '2025-09-30 06:58:08', NULL, '2025-09-30 04:51:39', '2025-09-30 06:58:08'),
(162, 'App\\Models\\User', 23, 'auth_token', '178512916fa0081fb10c921e6926535d454ddd54a8c5051448682e80203c0207', '[\"*\"]', '2025-10-22 09:03:41', NULL, '2025-10-22 07:43:41', '2025-10-22 09:03:41'),
(165, 'App\\Models\\User', 76, 'auth_token', '5ee297a2fc9650adb2e3cabd546d4e9cdbd140a51803c4b5d3fd19e4153bab80', '[\"*\"]', NULL, NULL, '2025-10-26 18:24:11', '2025-10-26 18:24:11'),
(166, 'App\\Models\\User', 76, 'auth_token', '6b2f2373aebec850dd862f9297d7f78888ea82de11034dc0e98252bd5d33d129', '[\"*\"]', NULL, NULL, '2025-10-26 18:24:37', '2025-10-26 18:24:37'),
(168, 'App\\Models\\User', 1, 'auth_token', '5a6eca8aa3e232f3a6ae7c5afa5bf0b70f85593cc10c257bc217e77c21a5471b', '[\"*\"]', NULL, NULL, '2025-10-26 18:26:54', '2025-10-26 18:26:54'),
(169, 'App\\Models\\User', 1, 'auth_token', 'c4154745fad2fe75e4bb5a85e02b3dcd724033e458c7a2035f423fd25479364f', '[\"*\"]', '2025-10-26 18:36:48', NULL, '2025-10-26 18:36:48', '2025-10-26 18:36:48'),
(170, 'App\\Models\\User', 1, 'auth_token', 'c50ea29642e269bc5bddfb9cd47025a7f439bf85e0666d1452f4b6027e581dc7', '[\"*\"]', '2025-10-26 18:42:20', NULL, '2025-10-26 18:40:38', '2025-10-26 18:42:20'),
(171, 'App\\Models\\User', 1, 'auth_token', 'b57621fc1ef45a0c85a1c261da0c460a3d951df3f2708e778f1314cba9717cd8', '[\"*\"]', '2025-10-26 18:43:31', NULL, '2025-10-26 18:43:22', '2025-10-26 18:43:31'),
(173, 'App\\Models\\User', 1, 'auth_token', 'dbc83cf862a86d7d4c855562f3a6efe9aafa42798f9bd195fbafd0a4a8f75c3f', '[\"*\"]', '2025-10-26 18:52:29', NULL, '2025-10-26 18:52:28', '2025-10-26 18:52:29'),
(175, 'App\\Models\\User', 76, 'auth_token', '74fd4127116777ea67f35f5ef955f40b4fe7cca5d99cc0c5c29ce48b752ad3c8', '[\"*\"]', '2025-10-26 19:03:48', NULL, '2025-10-26 18:53:48', '2025-10-26 19:03:48'),
(178, 'App\\Models\\User', 76, 'auth_token', '2efd40aa11938a2ca7f7a682d431746a340b2ded546e04801218f4f24da91572', '[\"*\"]', '2025-10-26 19:13:57', NULL, '2025-10-26 19:03:57', '2025-10-26 19:13:57'),
(181, 'App\\Models\\User', 1, 'auth_token', '0c0467c2e56c64525363d5e0bbe3008bf562878b62fec472151314fa3575dccc', '[\"*\"]', '2025-10-26 19:05:44', NULL, '2025-10-26 19:05:36', '2025-10-26 19:05:44'),
(186, 'App\\Models\\User', 1, 'auth_token', '1eb3c9a3609bca60595b34ce9bf231c046bf936e6f95762b6856d508bd93fdce', '[\"*\"]', '2025-10-26 19:19:30', NULL, '2025-10-26 19:09:29', '2025-10-26 19:19:30'),
(188, 'App\\Models\\User', 76, 'auth_token', 'ee63d8c3e16585e95d73ae9dff3ee53c60b4decebe02a6026c48834b98e4fd84', '[\"*\"]', '2025-10-26 19:54:43', NULL, '2025-10-26 19:54:11', '2025-10-26 19:54:43'),
(189, 'App\\Models\\User', 76, 'auth_token', '62f8181a78a9cc8632fa33e00e5903c7ad07c5d21c96dcf6872be7c958afd316', '[\"*\"]', '2025-10-26 20:15:01', NULL, '2025-10-26 20:05:01', '2025-10-26 20:15:01'),
(190, 'App\\Models\\User', 76, 'auth_token', 'c5ea8d1dfdbf0e6fb1aed906bbc85c5f1ca445a5e1310ea3a561a86f7a0b753e', '[\"*\"]', '2025-10-26 20:23:23', NULL, '2025-10-26 20:23:15', '2025-10-26 20:23:23'),
(191, 'App\\Models\\User', 76, 'auth_token', '7a2480456075027af9749449aff77922340232866c38814f769b4a6e581ba87e', '[\"*\"]', '2025-10-26 20:24:52', NULL, '2025-10-26 20:24:49', '2025-10-26 20:24:52'),
(192, 'App\\Models\\User', 76, 'auth_token', 'd2c6c7404976aeeeb20f0a159340b549cfdaadc1100d35364309b98fc49082f3', '[\"*\"]', '2025-10-26 20:57:31', NULL, '2025-10-26 20:27:29', '2025-10-26 20:57:31'),
(193, 'App\\Models\\User', 76, 'auth_token', '151d8bd01ab650d0c230e43b1fd3ac5d6e7035cf5ab4365535b54a46f40aa2fa', '[\"*\"]', '2025-10-27 19:49:50', NULL, '2025-10-27 19:29:48', '2025-10-27 19:49:50'),
(194, 'App\\Models\\User', 76, 'auth_token', '131688460a1097b144d61335c804eaf862e8ddee2bf0d73bc8a925daceeb4483', '[\"*\"]', '2025-10-27 20:06:51', NULL, '2025-10-27 19:56:51', '2025-10-27 20:06:51'),
(195, 'App\\Models\\User', 76, 'auth_token', '893dee60258d7f68c3e684447497d3851b494708c9b98d526d2fc6a9d99fc3cb', '[\"*\"]', '2025-10-27 20:14:36', NULL, '2025-10-27 20:14:31', '2025-10-27 20:14:36'),
(196, 'App\\Models\\User', 76, 'auth_token', '85d7bcd1d11271577efbab943586c870ead444aef795b70e7ac2485f9f97956c', '[\"*\"]', '2025-10-27 20:19:40', NULL, '2025-10-27 20:19:25', '2025-10-27 20:19:40'),
(197, 'App\\Models\\User', 76, 'auth_token', '1bd738dae9a52cabbb70326d9454c48142ada97384947fd8d8926b2a091b1c47', '[\"*\"]', '2025-10-27 20:24:29', NULL, '2025-10-27 20:23:55', '2025-10-27 20:24:29'),
(198, 'App\\Models\\User', 76, 'auth_token', '8f5d51b430d8539d641ead88e0a8d8b586f297b33e8fba95a76d36167e0779ad', '[\"*\"]', '2025-10-27 20:34:11', NULL, '2025-10-27 20:33:50', '2025-10-27 20:34:11'),
(199, 'App\\Models\\User', 76, 'auth_token', '5bbd36eb1406d4d4cb14ec3741d35a39dc6ab386c3762baaf90682223d4be46b', '[\"*\"]', '2025-10-27 21:02:25', NULL, '2025-10-27 20:42:51', '2025-10-27 21:02:25'),
(200, 'App\\Models\\User', 76, 'auth_token', 'd75ac5e26bd839466d4fa2d079dae1019892fe8d810128b691adf0dc7667c93c', '[\"*\"]', '2025-10-27 21:03:30', NULL, '2025-10-27 21:03:24', '2025-10-27 21:03:30'),
(201, 'App\\Models\\User', 76, 'auth_token', 'f2b4352a622262f896dfa2301bc86616820a9ffb315addc8a0b717228dce5904', '[\"*\"]', '2025-10-27 21:15:30', NULL, '2025-10-27 21:05:29', '2025-10-27 21:15:30'),
(202, 'App\\Models\\User', 76, 'auth_token', '5ecaca03bdf5f5af04350820f8e94861a4169269fd4bc16c0a65bb54db10b669', '[\"*\"]', '2025-10-27 21:31:06', NULL, '2025-10-27 21:21:05', '2025-10-27 21:31:06'),
(203, 'App\\Models\\User', 77, 'auth_token', '69cb9082679c6a50478c9a472fff4e2fd071e0da8349a5ed234abfe01829a08f', '[\"*\"]', '2025-10-27 21:41:27', NULL, '2025-10-27 21:40:56', '2025-10-27 21:41:27'),
(204, 'App\\Models\\User', 76, 'auth_token', 'c84694ed4376b3236f3a980bc12c65dd37f2d730f4677fbfa26196baabbfdc40', '[\"*\"]', '2025-10-28 20:38:23', NULL, '2025-10-28 20:20:20', '2025-10-28 20:38:23'),
(205, 'App\\Models\\User', 76, 'auth_token', '44c2174bb16274973f16fad86792792c664e4c0c18fd403612fe8d420b0437e5', '[\"*\"]', '2025-10-28 20:39:19', NULL, '2025-10-28 20:38:59', '2025-10-28 20:39:19'),
(206, 'App\\Models\\User', 76, 'auth_token', 'd3f275288c333f352e6be7a651b1e54af5ec0c49b900f3980ace542caa7b7dce', '[\"*\"]', '2025-10-28 20:39:32', NULL, '2025-10-28 20:39:28', '2025-10-28 20:39:32'),
(207, 'App\\Models\\User', 76, 'auth_token', '4517b438cf2587fa6a3941d3048c6c63709a539b8c57657cd2b10db0c36c0e42', '[\"*\"]', '2025-10-28 22:11:08', NULL, '2025-10-28 22:01:06', '2025-10-28 22:11:08'),
(208, 'App\\Models\\User', 76, 'auth_token', 'bd07305f1fb1a943ea6718e39fcd556c4eaae362027efc8b071f51e4be844fae', '[\"*\"]', '2025-10-29 08:34:34', NULL, '2025-10-28 22:13:23', '2025-10-29 08:34:34'),
(209, 'App\\Models\\User', 76, 'auth_token', '2fcae4d5edd4fa7979b899cfdef1a7fd96b1af3880707ea3034824ac760c1a5a', '[\"*\"]', '2025-11-02 23:14:59', NULL, '2025-10-28 22:16:51', '2025-11-02 23:14:59'),
(210, 'App\\Models\\User', 76, 'auth_token', 'e4a434f9bae14ef230371e237e7b5db489a28d4cc81b744b2132689f67128ae2', '[\"*\"]', '2025-10-29 19:43:18', NULL, '2025-10-29 19:43:16', '2025-10-29 19:43:18'),
(211, 'App\\Models\\User', 76, 'auth_token', 'add96fe1ab0d40a7a7e22336624aa0e53a378b4d1e2d3db85a987a3091e92b77', '[\"*\"]', NULL, NULL, '2025-10-29 19:54:02', '2025-10-29 19:54:02'),
(212, 'App\\Models\\User', 76, 'auth_token', '5cca3d281cdedf8c6bdb1154b7ca27188064c8177559d40b0381185a80cad04c', '[\"*\"]', '2025-10-29 19:55:02', NULL, '2025-10-29 19:54:12', '2025-10-29 19:55:02'),
(213, 'App\\Models\\User', 76, 'auth_token', '29d5743ff7c0bb387292c73cb416cc077c437188acc240745507482f7bb2d1e2', '[\"*\"]', '2025-10-30 20:26:59', NULL, '2025-10-29 20:35:43', '2025-10-30 20:26:59'),
(214, 'App\\Models\\User', 78, 'auth_token', '01d76139a020bb815600d73dd9c41a4bbfe0dcd9b3017c04218da45268d32be7', '[\"*\"]', '2025-10-30 18:30:34', NULL, '2025-10-30 18:30:33', '2025-10-30 18:30:34'),
(216, 'App\\Models\\User', 76, 'auth_token', '5a7cd21556c6cbd1008950e71cccf04ef4ce3574122a99114b6691ba0474501e', '[\"*\"]', '2025-10-30 21:32:49', NULL, '2025-10-30 20:56:55', '2025-10-30 21:32:49'),
(219, 'App\\Models\\User', 76, 'auth_token', '49942e19d59dc3b5608952e843f4f00a66f0286c391803ca68661cadbdc335b3', '[\"*\"]', '2025-11-02 20:59:55', NULL, '2025-11-02 11:00:13', '2025-11-02 20:59:55'),
(220, 'App\\Models\\User', 83, 'auth_token', 'c3783e58f21e575c82553dac3098a2fd1afd08abd6d33e6cc5df32bbbef2181f', '[\"*\"]', '2025-11-02 11:18:28', NULL, '2025-11-02 11:16:20', '2025-11-02 11:18:28'),
(221, 'App\\Models\\User', 76, 'auth_token', '4cea166b26beacf412db3baf270f67aae488c665b2ccd484fb292ac3b818b984', '[\"*\"]', '2025-11-02 11:38:47', NULL, '2025-11-02 11:16:59', '2025-11-02 11:38:47'),
(222, 'App\\Models\\User', 77, 'auth_token', '51d2074c49e6f8742e56c4e7fe4a61790fa361a9ce9fdc35a03cd92d657640b2', '[\"*\"]', '2025-11-02 19:17:33', NULL, '2025-11-02 11:20:45', '2025-11-02 19:17:33'),
(223, 'App\\Models\\User', 2, 'auth_token', '893a07bc5cb2ce0484d4aac0cec75c392124d473d57d3b8f00e6d92c8ae16a41', '[\"*\"]', '2025-11-02 11:56:00', NULL, '2025-11-02 11:22:56', '2025-11-02 11:56:00'),
(224, 'App\\Models\\User', 82, 'auth_token', '1b19b84420654ca5a120d7de2b12154101887dac67de0fb31df49d17ec30510f', '[\"*\"]', '2025-11-02 11:36:16', NULL, '2025-11-02 11:35:58', '2025-11-02 11:36:16'),
(225, 'App\\Models\\User', 81, 'auth_token', 'b0449ab58ce3b920b776ecdd9cf53339ff3a50a3fd4730f5281883d63c8e6ab4', '[\"*\"]', '2025-11-02 16:24:05', NULL, '2025-11-02 16:23:43', '2025-11-02 16:24:05'),
(226, 'App\\Models\\User', 77, 'auth_token', '24175e27c42a7915afb3a05e621df08a03f777459d6ef915148599b4e2ecd157', '[\"*\"]', '2025-11-03 08:14:16', NULL, '2025-11-02 19:18:02', '2025-11-03 08:14:16'),
(228, 'App\\Models\\User', 76, 'auth_token', '0a2fdcfe8e4edcb427fa4471a565691004d03a9183fade9d54135d4b668676a2', '[\"*\"]', '2025-11-03 17:07:17', NULL, '2025-11-02 21:58:53', '2025-11-03 17:07:17'),
(229, 'App\\Models\\User', 80, 'auth_token', '7d86bd71b8c55ee0ee6a10dbe00f99d7446dca2537631815f92a16732132e4c8', '[\"*\"]', '2025-11-02 22:25:32', NULL, '2025-11-02 22:25:18', '2025-11-02 22:25:32'),
(230, 'App\\Models\\User', 76, 'auth_token', 'e998d136e072e25c3831f83e2c2bdbef39700eb88925ea132dd3b4f1f2755366', '[\"*\"]', '2025-11-03 16:31:33', NULL, '2025-11-03 07:38:32', '2025-11-03 16:31:33'),
(231, 'App\\Models\\User', 76, 'auth_token', '9b552817e28d763d9b16d190b963d184dce9cc1aaf6c363691a9bb95944ffd1e', '[\"*\"]', '2025-11-03 21:03:03', NULL, '2025-11-03 17:10:44', '2025-11-03 21:03:03'),
(232, 'App\\Models\\User', 76, 'auth_token', 'a14c333b2949911122187ad3901c835f196ecc41e78b511003431773d9f6babe', '[\"*\"]', '2025-11-03 21:56:10', NULL, '2025-11-03 21:35:16', '2025-11-03 21:56:10'),
(233, 'App\\Models\\User', 76, 'auth_token', '865827f3d7d5e16a9f5abd96e6a52b1689e9fb78960200bbc5abaec483cbd0f3', '[\"*\"]', '2025-11-03 21:54:56', NULL, '2025-11-03 21:54:29', '2025-11-03 21:54:56');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(8,2) NOT NULL,
  `cost_price` decimal(8,2) DEFAULT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `min_stock` int NOT NULL DEFAULT '5',
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#6B7280',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `profiles`
--

CREATE TABLE `profiles` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'France',
  `emergency_contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medical_notes` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `preferences` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `date_of_birth`, `gender`, `address`, `city`, `postal_code`, `country`, `emergency_contact_name`, `emergency_contact_phone`, `medical_notes`, `avatar`, `bio`, `preferences`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Admin', 'Système', '+32 2 123 45 67', '1980-01-01', NULL, 'Rue de l\'Administration 1, 1000 Bruxelles', NULL, NULL, 'France', 'Support Technique', '+32 2 123 45 68', NULL, NULL, NULL, NULL, '2025-09-14 16:48:22', '2025-09-14 16:48:22', NULL),
(11, 20, 'Administrateur', 'Supplémentaire', '+32 2 123 45 67', NULL, NULL, NULL, NULL, NULL, 'France', NULL, NULL, NULL, NULL, 'Administrateur de la plateforme activibe', '{\"email_updates\": true, \"notifications\": true, \"admin_dashboard\": true}', '2025-09-15 15:41:00', '2025-09-15 15:41:00', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `skills`
--

CREATE TABLE `skills` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('technical','pedagogical','management','communication','technology') COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_type_id` bigint UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `levels` json DEFAULT NULL,
  `requirements` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `students`
--

CREATE TABLE `students` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `club_id` bigint UNSIGNED DEFAULT NULL,
  `level` enum('debutant','intermediaire','avance','expert') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `goals` text COLLATE utf8mb4_unicode_ci,
  `preferred_disciplines` json DEFAULT NULL,
  `preferred_levels` json DEFAULT NULL,
  `preferred_formats` json DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `max_distance` int DEFAULT NULL,
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `medical_info` text COLLATE utf8mb4_unicode_ci,
  `preferences` json DEFAULT NULL,
  `total_lessons` int NOT NULL DEFAULT '0',
  `total_spent` decimal(10,2) NOT NULL DEFAULT '0.00',
  `emergency_contacts` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `students`
--

INSERT INTO `students` (`id`, `user_id`, `first_name`, `last_name`, `date_of_birth`, `club_id`, `level`, `goals`, `preferred_disciplines`, `preferred_levels`, `preferred_formats`, `location`, `max_price`, `max_distance`, `notifications_enabled`, `medical_info`, `preferences`, `total_lessons`, `total_spent`, `emergency_contacts`, `created_at`, `updated_at`, `deleted_at`) VALUES
(53, NULL, NULL, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-02 22:14:47', '2025-11-02 22:14:47', NULL),
(54, 85, NULL, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-02 23:14:32', '2025-11-02 23:14:32', NULL),
(55, NULL, 'Raphael', 'Marcelli', NULL, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 14:53:48', '2025-11-03 14:53:48', NULL),
(56, NULL, 'Mattia', 'Cassano', NULL, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 14:56:06', '2025-11-03 14:56:06', NULL),
(57, NULL, 'Emy', 'Zapico', '2022-02-26', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 14:57:48', '2025-11-03 14:57:48', NULL),
(58, NULL, 'Amelia', 'Borodan', '2021-06-13', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 14:58:56', '2025-11-03 14:58:56', NULL),
(59, NULL, 'Tom', 'Broucke', '2020-08-23', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 14:59:48', '2025-11-03 14:59:48', NULL),
(60, NULL, 'Louisa', 'Broucke', '2023-03-18', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:00:30', '2025-11-03 15:00:30', NULL),
(61, NULL, 'Luis', 'Frassanito', '2022-05-05', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:03:28', '2025-11-03 15:03:28', NULL),
(62, NULL, 'Malone', 'Kinet', '2011-11-12', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:06:21', '2025-11-03 15:06:21', NULL),
(63, NULL, 'Chloé', 'Vaguener', '2019-01-15', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:07:18', '2025-11-03 15:07:18', NULL),
(64, NULL, 'Maxime', 'Noiret', '2022-08-01', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:09:06', '2025-11-03 15:09:06', NULL),
(65, NULL, 'Timeo', 'Alba', NULL, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:10:17', '2025-11-03 15:10:17', NULL),
(66, NULL, 'Andreï', 'Ghinet', '2017-11-19', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:14:41', '2025-11-03 15:14:41', NULL),
(67, NULL, 'Bjorn', 'Canivet', '2021-06-10', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:17:05', '2025-11-03 15:17:05', NULL),
(68, NULL, 'Lyzea', 'Diels', NULL, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:21:58', '2025-11-03 15:21:58', NULL),
(69, NULL, 'Tom', 'Smidts', NULL, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:25:15', '2025-11-03 15:25:15', NULL),
(70, NULL, 'Alba', 'Di Franco', '2022-05-15', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:27:26', '2025-11-03 15:27:26', NULL),
(71, NULL, 'Gioia', 'Di Franco', '2020-09-01', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:28:06', '2025-11-03 15:28:06', NULL),
(72, NULL, 'Jaad', 'Lagmani', '2022-11-29', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:28:53', '2025-11-03 15:28:53', NULL),
(73, NULL, 'Adonis', 'Lavency', '2021-02-25', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:29:37', '2025-11-03 15:29:37', NULL),
(74, NULL, 'Kenlia', 'De Luca', '2020-04-25', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:30:18', '2025-11-03 15:30:18', NULL),
(75, NULL, 'Iliano', 'De Luca', '2021-07-31', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:30:51', '2025-11-03 15:30:51', NULL),
(76, NULL, 'Ilena', 'Reynaerts', '2021-10-05', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:31:30', '2025-11-03 15:31:30', NULL),
(77, NULL, 'Leon', 'Rouffange', '2017-11-02', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 15:32:00', '2025-11-03 15:32:00', NULL),
(78, NULL, 'Vasco', 'Gambirasio', '2016-07-21', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 0.00, NULL, '2025-11-03 16:31:33', '2025-11-03 16:31:33', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `student_disciplines`
--

CREATE TABLE `student_disciplines` (
  `id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `discipline_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `student_medical_documents`
--

CREATE TABLE `student_medical_documents` (
  `id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `document_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `renewal_frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `student_preferences`
--

CREATE TABLE `student_preferences` (
  `id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `discipline_id` bigint UNSIGNED NOT NULL,
  `course_type_id` bigint UNSIGNED NOT NULL,
  `is_preferred` tinyint(1) NOT NULL DEFAULT '0',
  `priority_level` int NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `validity_months` int DEFAULT NULL,
  `subscription_template_id` bigint UNSIGNED DEFAULT NULL,
  `subscription_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `created_at`, `updated_at`, `validity_months`, `subscription_template_id`, `subscription_number`) VALUES
(1, '2025-11-03 20:25:56', '2025-11-03 20:25:56', NULL, 1, '2511-001');

-- --------------------------------------------------------

--
-- Structure de la table `subscription_course_types`
--

CREATE TABLE `subscription_course_types` (
  `id` bigint UNSIGNED NOT NULL,
  `subscription_id` bigint UNSIGNED NOT NULL,
  `course_type_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subscription_instances`
--

CREATE TABLE `subscription_instances` (
  `id` bigint UNSIGNED NOT NULL,
  `subscription_id` bigint UNSIGNED NOT NULL,
  `lessons_used` int NOT NULL DEFAULT '0',
  `started_at` date NOT NULL,
  `expires_at` date DEFAULT NULL,
  `status` enum('active','completed','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `subscription_instances`
--

INSERT INTO `subscription_instances` (`id`, `subscription_id`, `lessons_used`, `started_at`, `expires_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 0, '2025-11-03', '2026-03-03', 'active', '2025-11-03 20:25:56', '2025-11-03 20:25:56');

-- --------------------------------------------------------

--
-- Structure de la table `subscription_instance_students`
--

CREATE TABLE `subscription_instance_students` (
  `id` bigint UNSIGNED NOT NULL,
  `subscription_instance_id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `subscription_instance_students`
--

INSERT INTO `subscription_instance_students` (`id`, `subscription_instance_id`, `student_id`, `created_at`, `updated_at`) VALUES
(1, 1, 54, '2025-11-03 20:25:56', '2025-11-03 20:25:56');

-- --------------------------------------------------------

--
-- Structure de la table `subscription_lessons`
--

CREATE TABLE `subscription_lessons` (
  `id` bigint UNSIGNED NOT NULL,
  `subscription_instance_id` bigint UNSIGNED NOT NULL,
  `lesson_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subscription_recurring_slots`
--

CREATE TABLE `subscription_recurring_slots` (
  `id` bigint UNSIGNED NOT NULL,
  `subscription_instance_id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NOT NULL,
  `student_id` bigint UNSIGNED NOT NULL,
  `day_of_week` tinyint UNSIGNED NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subscription_templates`
--

CREATE TABLE `subscription_templates` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `model_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_lessons` int NOT NULL,
  `free_lessons` int NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL,
  `validity_months` int NOT NULL DEFAULT '12',
  `validity_value` int DEFAULT NULL,
  `validity_unit` enum('weeks','months') COLLATE utf8mb4_unicode_ci DEFAULT 'months',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `subscription_templates`
--

INSERT INTO `subscription_templates` (`id`, `club_id`, `model_number`, `total_lessons`, `free_lessons`, `price`, `validity_months`, `validity_value`, `validity_unit`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 11, 'MOD-01-Natation - Cours standard', 10, 1, 180.00, 4, 15, 'weeks', 1, '2025-11-02 22:14:09', '2025-11-02 22:14:09');

-- --------------------------------------------------------

--
-- Structure de la table `subscription_template_course_types`
--

CREATE TABLE `subscription_template_course_types` (
  `id` bigint UNSIGNED NOT NULL,
  `subscription_template_id` bigint UNSIGNED NOT NULL,
  `course_type_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `subscription_template_course_types`
--

INSERT INTO `subscription_template_course_types` (`id`, `subscription_template_id`, `course_type_id`, `created_at`, `updated_at`) VALUES
(2, 1, 5, '2025-11-03 20:28:27', '2025-11-03 20:28:27');

-- --------------------------------------------------------

--
-- Structure de la table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED DEFAULT NULL,
  `specialties` json DEFAULT NULL,
  `experience_years` int NOT NULL DEFAULT '0',
  `certifications` json DEFAULT NULL,
  `hourly_rate` decimal(8,2) NOT NULL DEFAULT '0.00',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `max_travel_distance` int NOT NULL DEFAULT '50',
  `preferred_locations` json DEFAULT NULL,
  `stripe_account_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_lessons` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `club_id`, `specialties`, `experience_years`, `certifications`, `hourly_rate`, `bio`, `is_available`, `max_travel_distance`, `preferred_locations`, `stripe_account_id`, `rating`, `total_lessons`, `created_at`, `updated_at`, `deleted_at`) VALUES
(16, 77, NULL, '\"[]\"', 0, NULL, 24.00, NULL, 1, 50, NULL, NULL, 0.00, 0, '2025-10-27 19:59:08', '2025-10-27 19:59:08', NULL),
(17, 78, NULL, '\"[]\"', 0, NULL, 24.00, NULL, 1, 50, NULL, NULL, 0.00, 0, '2025-10-30 18:25:55', '2025-10-30 18:25:55', NULL),
(18, 79, NULL, '\"[]\"', 0, NULL, 24.00, NULL, 1, 50, NULL, NULL, 0.00, 0, '2025-10-30 18:30:13', '2025-10-30 18:30:13', NULL),
(19, 80, NULL, '\"[]\"', 0, NULL, 23.00, NULL, 1, 50, NULL, NULL, 0.00, 0, '2025-11-02 11:05:32', '2025-11-02 11:05:32', NULL),
(20, 81, NULL, '\"[]\"', 0, NULL, 18.00, NULL, 1, 50, NULL, NULL, 0.00, 0, '2025-11-02 11:08:04', '2025-11-02 11:08:04', NULL),
(21, 82, NULL, '\"[]\"', 0, NULL, 23.00, NULL, 1, 50, NULL, NULL, 0.00, 0, '2025-11-02 11:10:08', '2025-11-02 11:10:08', NULL),
(22, 83, NULL, '\"[]\"', 0, NULL, 24.00, NULL, 1, 50, NULL, NULL, 0.00, 0, '2025-11-02 11:11:00', '2025-11-02 11:11:00', NULL),
(23, 84, NULL, '\"[]\"', 0, NULL, 24.00, NULL, 1, 50, NULL, NULL, 0.00, 0, '2025-11-02 11:13:16', '2025-11-02 11:13:16', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `teacher_certifications`
--

CREATE TABLE `teacher_certifications` (
  `id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NOT NULL,
  `certification_id` bigint UNSIGNED NOT NULL,
  `obtained_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `certificate_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issuing_authority` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_valid` tinyint(1) NOT NULL DEFAULT '1',
  `renewal_required` tinyint(1) NOT NULL DEFAULT '0',
  `renewal_reminder_date` date DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_by` bigint UNSIGNED DEFAULT NULL,
  `verified_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_disciplines`
--

CREATE TABLE `teacher_disciplines` (
  `id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NOT NULL,
  `discipline_id` bigint UNSIGNED NOT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'intermediate',
  `certifications` text COLLATE utf8mb4_unicode_ci,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_skills`
--

CREATE TABLE `teacher_skills` (
  `id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NOT NULL,
  `skill_id` bigint UNSIGNED NOT NULL,
  `level` enum('beginner','intermediate','advanced','expert','master') COLLATE utf8mb4_unicode_ci NOT NULL,
  `experience_years` int NOT NULL DEFAULT '0',
  `acquired_date` date DEFAULT NULL,
  `last_practiced` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `evidence` json DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_by` bigint UNSIGNED DEFAULT NULL,
  `verified_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `time_blocks`
--

CREATE TABLE `time_blocks` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `cash_register_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `type` enum('sale','refund','expense','deposit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','transfer','check','multiple') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `processed_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transaction_items`
--

CREATE TABLE `transaction_items` (
  `id` bigint UNSIGNED NOT NULL,
  `transaction_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(8,2) NOT NULL,
  `total_price` decimal(8,2) NOT NULL,
  `discount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `qr_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code_generated_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` enum('admin','teacher','student','club') COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_box` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Belgium',
  `birth_date` date DEFAULT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `first_name`, `last_name`, `email`, `email_verified_at`, `qr_code`, `qr_code_generated_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`, `phone`, `address`, `street`, `street_number`, `street_box`, `postal_code`, `city`, `country`, `birth_date`, `status`, `is_active`) VALUES
(1, 'Administrateur', NULL, NULL, 'admin@activibe.com', '2025-09-14 16:48:22', NULL, NULL, '$2y$12$UX6V5rZfXLCkeOgV3zpA/..7rWdii2SSaTtyoJl7HKbmqsTs4rUWu', NULL, '2025-09-14 16:48:22', '2025-09-14 16:48:22', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(20, 'Administrateur Supplémentaire', NULL, NULL, 'admin2@activibe.com', NULL, NULL, NULL, '$2y$12$uFBkcDG9sC1OypTmNemVzePb282RzSpO0OG6StWKd7Kvl5xMAN41q', NULL, '2025-09-15 15:41:00', '2025-09-15 15:41:00', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(21, 'Olivier LEGRAND', 'Olivier', 'LEGRAND', 'o.legrand1976@gmail.com', NULL, NULL, NULL, '$2y$12$QPiqXgJb8vI0Ovv5ct7D5.WRNTE2OzRHsq7rj4K92I9lzSfBoqGTm', NULL, '2025-09-17 11:39:15', '2025-09-17 11:39:15', 'teacher', '0478031906', NULL, 'Rue de la Résistance', '92 / A', NULL, '7131', 'Waudrez', 'Belgium', '1976-01-10', 'active', 1),
(76, 'Barbara MURGO', 'Barbara', 'MURGO', 'b.murgo1976@gmail.com', NULL, NULL, NULL, '$2y$12$S0t9/UMmqgnWuisyd8F4Y.v5Q25BCgbjgssq6dHAKUPmkgAp/OMNS', NULL, '2025-10-26 18:24:11', '2025-10-26 19:08:54', 'club', '0478023377', NULL, 'Rue de la Résistance,', '92 / A', NULL, '7131', 'Waudrez', 'Belgium', '1976-11-28', 'active', 1),
(77, 'Elena LEGRAND', 'Elena', 'LEGRAND', 'elena200309@gmail.com', NULL, NULL, NULL, '$2y$12$tUw.tfPIGgfvsBBEjkE8TuQGVw04AD6QWh0fI5sz976SYiL.TDcQO', 'QD7NryWVrIxpwrMaPtRI9uuwIGYtW5Pr733ieANL0Q55LKDkhGYrlqtPqGle', '2025-10-27 19:59:08', '2025-10-27 21:40:35', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(78, 'Jimmy FEINCOEUR', 'Jimmy', 'FEINCOEUR', 'j.feincoeur02@gmail.com', NULL, NULL, NULL, '$2y$12$cUY1z7GxoTzaKGVX2fi6T.CQbzIRA6rO4mzL8sJ7JSHa9.sxjgO4q', 'gxbSxqdvamTHv5KhFRpB9BR6CbZdfD47jTqc6eK2ZsAdv2n4DGr6eilYn03a', '2025-10-30 18:25:55', '2025-10-30 18:30:28', 'teacher', '0491306821', NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(79, 'Alicia LAPAGLIA', 'Alicia', 'LAPAGLIA', 'a.lapaglia16@gmail.com', NULL, NULL, NULL, '$2y$12$B3ASw7VU7VydjhkWT8uGpuRNxxxaD5jrf.1tMJSNuLV4iDasmHjDC', NULL, '2025-10-30 18:30:13', '2025-10-30 18:30:13', 'teacher', '0478016681', NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(80, 'Zakaria BAIL', 'Zakaria', 'BAIL', 'zakariabaik041@gmail.com', NULL, NULL, NULL, '$2y$12$aZMqFmUdyPtNeKBbdMibP.fr/egiTPw49PHw5KQ1nW//9qQy0ZONG', 's6WRToS2NWoeST28RHSBbso8XKIkSyIk5XRNSXsbDP8X1WLG7etrRS1X5LlH', '2025-11-02 11:05:32', '2025-11-02 22:25:05', 'teacher', '0489777183', NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(81, 'Estelle FURIA', 'Estelle', 'FURIA', 'f.estelle15@icloud.com', NULL, NULL, NULL, '$2y$12$OHB8l2QNzXWgwaWkrMYSweNgRi2.KbKvFzmTQjSVtxCFxetGF4Km6', 'srAI50kONQabcWNKe91qshcGISsUJWKcDUGFThbZyJs8qHKiw6MPYakf45Xd', '2025-11-02 11:08:04', '2025-11-02 16:23:32', 'teacher', '0497948976', NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(82, 'Louis WINDELS', 'Louis', 'WINDELS', 'louis.windels@gmail.com', NULL, NULL, NULL, '$2y$12$KXMqTWL0Y4xMeyinOUf5Wu3KeJ7qrWxEX8UgyA6CE7.4R8Nl8Y0Im', '9MOOg4ozCjmcbAfgtx8xlCWZGlolQ164fCjcDlp6LA6DdLcospPXTyHJ4MI5', '2025-11-02 11:10:08', '2025-11-02 11:35:50', 'teacher', '0473320590', NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(83, 'Baptiste NAVARRA', 'Baptiste', 'NAVARRA', 'batnav486@gmail.com', NULL, NULL, NULL, '$2y$12$gwRwMpwOwj4wsBDuxasNUuGlbV642jpFBG8t/vOb6MqyF.4aOuh/e', 'cE59Sbwd6EcAm27F13io2i3n25Axgakdv9TXjQFQChgEo9q2dvDTa7PsU6mC', '2025-11-02 11:11:00', '2025-11-02 11:16:01', 'teacher', '0485444401', NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(84, 'Fanny VOGELS', 'Fanny', 'VOGELS', 'fannyvogels@gmail.com', NULL, NULL, NULL, '$2y$12$Fxn8DY89j5iJpPI9edjNK.QnqiB9p9nMulUH6kRHqwusTdkZ5Fd/6', NULL, '2025-11-02 11:13:16', '2025-11-02 11:13:16', 'teacher', '0486034114', NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1),
(85, 'acti vibe', 'acti', 'vibe', 'info@activibe.be', NULL, NULL, NULL, '$2y$12$D6olA69JDRP4tEAgkZIxcOA3DHigDCIDlvFm..dgFXyrvhU72UmbO', NULL, '2025-11-02 23:14:31', '2025-11-02 23:14:31', 'student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Belgium', NULL, 'active', 1);

-- --------------------------------------------------------

--
-- Structure de la table `volunteer_expense_limits`
--

CREATE TABLE `volunteer_expense_limits` (
  `id` bigint UNSIGNED NOT NULL,
  `year` year NOT NULL,
  `daily_amount` decimal(8,2) NOT NULL,
  `yearly_amount` decimal(8,2) NOT NULL,
  `yearly_special_categories` decimal(8,2) DEFAULT NULL,
  `yearly_health_sector` decimal(8,2) DEFAULT NULL,
  `source_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fetched_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `volunteer_expense_limits`
--

INSERT INTO `volunteer_expense_limits` (`id`, `year`, `daily_amount`, `yearly_amount`, `yearly_special_categories`, `yearly_health_sector`, `source_url`, `fetched_at`, `created_at`, `updated_at`) VALUES
(1, '2025', 42.31, 1692.51, 3108.44, NULL, 'https://conseilsuperieurvolontaires.belgium.be/fr/defraiements/plafonds-limites-indexes.htm', '2025-11-02 21:31:14', '2025-11-02 21:31:14', '2025-11-02 21:31:14');

-- --------------------------------------------------------

--
-- Structure de la table `volunteer_letter_sends`
--

CREATE TABLE `volunteer_letter_sends` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NOT NULL,
  `sent_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_types`
--
ALTER TABLE `activity_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `activity_types_slug_unique` (`slug`),
  ADD KEY `activity_types_slug_is_active_index` (`slug`,`is_active`);

--
-- Index pour la table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `app_settings_key_group_unique` (`key`,`group`),
  ADD KEY `app_settings_key_index` (`key`),
  ADD KEY `app_settings_group_index` (`group`);

--
-- Index pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  ADD KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `audit_logs_action_index` (`action`);

--
-- Index pour la table `availabilities`
--
ALTER TABLE `availabilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `availabilities_teacher_id_start_time_index` (`teacher_id`,`start_time`),
  ADD KEY `availabilities_location_id_start_time_index` (`location_id`,`start_time`);

--
-- Index pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bookings_student_id_lesson_id_unique` (`student_id`,`lesson_id`),
  ADD KEY `bookings_student_id_status_index` (`student_id`,`status`),
  ADD KEY `bookings_lesson_id_status_index` (`lesson_id`,`status`);

--
-- Index pour la table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Index pour la table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Index pour la table `cash_registers`
--
ALTER TABLE `cash_registers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cash_registers_club_id_is_active_index` (`club_id`,`is_active`);

--
-- Index pour la table `certifications`
--
ALTER TABLE `certifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certifications_category_is_active_index` (`category`,`is_active`),
  ADD KEY `certifications_activity_type_id_is_active_index` (`activity_type_id`,`is_active`),
  ADD KEY `certifications_issuing_authority_is_active_index` (`issuing_authority`,`is_active`);

--
-- Index pour la table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clubs_email_unique` (`email`),
  ADD UNIQUE KEY `clubs_qr_code_unique` (`qr_code`),
  ADD KEY `clubs_city_is_active_index` (`city`,`is_active`),
  ADD KEY `clubs_is_active_index` (`is_active`),
  ADD KEY `clubs_activity_type_id_foreign` (`activity_type_id`);

--
-- Index pour la table `club_activity_types`
--
ALTER TABLE `club_activity_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_activity_types_club_id_activity_type_id_unique` (`club_id`,`activity_type_id`),
  ADD KEY `club_activity_types_activity_type_id_foreign` (`activity_type_id`);

--
-- Index pour la table `club_custom_specialties`
--
ALTER TABLE `club_custom_specialties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_custom_specialties_activity_type_id_foreign` (`activity_type_id`),
  ADD KEY `club_custom_specialties_club_id_activity_type_id_index` (`club_id`,`activity_type_id`);

--
-- Index pour la table `club_disciplines`
--
ALTER TABLE `club_disciplines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_disciplines_club_id_discipline_id_unique` (`club_id`,`discipline_id`),
  ADD KEY `club_disciplines_discipline_id_foreign` (`discipline_id`);

--
-- Index pour la table `club_managers`
--
ALTER TABLE `club_managers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_managers_club_id_user_id_unique` (`club_id`,`user_id`),
  ADD KEY `club_managers_user_id_foreign` (`user_id`);

--
-- Index pour la table `club_open_slots`
--
ALTER TABLE `club_open_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_open_slots_club_id_day_of_week_index` (`club_id`,`day_of_week`);

--
-- Index pour la table `club_open_slot_course_types`
--
ALTER TABLE `club_open_slot_course_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slot_course_type_unique` (`club_open_slot_id`,`course_type_id`),
  ADD KEY `club_open_slot_course_types_course_type_id_foreign` (`course_type_id`),
  ADD KEY `slot_course_type_idx` (`club_open_slot_id`,`course_type_id`);

--
-- Index pour la table `club_settings`
--
ALTER TABLE `club_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_settings_club_id_feature_key_unique` (`club_id`,`feature_key`),
  ADD KEY `club_settings_club_id_feature_category_index` (`club_id`,`feature_category`),
  ADD KEY `club_settings_feature_category_is_enabled_index` (`feature_category`,`is_enabled`),
  ADD KEY `club_settings_feature_key_index` (`feature_key`);

--
-- Index pour la table `club_students`
--
ALTER TABLE `club_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_students_club_id_student_id_unique` (`club_id`,`student_id`),
  ADD KEY `club_students_club_id_is_active_index` (`club_id`,`is_active`),
  ADD KEY `club_students_student_id_is_active_index` (`student_id`,`is_active`);

--
-- Index pour la table `club_teachers`
--
ALTER TABLE `club_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_teachers_club_id_teacher_id_unique` (`club_id`,`teacher_id`),
  ADD KEY `club_teachers_club_id_is_active_index` (`club_id`,`is_active`),
  ADD KEY `club_teachers_teacher_id_is_active_index` (`teacher_id`,`is_active`);

--
-- Index pour la table `club_user`
--
ALTER TABLE `club_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_user_club_id_user_id_unique` (`club_id`,`user_id`),
  ADD KEY `club_user_user_id_foreign` (`user_id`),
  ADD KEY `club_user_club_id_role_index` (`club_id`,`role`);

--
-- Index pour la table `course_types`
--
ALTER TABLE `course_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_types_discipline_id_is_active_index` (`discipline_id`,`is_active`),
  ADD KEY `course_types_is_individual_index` (`is_individual`),
  ADD KEY `course_types_club_discipline_active_idx` (`club_id`,`discipline_id`,`is_active`);

--
-- Index pour la table `disciplines`
--
ALTER TABLE `disciplines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `disciplines_slug_unique` (`slug`),
  ADD KEY `disciplines_is_active_index` (`is_active`),
  ADD KEY `disciplines_activity_type_id_is_active_index` (`activity_type_id`,`is_active`),
  ADD KEY `disciplines_slug_is_active_index` (`slug`,`is_active`);

--
-- Index pour la table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facilities_activity_type_id_is_active_index` (`activity_type_id`,`is_active`),
  ADD KEY `facilities_type_is_active_index` (`type`,`is_active`);

--
-- Index pour la table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Index pour la table `google_calendar_tokens`
--
ALTER TABLE `google_calendar_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `google_calendar_tokens_user_id_unique` (`user_id`);

--
-- Index pour la table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Index pour la table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lessons_course_type_id_foreign` (`course_type_id`),
  ADD KEY `lessons_location_id_foreign` (`location_id`),
  ADD KEY `lessons_teacher_id_start_time_index` (`teacher_id`,`start_time`),
  ADD KEY `lessons_student_id_start_time_index` (`student_id`,`start_time`),
  ADD KEY `lessons_status_index` (`status`),
  ADD KEY `lessons_club_id_index` (`club_id`),
  ADD KEY `lessons_club_id_start_time_index` (`club_id`,`start_time`),
  ADD KEY `lessons_teacher_status_start_idx` (`teacher_id`,`status`,`start_time`),
  ADD KEY `lessons_start_time_idx` (`start_time`),
  ADD KEY `lessons_status_start_time_idx` (`status`,`start_time`),
  ADD KEY `lessons_student_status_start_idx` (`student_id`,`status`,`start_time`),
  ADD KEY `lessons_payment_status_idx` (`payment_status`);

--
-- Index pour la table `lesson_replacements`
--
ALTER TABLE `lesson_replacements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_replacements_lesson_id_index` (`lesson_id`),
  ADD KEY `lesson_replacements_original_teacher_id_index` (`original_teacher_id`),
  ADD KEY `lesson_replacements_replacement_teacher_id_index` (`replacement_teacher_id`),
  ADD KEY `lesson_replacements_status_index` (`status`);

--
-- Index pour la table `lesson_student`
--
ALTER TABLE `lesson_student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lesson_student_lesson_id_student_id_unique` (`lesson_id`,`student_id`),
  ADD KEY `lesson_student_lesson_id_status_index` (`lesson_id`,`status`),
  ADD KEY `lesson_student_student_id_status_index` (`student_id`,`status`);

--
-- Index pour la table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_read_index` (`user_id`,`read`),
  ADD KEY `notifications_created_at_index` (`created_at`);

--
-- Index pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_lesson_id_foreign` (`lesson_id`),
  ADD KEY `payments_student_id_foreign` (`student_id`);

--
-- Index pour la table `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_club_id_is_active_index` (`club_id`,`is_active`),
  ADD KEY `products_category_id_is_active_index` (`category_id`,`is_active`),
  ADD KEY `products_sku_index` (`sku`),
  ADD KEY `products_barcode_index` (`barcode`);

--
-- Index pour la table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_categories_slug_unique` (`slug`),
  ADD KEY `product_categories_slug_is_active_index` (`slug`,`is_active`);

--
-- Index pour la table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profiles_user_id_index` (`user_id`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Index pour la table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `skills_category_is_active_index` (`category`,`is_active`),
  ADD KEY `skills_activity_type_id_is_active_index` (`activity_type_id`,`is_active`);

--
-- Index pour la table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `students_user_id_index` (`user_id`),
  ADD KEY `students_club_id_index` (`club_id`);

--
-- Index pour la table `student_disciplines`
--
ALTER TABLE `student_disciplines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_disciplines_student_id_discipline_id_unique` (`student_id`,`discipline_id`),
  ADD KEY `student_disciplines_discipline_id_foreign` (`discipline_id`);

--
-- Index pour la table `student_medical_documents`
--
ALTER TABLE `student_medical_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_medical_documents_student_id_foreign` (`student_id`);

--
-- Index pour la table `student_preferences`
--
ALTER TABLE `student_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_prefs_unique` (`student_id`,`discipline_id`,`course_type_id`),
  ADD KEY `student_preferences_student_id_is_preferred_index` (`student_id`,`is_preferred`),
  ADD KEY `student_preferences_discipline_id_is_preferred_index` (`discipline_id`,`is_preferred`),
  ADD KEY `student_preferences_course_type_id_is_preferred_index` (`course_type_id`,`is_preferred`);

--
-- Index pour la table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscriptions_subscription_number_unique` (`subscription_number`),
  ADD KEY `sub_template_fk` (`subscription_template_id`);

--
-- Index pour la table `subscription_course_types`
--
ALTER TABLE `subscription_course_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sub_course_types_unique` (`subscription_id`,`course_type_id`),
  ADD KEY `subscription_course_types_new_course_type_id_foreign` (`course_type_id`);

--
-- Index pour la table `subscription_instances`
--
ALTER TABLE `subscription_instances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_instances_subscription_id_status_index` (`subscription_id`,`status`);

--
-- Index pour la table `subscription_instance_students`
--
ALTER TABLE `subscription_instance_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sub_instance_student_unique` (`subscription_instance_id`,`student_id`),
  ADD KEY `subscription_instance_students_student_id_index` (`student_id`),
  ADD KEY `subscription_instance_students_subscription_instance_id_index` (`subscription_instance_id`);

--
-- Index pour la table `subscription_lessons`
--
ALTER TABLE `subscription_lessons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscription_lessons_lesson_id_unique` (`lesson_id`),
  ADD KEY `subscription_lessons_subscription_instance_id_foreign` (`subscription_instance_id`);

--
-- Index pour la table `subscription_recurring_slots`
--
ALTER TABLE `subscription_recurring_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_recurring_slots_subscription_instance_id_foreign` (`subscription_instance_id`),
  ADD KEY `subscription_recurring_slots_teacher_id_foreign` (`teacher_id`),
  ADD KEY `subscription_recurring_slots_student_id_foreign` (`student_id`);

--
-- Index pour la table `subscription_templates`
--
ALTER TABLE `subscription_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscription_templates_club_id_model_number_unique` (`club_id`,`model_number`),
  ADD KEY `subscription_templates_club_id_is_active_index` (`club_id`,`is_active`);

--
-- Index pour la table `subscription_template_course_types`
--
ALTER TABLE `subscription_template_course_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sub_template_course_unique` (`subscription_template_id`,`course_type_id`),
  ADD KEY `sub_template_course_type_id_fk` (`course_type_id`);

--
-- Index pour la table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teachers_user_id_index` (`user_id`),
  ADD KEY `teachers_is_available_index` (`is_available`),
  ADD KEY `teachers_rating_index` (`rating`),
  ADD KEY `teachers_club_id_index` (`club_id`);

--
-- Index pour la table `teacher_certifications`
--
ALTER TABLE `teacher_certifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_certifications_teacher_id_certification_id_unique` (`teacher_id`,`certification_id`),
  ADD KEY `teacher_certifications_verified_by_foreign` (`verified_by`),
  ADD KEY `teacher_certifications_teacher_id_is_valid_index` (`teacher_id`,`is_valid`),
  ADD KEY `teacher_certifications_certification_id_is_valid_index` (`certification_id`,`is_valid`),
  ADD KEY `teacher_certifications_expiry_date_renewal_required_index` (`expiry_date`,`renewal_required`),
  ADD KEY `teacher_certifications_is_verified_is_valid_index` (`is_verified`,`is_valid`);

--
-- Index pour la table `teacher_disciplines`
--
ALTER TABLE `teacher_disciplines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_disciplines_teacher_id_discipline_id_unique` (`teacher_id`,`discipline_id`),
  ADD KEY `teacher_disciplines_teacher_id_is_primary_index` (`teacher_id`,`is_primary`),
  ADD KEY `teacher_disciplines_discipline_id_index` (`discipline_id`);

--
-- Index pour la table `teacher_skills`
--
ALTER TABLE `teacher_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_skills_teacher_id_skill_id_unique` (`teacher_id`,`skill_id`),
  ADD KEY `teacher_skills_verified_by_foreign` (`verified_by`),
  ADD KEY `teacher_skills_teacher_id_level_index` (`teacher_id`,`level`),
  ADD KEY `teacher_skills_skill_id_level_index` (`skill_id`,`level`),
  ADD KEY `teacher_skills_is_verified_is_active_index` (`is_verified`,`is_active`);

--
-- Index pour la table `time_blocks`
--
ALTER TABLE `time_blocks`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transactions_user_id_foreign` (`user_id`),
  ADD KEY `transactions_club_id_processed_at_index` (`club_id`,`processed_at`),
  ADD KEY `transactions_cash_register_id_processed_at_index` (`cash_register_id`,`processed_at`),
  ADD KEY `transactions_type_processed_at_index` (`type`,`processed_at`),
  ADD KEY `transactions_payment_method_processed_at_index` (`payment_method`,`processed_at`);

--
-- Index pour la table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_items_product_id_foreign` (`product_id`),
  ADD KEY `transaction_items_transaction_id_product_id_index` (`transaction_id`,`product_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_qr_code_unique` (`qr_code`);

--
-- Index pour la table `volunteer_expense_limits`
--
ALTER TABLE `volunteer_expense_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `volunteer_expense_limits_year_unique` (`year`),
  ADD KEY `volunteer_expense_limits_year_index` (`year`);

--
-- Index pour la table `volunteer_letter_sends`
--
ALTER TABLE `volunteer_letter_sends`
  ADD PRIMARY KEY (`id`),
  ADD KEY `volunteer_letter_sends_teacher_id_foreign` (`teacher_id`),
  ADD KEY `volunteer_letter_sends_sent_by_user_id_foreign` (`sent_by_user_id`),
  ADD KEY `volunteer_letter_sends_club_id_teacher_id_index` (`club_id`,`teacher_id`),
  ADD KEY `volunteer_letter_sends_status_index` (`status`),
  ADD KEY `volunteer_letter_sends_sent_at_index` (`sent_at`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_types`
--
ALTER TABLE `activity_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT pour la table `availabilities`
--
ALTER TABLE `availabilities`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT pour la table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `cash_registers`
--
ALTER TABLE `cash_registers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `certifications`
--
ALTER TABLE `certifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `club_activity_types`
--
ALTER TABLE `club_activity_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `club_custom_specialties`
--
ALTER TABLE `club_custom_specialties`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `club_disciplines`
--
ALTER TABLE `club_disciplines`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `club_managers`
--
ALTER TABLE `club_managers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `club_open_slots`
--
ALTER TABLE `club_open_slots`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `club_open_slot_course_types`
--
ALTER TABLE `club_open_slot_course_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `club_settings`
--
ALTER TABLE `club_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `club_students`
--
ALTER TABLE `club_students`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT pour la table `club_teachers`
--
ALTER TABLE `club_teachers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `club_user`
--
ALTER TABLE `club_user`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `course_types`
--
ALTER TABLE `course_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `disciplines`
--
ALTER TABLE `disciplines`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `google_calendar_tokens`
--
ALTER TABLE `google_calendar_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

--
-- AUTO_INCREMENT pour la table `lesson_replacements`
--
ALTER TABLE `lesson_replacements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lesson_student`
--
ALTER TABLE `lesson_student`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT pour la table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT pour la table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT pour la table `student_disciplines`
--
ALTER TABLE `student_disciplines`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `student_medical_documents`
--
ALTER TABLE `student_medical_documents`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `student_preferences`
--
ALTER TABLE `student_preferences`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `subscription_course_types`
--
ALTER TABLE `subscription_course_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `subscription_instances`
--
ALTER TABLE `subscription_instances`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `subscription_instance_students`
--
ALTER TABLE `subscription_instance_students`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `subscription_lessons`
--
ALTER TABLE `subscription_lessons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `subscription_recurring_slots`
--
ALTER TABLE `subscription_recurring_slots`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `subscription_templates`
--
ALTER TABLE `subscription_templates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `subscription_template_course_types`
--
ALTER TABLE `subscription_template_course_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `teacher_certifications`
--
ALTER TABLE `teacher_certifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `teacher_disciplines`
--
ALTER TABLE `teacher_disciplines`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `teacher_skills`
--
ALTER TABLE `teacher_skills`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `time_blocks`
--
ALTER TABLE `time_blocks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `transaction_items`
--
ALTER TABLE `transaction_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT pour la table `volunteer_expense_limits`
--
ALTER TABLE `volunteer_expense_limits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `volunteer_letter_sends`
--
ALTER TABLE `volunteer_letter_sends`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `availabilities`
--
ALTER TABLE `availabilities`
  ADD CONSTRAINT `availabilities_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `availabilities_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `cash_registers`
--
ALTER TABLE `cash_registers`
  ADD CONSTRAINT `cash_registers_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `certifications`
--
ALTER TABLE `certifications`
  ADD CONSTRAINT `certifications_activity_type_id_foreign` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `clubs`
--
ALTER TABLE `clubs`
  ADD CONSTRAINT `clubs_activity_type_id_foreign` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `club_activity_types`
--
ALTER TABLE `club_activity_types`
  ADD CONSTRAINT `club_activity_types_activity_type_id_foreign` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_activity_types_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_custom_specialties`
--
ALTER TABLE `club_custom_specialties`
  ADD CONSTRAINT `club_custom_specialties_activity_type_id_foreign` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_custom_specialties_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_disciplines`
--
ALTER TABLE `club_disciplines`
  ADD CONSTRAINT `club_disciplines_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_disciplines_discipline_id_foreign` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_managers`
--
ALTER TABLE `club_managers`
  ADD CONSTRAINT `club_managers_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_managers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_open_slots`
--
ALTER TABLE `club_open_slots`
  ADD CONSTRAINT `club_open_slots_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_open_slot_course_types`
--
ALTER TABLE `club_open_slot_course_types`
  ADD CONSTRAINT `club_open_slot_course_types_club_open_slot_id_foreign` FOREIGN KEY (`club_open_slot_id`) REFERENCES `club_open_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_open_slot_course_types_course_type_id_foreign` FOREIGN KEY (`course_type_id`) REFERENCES `course_types` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_settings`
--
ALTER TABLE `club_settings`
  ADD CONSTRAINT `club_settings_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_students`
--
ALTER TABLE `club_students`
  ADD CONSTRAINT `club_students_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_students_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_teachers`
--
ALTER TABLE `club_teachers`
  ADD CONSTRAINT `club_teachers_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_teachers_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `club_user`
--
ALTER TABLE `club_user`
  ADD CONSTRAINT `club_user_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `course_types`
--
ALTER TABLE `course_types`
  ADD CONSTRAINT `course_types_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_types_discipline_id_foreign` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `disciplines`
--
ALTER TABLE `disciplines`
  ADD CONSTRAINT `disciplines_activity_type_id_foreign` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `facilities`
--
ALTER TABLE `facilities`
  ADD CONSTRAINT `facilities_activity_type_id_foreign` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `google_calendar_tokens`
--
ALTER TABLE `google_calendar_tokens`
  ADD CONSTRAINT `google_calendar_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lessons_course_type_id_foreign` FOREIGN KEY (`course_type_id`) REFERENCES `course_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lessons_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lessons_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lessons_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lesson_replacements`
--
ALTER TABLE `lesson_replacements`
  ADD CONSTRAINT `lesson_replacements_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_replacements_original_teacher_id_foreign` FOREIGN KEY (`original_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_replacements_replacement_teacher_id_foreign` FOREIGN KEY (`replacement_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lesson_student`
--
ALTER TABLE `lesson_student`
  ADD CONSTRAINT `lesson_student_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_student_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `skills`
--
ALTER TABLE `skills`
  ADD CONSTRAINT `skills_activity_type_id_foreign` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `student_disciplines`
--
ALTER TABLE `student_disciplines`
  ADD CONSTRAINT `student_disciplines_discipline_id_foreign` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_disciplines_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `student_medical_documents`
--
ALTER TABLE `student_medical_documents`
  ADD CONSTRAINT `student_medical_documents_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `student_preferences`
--
ALTER TABLE `student_preferences`
  ADD CONSTRAINT `student_preferences_course_type_id_foreign` FOREIGN KEY (`course_type_id`) REFERENCES `course_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_preferences_discipline_id_foreign` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_preferences_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `sub_template_fk` FOREIGN KEY (`subscription_template_id`) REFERENCES `subscription_templates` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subscription_course_types`
--
ALTER TABLE `subscription_course_types`
  ADD CONSTRAINT `subscription_course_types_new_course_type_id_foreign` FOREIGN KEY (`course_type_id`) REFERENCES `course_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_course_types_new_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subscription_instances`
--
ALTER TABLE `subscription_instances`
  ADD CONSTRAINT `subscription_instances_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subscription_instance_students`
--
ALTER TABLE `subscription_instance_students`
  ADD CONSTRAINT `subscription_instance_students_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_instance_students_subscription_instance_id_foreign` FOREIGN KEY (`subscription_instance_id`) REFERENCES `subscription_instances` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subscription_lessons`
--
ALTER TABLE `subscription_lessons`
  ADD CONSTRAINT `subscription_lessons_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_lessons_subscription_instance_id_foreign` FOREIGN KEY (`subscription_instance_id`) REFERENCES `subscription_instances` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subscription_recurring_slots`
--
ALTER TABLE `subscription_recurring_slots`
  ADD CONSTRAINT `subscription_recurring_slots_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_recurring_slots_subscription_instance_id_foreign` FOREIGN KEY (`subscription_instance_id`) REFERENCES `subscription_instances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_recurring_slots_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subscription_templates`
--
ALTER TABLE `subscription_templates`
  ADD CONSTRAINT `sub_template_club_fk` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subscription_template_course_types`
--
ALTER TABLE `subscription_template_course_types`
  ADD CONSTRAINT `sub_template_course_type_id_fk` FOREIGN KEY (`course_type_id`) REFERENCES `course_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sub_template_id_fk` FOREIGN KEY (`subscription_template_id`) REFERENCES `subscription_templates` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teacher_certifications`
--
ALTER TABLE `teacher_certifications`
  ADD CONSTRAINT `teacher_certifications_certification_id_foreign` FOREIGN KEY (`certification_id`) REFERENCES `certifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_certifications_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_certifications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `teacher_disciplines`
--
ALTER TABLE `teacher_disciplines`
  ADD CONSTRAINT `teacher_disciplines_discipline_id_foreign` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_disciplines_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teacher_skills`
--
ALTER TABLE `teacher_skills`
  ADD CONSTRAINT `teacher_skills_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_skills_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_skills_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_cash_register_id_foreign` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD CONSTRAINT `transaction_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaction_items_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `volunteer_letter_sends`
--
ALTER TABLE `volunteer_letter_sends`
  ADD CONSTRAINT `volunteer_letter_sends_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `volunteer_letter_sends_sent_by_user_id_foreign` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `volunteer_letter_sends_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
