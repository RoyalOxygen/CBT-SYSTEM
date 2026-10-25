-- CBT System - SAMS attendance integration + ArcFace profiles
-- Safe to run repeatedly.

CREATE TABLE IF NOT EXISTS `face_profiles` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(11) UNSIGNED NOT NULL,
  `sams_student_id` int(11) UNSIGNED DEFAULT NULL,
  `descriptor_json` longtext NOT NULL,
  `descriptor_hash` char(64) NOT NULL,
  `sample_path` varchar(255) DEFAULT NULL,
  `enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_face_student` (`student_id`),
  KEY `idx_face_sams` (`sams_student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_files` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(11) UNSIGNED NOT NULL,
  `file_kind` varchar(20) NOT NULL DEFAULT 'photo',
  `filename` varchar(255) NOT NULL,
  `mime` varchar(80) DEFAULT 'image/jpeg',
  `content_b64` longtext NOT NULL,
  `content_hash` char(64) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_file_kind` (`student_id`,`file_kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing databases: ignore errors if the column or index already exists.
-- ALTER TABLE `students` ADD COLUMN `sams_student_id` int(11) UNSIGNED DEFAULT NULL AFTER `matric_number`;
-- ALTER TABLE `students` ADD COLUMN `sams_updated_at` datetime DEFAULT NULL AFTER `updated_at`;
-- ALTER TABLE `students` ADD COLUMN `face_enrolled` tinyint(1) NOT NULL DEFAULT 0 AFTER `fingerprint_enrolled`;
-- ALTER TABLE `students` ADD COLUMN `attendance_percent` decimal(5,1) DEFAULT NULL AFTER `face_enrolled`;
-- ALTER TABLE `students` ADD COLUMN `attendance_present` int(11) UNSIGNED DEFAULT NULL AFTER `attendance_percent`;
-- ALTER TABLE `students` ADD COLUMN `attendance_total` int(11) UNSIGNED DEFAULT NULL AFTER `attendance_present`;
-- ALTER TABLE `students` ADD UNIQUE KEY `uk_sams_student` (`sams_student_id`);
