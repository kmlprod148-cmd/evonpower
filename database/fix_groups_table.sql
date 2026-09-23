-- Sauvegarde des données existantes (si nécessaire)
CREATE TABLE IF NOT EXISTS groups_backup AS SELECT * FROM groups;

-- Supprimer la table problématique
DROP TABLE IF EXISTS groups;

-- Recréer la table avec la structure complète
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(255) DEFAULT NULL,
  `integrator_id` bigint(20) unsigned DEFAULT NULL,
  `partner_id` bigint(20) unsigned DEFAULT NULL,
  `parent_group_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `type` enum('station','area','organization','other') DEFAULT 'station',
  `is_featured` tinyint(1) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'UTC',
  `public_access` tinyint(1) DEFAULT 1,
  `max_charging_points` int(11) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `manager_email` varchar(255) DEFAULT NULL,
  `manager_phone` varchar(50) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `color_code` varchar(20) DEFAULT NULL,
  `opening_hours` json DEFAULT NULL,
  `services` json DEFAULT NULL,
  `restrictions` json DEFAULT NULL,
  `access_control` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `analytics_config` json DEFAULT NULL,
  `pricing_plan_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groups_slug_unique` (`slug`),
  KEY `groups_status_index` (`status`),
  KEY `groups_type_index` (`type`),
  KEY `groups_public_access_index` (`public_access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurer les données (si nécessaire et si la structure est compatible)
-- INSERT INTO groups (id, external_id, created_at, updated_at)
-- SELECT id, external_id, created_at, updated_at FROM groups_backup;