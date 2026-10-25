-- CBT System - Fresh schema for the web installer
-- Contains structure only. Seed data is applied by install/lib.php.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_type` enum('admin','student') NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('super_admin','admin','invigilator') NOT NULL DEFAULT 'admin',
  `photo` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(11) UNSIGNED NOT NULL,
  `attempt_id` int(11) UNSIGNED NOT NULL,
  `question_id` int(11) UNSIGNED NOT NULL,
  `selected_answer` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_obtained` decimal(4,2) DEFAULT 0.00,
  `answered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `composite_exams` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_code` varchar(30) NOT NULL,
  `exam_title` varchar(200) NOT NULL,
  `department_id` int(11) UNSIGNED DEFAULT NULL,
  `level_id` int(11) UNSIGNED DEFAULT NULL,
  `semester_id` int(11) UNSIGNED DEFAULT NULL,
  `session_id` int(11) UNSIGNED DEFAULT NULL,
  `question_limit` int(4) NOT NULL DEFAULT 10,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration` int(4) NOT NULL DEFAULT 60,
  `pass_mark` int(3) NOT NULL DEFAULT 40,
  `instruction` text DEFAULT NULL,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 1,
  `shuffle_sub_questions` tinyint(1) NOT NULL DEFAULT 1,
  `show_result` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('draft','published','running','ended','archived') NOT NULL DEFAULT 'draft',
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `composite_exam_answers` (
  `id` int(11) UNSIGNED NOT NULL,
  `attempt_id` int(11) UNSIGNED NOT NULL,
  `composite_sub_question_id` int(11) UNSIGNED NOT NULL,
  `selected_answer` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_obtained` decimal(4,2) DEFAULT 0.00,
  `answered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `composite_exam_attempts` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_exam_id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `total_questions` int(4) NOT NULL DEFAULT 0 COMMENT 'Number of main questions',
  `total_sub_questions` int(4) NOT NULL DEFAULT 0 COMMENT 'Total sub-questions',
  `question_ids` text DEFAULT NULL,
  `answered_count` int(4) NOT NULL DEFAULT 0,
  `correct_count` int(4) NOT NULL DEFAULT 0,
  `score` decimal(6,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('in_progress','submitted','auto_submitted','timed_out','reset') NOT NULL DEFAULT 'in_progress',
  `submission_method` enum('manual','auto','admin') DEFAULT NULL,
  `original_duration` int(4) DEFAULT NULL,
  `time_extension_minutes` int(4) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser_info` varchar(255) DEFAULT NULL,
  `presented_sub_questions` text DEFAULT NULL COMMENT 'JSON array of sub-question IDs presented to this student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `composite_exam_results` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_exam_id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `attempt_id` int(11) UNSIGNED NOT NULL,
  `total_questions` int(4) NOT NULL DEFAULT 0,
  `total_sub_questions` int(4) NOT NULL DEFAULT 0,
  `answered` int(4) NOT NULL DEFAULT 0,
  `correct` int(4) NOT NULL DEFAULT 0,
  `wrong` int(4) NOT NULL DEFAULT 0,
  `score` decimal(6,2) DEFAULT 0.00,
  `total_marks` decimal(6,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(2) DEFAULT NULL,
  `remark` varchar(20) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `published_by` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `composite_exam_students` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_exam_id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `status` enum('assigned','started','submitted','reset') NOT NULL DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `composite_questions` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_exam_id` int(11) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_image` varchar(255) DEFAULT NULL,
  `marks` decimal(4,2) NOT NULL DEFAULT 1.00,
  `question_order` int(5) DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `composite_sub_options` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_sub_question_id` int(11) UNSIGNED NOT NULL,
  `option_label` char(1) NOT NULL COMMENT 'A, B, C, D, E',
  `option_text` text NOT NULL,
  `option_image` varchar(255) DEFAULT NULL,
  `option_order` int(2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `composite_sub_questions` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_question_id` int(11) UNSIGNED NOT NULL,
  `sub_question_label` char(1) NOT NULL COMMENT 'A, B, C, D, etc.',
  `sub_question_text` text NOT NULL,
  `sub_question_image` varchar(255) DEFAULT NULL,
  `correct_answer` char(1) NOT NULL COMMENT 'The correct option for this sub-question',
  `marks` decimal(4,2) NOT NULL DEFAULT 1.00,
  `sub_order` int(5) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) UNSIGNED NOT NULL,
  `dept_code` varchar(20) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exams` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_code` varchar(30) NOT NULL,
  `exam_title` varchar(200) NOT NULL,
  `department_id` int(11) UNSIGNED DEFAULT NULL,
  `level_id` int(11) UNSIGNED DEFAULT NULL,
  `semester_id` int(11) UNSIGNED DEFAULT NULL,
  `session_id` int(11) UNSIGNED DEFAULT NULL,
  `question_limit` int(4) NOT NULL DEFAULT 50,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration` int(4) NOT NULL,
  `fingerprint_required` tinyint(1) NOT NULL DEFAULT 0,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 1,
  `shuffle_options` tinyint(1) NOT NULL DEFAULT 1,
  `show_result` tinyint(1) NOT NULL DEFAULT 1,
  `overall_score` int(3) NOT NULL DEFAULT 100,
  `has_test_assessment` tinyint(1) NOT NULL DEFAULT 0,
  `pass_mark` int(3) NOT NULL DEFAULT 40,
  `instruction` text DEFAULT NULL,
  `status` enum('draft','published','running','ended','archived') NOT NULL DEFAULT 'draft',
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `has_batches` tinyint(1) NOT NULL DEFAULT 0,
  `default_biometric_required` tinyint(1) NOT NULL DEFAULT 0,
  `global_time_extension` int(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_attempts` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_id` int(11) UNSIGNED NOT NULL,
  `batch_id` int(11) UNSIGNED DEFAULT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `time_remaining` int(5) DEFAULT NULL,
  `total_questions` int(4) NOT NULL DEFAULT 0,
  `question_ids` text DEFAULT NULL,
  `answered_count` int(4) NOT NULL DEFAULT 0,
  `correct_count` int(4) NOT NULL DEFAULT 0,
  `score` decimal(6,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(2) DEFAULT NULL,
  `status` enum('in_progress','submitted','auto_submitted','timed_out','reset') NOT NULL DEFAULT 'in_progress',
  `ip_address` varchar(45) DEFAULT NULL,
  `browser_info` varchar(255) DEFAULT NULL,
  `submission_method` enum('manual','auto','admin') DEFAULT NULL,
  `original_duration` int(4) DEFAULT NULL,
  `time_extension_minutes` int(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_batches` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_id` int(11) UNSIGNED NOT NULL,
  `batch_code` varchar(30) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `batch_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration` int(4) NOT NULL,
  `capacity` int(6) DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `biometric_required` tinyint(1) NOT NULL DEFAULT 1,
  `biometric_verified_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_students` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_id` int(11) UNSIGNED NOT NULL,
  `batch_id` int(11) UNSIGNED DEFAULT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `is_carryover` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('assigned','started','submitted','reset') NOT NULL DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `face_profiles` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `sams_student_id` int(11) UNSIGNED DEFAULT NULL,
  `descriptor_json` longtext NOT NULL,
  `descriptor_hash` char(64) NOT NULL,
  `sample_path` varchar(255) DEFAULT NULL,
  `enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fingerprint_templates` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `template_data` longtext NOT NULL,
  `template_hash` varchar(255) NOT NULL,
  `finger_position` varchar(20) DEFAULT 'right_thumb',
  `quality_score` int(3) DEFAULT NULL,
  `enrolled_by` int(11) UNSIGNED DEFAULT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_files` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `file_kind` varchar(20) NOT NULL DEFAULT 'photo',
  `filename` varchar(255) NOT NULL,
  `mime` varchar(80) DEFAULT 'image/jpeg',
  `content_b64` longtext NOT NULL,
  `content_hash` char(64) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `levels` (
  `id` int(11) UNSIGNED NOT NULL,
  `level_name` varchar(20) NOT NULL,
  `level_order` int(2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `promotion_history` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `from_level_id` int(11) UNSIGNED DEFAULT NULL,
  `to_level_id` int(11) UNSIGNED DEFAULT NULL,
  `from_semester_id` int(11) UNSIGNED DEFAULT NULL,
  `to_semester_id` int(11) UNSIGNED DEFAULT NULL,
  `from_session_id` int(11) UNSIGNED DEFAULT NULL,
  `to_session_id` int(11) UNSIGNED DEFAULT NULL,
  `promoted_by` int(11) UNSIGNED NOT NULL,
  `promotion_type` varchar(50) NOT NULL DEFAULT 'single',
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `promoted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_id` int(11) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_image` varchar(255) DEFAULT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text DEFAULT NULL,
  `option_d` text DEFAULT NULL,
  `option_e` text DEFAULT NULL,
  `correct_answer` char(1) NOT NULL,
  `marks` decimal(4,2) NOT NULL DEFAULT 1.00,
  `question_order` int(5) DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `attempt_id` int(11) UNSIGNED NOT NULL,
  `total_questions` int(4) NOT NULL DEFAULT 0,
  `answered` int(4) NOT NULL DEFAULT 0,
  `correct` int(4) NOT NULL DEFAULT 0,
  `wrong` int(4) NOT NULL DEFAULT 0,
  `total_questions_score` decimal(6,2) DEFAULT 0.00,
  `score` decimal(6,2) DEFAULT 0.00,
  `total_marks` decimal(6,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(2) DEFAULT NULL,
  `remark` varchar(20) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `published_by` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semesters` (
  `id` int(11) UNSIGNED NOT NULL,
  `semester_name` varchar(20) NOT NULL,
  `semester_order` int(1) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(11) UNSIGNED NOT NULL,
  `session_name` varchar(20) NOT NULL,
  `session_start` date DEFAULT NULL,
  `session_end` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) UNSIGNED NOT NULL,
  `matric_number` varchar(30) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `other_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `department_id` int(11) UNSIGNED DEFAULT NULL,
  `level_id` int(11) UNSIGNED DEFAULT NULL,
  `semester_id` int(11) UNSIGNED DEFAULT NULL,
  `session_id` int(11) UNSIGNED DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `fingerprint_enrolled` tinyint(1) DEFAULT 0,
  `face_enrolled` tinyint(1) NOT NULL DEFAULT 0,
  `sams_student_id` int(11) UNSIGNED DEFAULT NULL,
  `sams_updated_at` datetime DEFAULT NULL,
  `biometric_verified` tinyint(1) DEFAULT 0,
  `login_status` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `login_ip` varchar(45) DEFAULT NULL,
  `current_exam_id` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_promotion_date` datetime DEFAULT NULL,
  `promotion_status` enum('active','promoted','graduated') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `time_extensions` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `attempt_id` int(11) UNSIGNED DEFAULT NULL,
  `extra_minutes` int(4) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `granted_by` int(11) UNSIGNED DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `activity_logs` ADD PRIMARY KEY (`id`);

ALTER TABLE `admins` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_username` (`username`),
  ADD UNIQUE KEY `uk_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_role` (`role`);

ALTER TABLE `answers` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_attempt_question` (`attempt_id`,`question_id`);

ALTER TABLE `composite_exams` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_composite_exam_code` (`exam_code`),
  ADD KEY `idx_composite_status` (`status`);

ALTER TABLE `composite_exam_answers` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_attempt_sub_question` (`attempt_id`,`composite_sub_question_id`);

ALTER TABLE `composite_exam_attempts` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_composite_exam_student_attempt` (`composite_exam_id`,`student_id`);

ALTER TABLE `composite_exam_results` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_composite_exam_student_result` (`composite_exam_id`,`student_id`);

ALTER TABLE `composite_exam_students` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_composite_exam_student` (`composite_exam_id`,`student_id`);

ALTER TABLE `composite_questions` ADD PRIMARY KEY (`id`),
  ADD KEY `idx_composite_exam` (`composite_exam_id`),
  ADD KEY `idx_composite_order` (`question_order`);

ALTER TABLE `composite_sub_options` ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sub_question` (`composite_sub_question_id`),
  ADD KEY `idx_option_order` (`option_order`);

ALTER TABLE `composite_sub_questions` ADD PRIMARY KEY (`id`),
  ADD KEY `idx_composite_question` (`composite_question_id`),
  ADD KEY `idx_sub_order` (`sub_order`);

ALTER TABLE `departments` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_dept_code` (`dept_code`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `exams` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_code` (`exam_code`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `exam_attempts` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_student_attempt` (`exam_id`,`student_id`);

ALTER TABLE `exam_batches` ADD PRIMARY KEY (`id`);

ALTER TABLE `exam_students` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_student` (`exam_id`,`student_id`);

ALTER TABLE `face_profiles` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_face_student` (`student_id`),
  ADD KEY `idx_face_sams` (`sams_student_id`);

ALTER TABLE `fingerprint_templates` ADD PRIMARY KEY (`id`);

ALTER TABLE `student_files` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_student_file_kind` (`student_id`,`file_kind`);

ALTER TABLE `levels` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_level_name` (`level_name`),
  ADD KEY `idx_level_order` (`level_order`);

ALTER TABLE `promotion_history` ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_promoted_by` (`promoted_by`),
  ADD KEY `idx_promotion_type` (`promotion_type`);

ALTER TABLE `questions` ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam` (`exam_id`);

ALTER TABLE `results` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_student_result` (`exam_id`,`student_id`);

ALTER TABLE `semesters` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_semester_name` (`semester_name`);

ALTER TABLE `sessions` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_session_name` (`session_name`),
  ADD KEY `idx_is_current` (`is_current`);

ALTER TABLE `settings` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_key` (`setting_key`);

ALTER TABLE `students` ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_matric` (`matric_number`),
  ADD UNIQUE KEY `uk_sams_student` (`sams_student_id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_level` (`level_id`),
  ADD KEY `idx_semester` (`semester_id`),
  ADD KEY `idx_session` (`session_id`);

ALTER TABLE `time_extensions` ADD PRIMARY KEY (`id`);

ALTER TABLE `activity_logs` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `admins` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `answers` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `composite_exams` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `composite_exam_answers` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `composite_exam_attempts` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `composite_exam_results` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `composite_exam_students` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `composite_questions` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `composite_sub_options` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `composite_sub_questions` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `departments` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `exams` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `exam_attempts` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `exam_batches` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `exam_students` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `face_profiles` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `fingerprint_templates` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `student_files` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `levels` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `promotion_history` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `questions` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `results` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `semesters` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `sessions` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `settings` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `students` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `time_extensions` MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
