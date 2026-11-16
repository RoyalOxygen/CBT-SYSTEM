-- CBT System - Online/Offline synchronisation schema
-- Safe to run repeatedly through sync/install.php

CREATE TABLE IF NOT EXISTS `sync_change` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `change_uid` CHAR(36) NOT NULL,
  `origin_uid` CHAR(36) NOT NULL,
  `table_name` VARCHAR(64) NOT NULL,
  `op` ENUM('insert','update','delete') NOT NULL,
  `record_uuid` CHAR(36) NOT NULL,
  `payload` LONGTEXT DEFAULT NULL,
  `changed_at` DATETIME NOT NULL,
  `synced` TINYINT(1) NOT NULL DEFAULT 0,
  `received_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sync_change_uid` (`change_uid`),
  KEY `idx_sync_change_origin` (`origin_uid`, `id`),
  KEY `idx_sync_change_record` (`table_name`, `record_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_id_map` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(64) NOT NULL,
  `origin_uid` CHAR(36) NOT NULL,
  `source_id` BIGINT(20) NOT NULL,
  `local_id` BIGINT(20) NOT NULL,
  `record_uuid` CHAR(36) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sync_id_map_source` (`table_name`, `origin_uid`, `source_id`),
  KEY `idx_sync_id_map_uuid` (`table_name`, `record_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_state` (
  `state_key` VARCHAR(64) NOT NULL,
  `state_value` LONGTEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`state_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_node` (
  `node_uid` CHAR(36) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('hub','leaf') NOT NULL DEFAULT 'leaf',
  `secret_token` VARCHAR(64) NOT NULL,
  `status` ENUM('active','suspended','retired') NOT NULL DEFAULT 'active',
  `policy_json` LONGTEXT DEFAULT NULL,
  `last_seen_at` DATETIME DEFAULT NULL,
  `last_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`node_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_nonce` (
  `node_uid` CHAR(36) NOT NULL,
  `nonce` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`node_uid`, `nonce`),
  KEY `idx_sync_nonce_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_error` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `node_uid` CHAR(36) DEFAULT NULL,
  `change_uid` CHAR(36) DEFAULT NULL,
  `table_name` VARCHAR(64) DEFAULT NULL,
  `direction` ENUM('push','pull') NOT NULL DEFAULT 'pull',
  `message` TEXT DEFAULT NULL,
  `payload` LONGTEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sync_error_node` (`node_uid`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_file_package` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `node_uid` CHAR(36) DEFAULT NULL,
  `version` VARCHAR(40) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `sha256` CHAR(64) NOT NULL,
  `size` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('queued','downloaded','applied','failed','cancelled') NOT NULL DEFAULT 'queued',
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `downloaded_at` DATETIME DEFAULT NULL,
  `applied_at` DATETIME DEFAULT NULL,
  `report_json` LONGTEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sync_file_node` (`node_uid`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
