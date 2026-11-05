-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2026 at 07:57 PM
-- Server version: 12.3.3-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cbt_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_type` enum('admin','student') NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_type`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'admin', 3, 'save_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-11 14:53:41'),
(2, 'student', 18, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:56:49'),
(3, 'student', 31, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:56:54'),
(4, 'student', 31, 'submit_composite', 'Submitted composite exam: CMP2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 15:04:57'),
(5, 'student', 31, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 15:07:14'),
(6, 'student', 30, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 15:07:17'),
(7, 'student', 30, 'submit_composite', 'Submitted composite exam: CMP2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 15:07:45'),
(8, 'admin', 3, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 04:24:45'),
(9, 'student', 30, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:34:35'),
(10, 'student', 26, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:34:40'),
(11, 'student', 26, 'submit_composite', 'Submitted composite exam: CMP2026 - Score: 10/25 (40%)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:35:11'),
(12, 'student', 26, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:35:28'),
(13, 'student', 25, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:35:31'),
(14, 'student', 25, 'submit_composite', 'Submitted composite exam: CMP2026 - Score: 5/25 (20%)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:35:57'),
(15, 'student', 25, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:48:51'),
(16, 'student', 13, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:48:54'),
(17, 'student', 13, 'submit_composite', 'Submitted composite exam: CMP2026 — Score: 12/15 (80%)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:49:27'),
(18, 'student', 13, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:49:58'),
(19, 'student', 30, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:50:11'),
(20, 'student', 30, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:50:22'),
(21, 'student', 29, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:50:24'),
(22, 'student', 29, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:50:36'),
(23, 'student', 28, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:50:38'),
(24, 'student', 28, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:50:50'),
(25, 'student', 27, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:50:53'),
(26, 'student', 27, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:51:04'),
(27, 'student', 19, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:51:07'),
(28, 'student', 19, 'submit_composite', 'Submitted composite exam: CMP2026 — Score: 4/15 (26.666666666667%)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:51:36'),
(29, 'admin', 3, 'update_composite_exam', 'Updated composite exam ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 04:52:40'),
(30, 'student', 19, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:52:45'),
(31, 'student', 24, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:52:55'),
(32, 'student', 24, 'submit_composite', 'Submitted composite exam: CMP2026 — Score: 14/20 (70%)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:53:30'),
(33, 'student', 24, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:53:41'),
(34, 'student', 23, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:53:45'),
(35, 'student', 23, 'submit_composite', 'Submitted composite exam: CMP2026 — Score: 5/20 (25%)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:54:19'),
(36, 'student', 23, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:57:58'),
(37, 'student', 22, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:04:22'),
(38, 'student', 22, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:05:52'),
(39, 'student', 22, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:06:00'),
(40, 'student', 22, 'submit_composite', 'Submitted composite exam: CMP2026 — Score: 15/20 (75%) — 20 answer rows cleaned', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:06:30'),
(41, 'admin', 3, 'delete_all_results', 'Deleted all 0 regular and 9 composite results', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:08:06'),
(42, 'student', 22, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:09:30'),
(43, 'student', 31, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:13:54'),
(44, 'student', 31, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:14:08'),
(45, 'student', 30, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:14:11'),
(46, 'student', 30, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:14:25'),
(47, 'student', 26, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:14:28'),
(48, 'admin', 3, 'remove_student_assignment', 'Removed student ID: 12 from exam ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:15:04'),
(49, 'student', 26, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:15:13'),
(50, 'student', 14, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:15:16'),
(51, 'admin', 3, 'update_exam', 'Updated exam ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:15:52'),
(52, 'admin', 3, 'update_exam', 'Updated exam ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:16:53'),
(53, 'admin', 3, 'update_exam', 'Updated exam ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:17:09'),
(54, 'admin', 3, 'start_exam', 'Started exam ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:04'),
(55, 'admin', 3, 'end_exam', 'Ended exam ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:07'),
(56, 'admin', 3, 'archive_exam', 'Archived exam ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:10'),
(57, 'admin', 3, 'republish_exam', 'Republished exam ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:13'),
(58, 'admin', 3, 'start_exam', 'Started exam ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:17'),
(59, 'admin', 3, 'end_exam', 'Ended exam ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:20'),
(60, 'admin', 3, 'archive_exam', 'Archived exam ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:21'),
(61, 'admin', 3, 'republish_exam', 'Republished exam ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:24'),
(62, 'admin', 3, 'assign_student', 'Assigned student ID: 3 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(63, 'admin', 3, 'assign_student', 'Assigned student ID: 11 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(64, 'admin', 3, 'assign_student', 'Assigned student ID: 16 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(65, 'admin', 3, 'assign_student', 'Assigned student ID: 24 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(66, 'admin', 3, 'assign_student', 'Assigned student ID: 10 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(67, 'admin', 3, 'assign_student', 'Assigned student ID: 2 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(68, 'admin', 3, 'assign_student', 'Assigned student ID: 23 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(69, 'admin', 3, 'assign_student', 'Assigned student ID: 9 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(70, 'admin', 3, 'assign_student', 'Assigned student ID: 27 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(71, 'admin', 3, 'assign_student', 'Assigned student ID: 5 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(72, 'admin', 3, 'assign_student', 'Assigned student ID: 15 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(73, 'admin', 3, 'assign_student', 'Assigned student ID: 1 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(74, 'admin', 3, 'assign_student', 'Assigned student ID: 17 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(75, 'admin', 3, 'assign_student', 'Assigned student ID: 25 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(76, 'admin', 3, 'assign_student', 'Assigned student ID: 13 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(77, 'admin', 3, 'assign_student', 'Assigned student ID: 7 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(78, 'admin', 3, 'assign_student', 'Assigned student ID: 12 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(79, 'admin', 3, 'assign_student', 'Assigned student ID: 26 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(80, 'admin', 3, 'assign_student', 'Assigned student ID: 4 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(81, 'admin', 3, 'assign_student', 'Assigned student ID: 8 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(82, 'admin', 3, 'assign_student', 'Assigned student ID: 14 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(83, 'admin', 3, 'assign_student', 'Assigned student ID: 6 to exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:19:41'),
(84, 'student', 14, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:19:55'),
(85, 'student', 3, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:19:59'),
(86, 'student', 3, 'submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:21:29'),
(87, 'admin', 3, 'update_exam', 'Updated exam ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 05:23:56'),
(88, 'student', 3, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:24:12'),
(89, 'student', 17, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:24:25'),
(90, 'student', 17, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:24:59'),
(91, 'student', 17, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:25:05'),
(92, 'student', 17, 'submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:25:34'),
(93, 'student', 17, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 05:30:11'),
(94, 'student', 23, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 13:52:59'),
(95, 'student', 23, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:01:12'),
(96, 'student', 0, 'login_failed', 'Failed login attempt: TSU/FMS/PB/25/2035', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:01:45'),
(97, 'student', 19, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:01:52'),
(98, 'student', 19, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:01:58'),
(99, 'student', 5, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:02:23'),
(100, 'student', 5, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:17:55'),
(101, 'student', 5, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:44:30'),
(102, 'student', 23, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:44:34'),
(103, 'student', 23, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:44:54'),
(104, 'student', 10, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 14:44:57'),
(105, 'student', 10, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:04:20'),
(106, 'student', 22, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:04:35'),
(107, 'student', 22, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:04:51'),
(108, 'student', 25, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:04:54'),
(109, 'student', 25, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:11:15'),
(110, 'admin', 0, 'login_failed', 'Failed login attempt for username: admin from IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:11:53'),
(111, 'admin', 0, 'login_failed', 'Failed login attempt for username: superadmin from IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:12:07'),
(112, 'admin', 0, 'login_failed', 'Failed login attempt for username: superadmin from IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:12:26'),
(113, 'admin', 0, 'login_failed', 'Failed login attempt for username: superadmin from IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:12:51'),
(114, 'admin', 3, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:13:23'),
(115, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:13:55'),
(116, 'student', 3, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:14:15'),
(117, 'student', 3, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:16:32'),
(118, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:16:51'),
(119, 'student', 3, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:24:30'),
(120, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:26:29'),
(121, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:35:27'),
(122, 'student', 3, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:35:31'),
(123, 'student', 23, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:35:35'),
(124, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:51:22'),
(125, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:52:18'),
(126, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:52:29'),
(127, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:54:19'),
(128, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:57:57'),
(129, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:05'),
(130, 'admin', 3, 'assign_student', 'Assigned student ID: 3 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(131, 'admin', 3, 'assign_student', 'Assigned student ID: 11 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(132, 'admin', 3, 'assign_student', 'Assigned student ID: 16 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(133, 'admin', 3, 'assign_student', 'Assigned student ID: 24 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(134, 'admin', 3, 'assign_student', 'Assigned student ID: 10 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(135, 'admin', 3, 'assign_student', 'Assigned student ID: 2 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(136, 'admin', 3, 'assign_student', 'Assigned student ID: 23 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(137, 'admin', 3, 'assign_student', 'Assigned student ID: 9 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(138, 'admin', 3, 'assign_student', 'Assigned student ID: 27 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(139, 'admin', 3, 'assign_student', 'Assigned student ID: 5 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(140, 'admin', 3, 'assign_student', 'Assigned student ID: 15 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(141, 'admin', 3, 'assign_student', 'Assigned student ID: 1 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(142, 'admin', 3, 'assign_student', 'Assigned student ID: 17 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:46'),
(143, 'admin', 3, 'assign_student', 'Assigned student ID: 25 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(144, 'admin', 3, 'assign_student', 'Assigned student ID: 13 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(145, 'admin', 3, 'assign_student', 'Assigned student ID: 7 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(146, 'admin', 3, 'assign_student', 'Assigned student ID: 12 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(147, 'admin', 3, 'assign_student', 'Assigned student ID: 26 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(148, 'admin', 3, 'assign_student', 'Assigned student ID: 4 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(149, 'admin', 3, 'assign_student', 'Assigned student ID: 8 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(150, 'admin', 3, 'assign_student', 'Assigned student ID: 14 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(151, 'admin', 3, 'assign_student', 'Assigned student ID: 6 to exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 15:59:47'),
(152, 'admin', 3, 'update_exam', 'Updated exam ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:00:11'),
(153, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:20:01'),
(154, 'student', 23, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:20:08'),
(155, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:20:15'),
(156, 'student', 23, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:20:21'),
(157, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:24:26'),
(158, 'student', 23, 'logout', 'Student logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:24:40'),
(159, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:25:07'),
(160, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:25:13'),
(161, 'student', 23, 'login', 'Student logged in (one-way)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 16:25:18'),
(162, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 17:06:47'),
(163, 'admin', 3, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 17:08:48'),
(164, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 17:17:54'),
(165, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 17:26:02'),
(166, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 17:26:06'),
(167, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 17:31:47'),
(168, 'student', 23, 'submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 17:45:14'),
(169, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 17:57:48'),
(170, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 18:02:53'),
(171, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 18:15:11'),
(172, 'student', 23, 'auto_submit_exam', 'Submitted exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 18:15:30'),
(173, 'admin', 3, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 20:09:46'),
(174, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 20:09:54'),
(175, 'admin', 3, 'reset_all_students_exam', 'Reset exam for all 22 students on exam: NRS-PP2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 20:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
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

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `photo`, `status`, `last_login`, `login_ip`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$A9Sjk9fj/oP.zcg/HMTqR.1.ppuuf1aIgxx34rIaSTw8bONHKf06u', 'System Administrator', 'admin@cbtsystem.com', NULL, 'invigilator', NULL, 1, '2026-09-11 15:46:05', '::1', '2026-09-06 17:28:47', '2026-09-11 14:46:05'),
(3, 'superadmin', '$2y$10$HllHESTAtc7VYoiqmGxt5OzbMH47iqlm55FGhO1SzQpmN6q7jwzoG', 'SUPER ADMIN', 'royaloxygen5@gmail.com', NULL, 'super_admin', NULL, 1, '2026-09-18 21:09:46', '::1', '2026-09-06 17:33:05', '2026-09-18 20:09:46');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` int(11) UNSIGNED NOT NULL,
  `attempt_id` int(11) UNSIGNED NOT NULL,
  `question_id` int(11) UNSIGNED NOT NULL,
  `selected_answer` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_obtained` decimal(4,2) DEFAULT 0.00,
  `answered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`id`, `attempt_id`, `question_id`, `selected_answer`, `is_correct`, `marks_obtained`, `answered_at`) VALUES
(19304, 22, 71, 'B', 0, 0.00, '2026-09-18 20:51:21'),
(19314, 22, 131, 'C', 1, 1.00, '2026-09-18 20:23:46'),
(19315, 22, 144, 'D', 0, 0.00, '2026-09-18 20:24:44'),
(19316, 22, 187, 'D', 1, 1.00, '2026-09-18 20:24:59'),
(19318, 22, 108, 'B', 1, 1.00, '2026-09-18 20:25:07');

-- --------------------------------------------------------

--
-- Table structure for table `composite_exams`
--

CREATE TABLE `composite_exams` (
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

--
-- Dumping data for table `composite_exams`
--

INSERT INTO `composite_exams` (`id`, `exam_code`, `exam_title`, `department_id`, `level_id`, `semester_id`, `session_id`, `question_limit`, `exam_date`, `start_time`, `duration`, `pass_mark`, `instruction`, `shuffle_questions`, `shuffle_sub_questions`, `show_result`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CMP20', 'INTRODUCTION TO PUBLIC ADMINISTRATION', 7, 4, 1, 2, 20, '2026-09-10', '18:52:00', 5, 40, '', 1, 0, 1, 'published', 3, '2026-09-10 17:52:45', '2026-09-11 14:47:27'),
(2, 'CMP2026', 'A TRUE OR FALSE EXAMPLE', 7, 0, 1, 2, 4, '2026-09-11', '06:56:00', 5, 40, '', 1, 0, 1, 'published', 3, '2026-09-11 05:56:50', '2026-09-12 04:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `composite_exam_answers`
--

CREATE TABLE `composite_exam_answers` (
  `id` int(11) UNSIGNED NOT NULL,
  `attempt_id` int(11) UNSIGNED NOT NULL,
  `composite_sub_question_id` int(11) UNSIGNED NOT NULL,
  `selected_answer` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_obtained` decimal(4,2) DEFAULT 0.00,
  `answered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `composite_exam_attempts`
--

CREATE TABLE `composite_exam_attempts` (
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

-- --------------------------------------------------------

--
-- Table structure for table `composite_exam_results`
--

CREATE TABLE `composite_exam_results` (
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

-- --------------------------------------------------------

--
-- Table structure for table `composite_exam_students`
--

CREATE TABLE `composite_exam_students` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_exam_id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `status` enum('assigned','started','submitted','reset') NOT NULL DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `composite_questions`
--

CREATE TABLE `composite_questions` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_exam_id` int(11) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_image` varchar(255) DEFAULT NULL,
  `marks` decimal(4,2) NOT NULL DEFAULT 1.00,
  `question_order` int(5) DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `composite_questions`
--

INSERT INTO `composite_questions` (`id`, `composite_exam_id`, `question_text`, `question_image`, `marks`, `question_order`, `status`, `created_at`) VALUES
(1, 1, 'Read the following passage carefully and answer the questions below:\n\n\"Education is the most powerful weapon which you can use to change the world. It is the key to unlocking a better future for individuals and society as a whole. Through education, we develop critical thinking skills, expand our knowledge, and gain the ability to make informed decisions. Education empowers people to break the cycle of poverty and build a more equitable society. Every child deserves access to quality education regardless of their background or circumstances.\"', NULL, 4.00, 1, 1, '2026-09-10 18:06:15'),
(2, 1, 'Study the following information about a rectangle ABCD where AB = 8 cm and BC = 6 cm, then answer the questions below.', NULL, 4.00, 2, 1, '2026-09-10 18:06:15'),
(3, 1, 'Read the following information about the human circulatory system:\n\nThe human circulatory system consists of the heart, blood vessels, and blood. The heart pumps blood through a network of arteries, veins, and capillaries. Blood carries oxygen and nutrients to body cells and removes carbon dioxide and waste products. The average adult heart beats about 72 times per minute, circulating approximately 5 liters of blood throughout the body.', NULL, 4.00, 3, 1, '2026-09-10 18:06:15'),
(4, 1, 'Read the following passage about Nigerian independence:\n\nNigeria gained independence from British colonial rule on October 1, 1960. The country became a republic in 1963. Sir Abubakar Tafawa Balewa served as the first Prime Minister, while Dr. Nnamdi Azikiwe became the first President when Nigeria became a republic. The independence movement was championed by several nationalist leaders who fought for self-governance and freedom from colonial rule.', NULL, 4.00, 4, 1, '2026-09-10 18:06:15'),
(5, 1, 'Study the following Python code snippet and answer the questions below:\n\n```python\ndef calculate_total(items):\n    total = 0\n    for item in items:\n        if item > 0:\n            total += item\n    return total\n\nresult = calculate_total([5, -3, 10, -1, 8])\nprint(result)\n```', NULL, 4.00, 5, 1, '2026-09-10 18:06:15'),
(6, 2, 'Read each statement about computer science fundamentals and indicate whether it is True or False.', NULL, 5.00, 1, 1, '2026-09-11 05:59:50'),
(7, 2, 'Determine whether each of the following mathematical statements is True or False.', NULL, 5.00, 2, 1, '2026-09-11 05:59:50'),
(8, 2, 'Read each biological statement below and indicate whether it is True or False.', NULL, 5.00, 3, 1, '2026-09-11 05:59:51'),
(9, 2, 'Evaluate each geographic statement and mark True or False.', NULL, 5.00, 4, 1, '2026-09-11 05:59:51'),
(10, 2, 'Decide whether each of the following general knowledge statements is True or False.', NULL, 5.00, 5, 1, '2026-09-11 05:59:51');

-- --------------------------------------------------------

--
-- Table structure for table `composite_sub_options`
--

CREATE TABLE `composite_sub_options` (
  `id` int(11) UNSIGNED NOT NULL,
  `composite_sub_question_id` int(11) UNSIGNED NOT NULL,
  `option_label` char(1) NOT NULL COMMENT 'A, B, C, D, E',
  `option_text` text NOT NULL,
  `option_image` varchar(255) DEFAULT NULL,
  `option_order` int(2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `composite_sub_options`
--

INSERT INTO `composite_sub_options` (`id`, `composite_sub_question_id`, `option_label`, `option_text`, `option_image`, `option_order`) VALUES
(1, 1, 'A', 'Education', NULL, 1),
(2, 1, 'B', 'Money', NULL, 2),
(3, 1, 'C', 'Technology', NULL, 3),
(4, 1, 'D', 'Military power', NULL, 4),
(5, 2, 'A', 'Physical strength', NULL, 1),
(6, 2, 'B', 'Wealth accumulation', NULL, 2),
(7, 2, 'C', 'Critical thinking skills', NULL, 3),
(8, 2, 'D', 'Political power', NULL, 4),
(9, 3, 'A', 'The law', NULL, 1),
(10, 3, 'B', 'The cycle of poverty', NULL, 2),
(11, 3, 'C', 'The economy', NULL, 3),
(12, 3, 'D', 'The government', NULL, 4),
(13, 4, 'A', 'Only rich children deserve education', NULL, 1),
(14, 4, 'B', 'Technology is more important than education', NULL, 2),
(15, 4, 'C', 'Education is only for the elite', NULL, 3),
(16, 4, 'D', 'Education is a transformative tool for individuals and society', NULL, 4),
(17, 5, 'A', '14 cm', NULL, 1),
(18, 5, 'B', '24 cm', NULL, 2),
(19, 5, 'C', '28 cm', NULL, 3),
(20, 5, 'D', '48 cm', NULL, 4),
(21, 6, 'A', '28 cm²', NULL, 1),
(22, 6, 'B', '48 cm²', NULL, 2),
(23, 6, 'C', '56 cm²', NULL, 3),
(24, 6, 'D', '64 cm²', NULL, 4),
(25, 7, 'A', '8 cm', NULL, 1),
(26, 7, 'B', '10 cm', NULL, 2),
(27, 7, 'C', '12 cm', NULL, 3),
(28, 7, 'D', '14 cm', NULL, 4),
(29, 8, 'A', '48 cm²', NULL, 1),
(30, 8, 'B', '72 cm²', NULL, 2),
(31, 8, 'C', '96 cm²', NULL, 3),
(32, 8, 'D', '128 cm²', NULL, 4),
(33, 9, 'A', 'Lungs', NULL, 1),
(34, 9, 'B', 'Heart', NULL, 2),
(35, 9, 'C', 'Liver', NULL, 3),
(36, 9, 'D', 'Kidney', NULL, 4),
(37, 10, 'A', '60 times', NULL, 1),
(38, 10, 'B', '72 times', NULL, 2),
(39, 10, 'C', '85 times', NULL, 3),
(40, 10, 'D', '100 times', NULL, 4),
(41, 11, 'A', 'Heart', NULL, 1),
(42, 11, 'B', 'Blood vessels', NULL, 2),
(43, 11, 'C', 'Blood', NULL, 3),
(44, 11, 'D', 'Lungs', NULL, 4),
(45, 12, 'A', '2 liters', NULL, 1),
(46, 12, 'B', '3 liters', NULL, 2),
(47, 12, 'C', '5 liters', NULL, 3),
(48, 12, 'D', '8 liters', NULL, 4),
(49, 13, 'A', 'October 1, 1958', NULL, 1),
(50, 13, 'B', 'January 1, 1960', NULL, 2),
(51, 13, 'C', 'October 1, 1960', NULL, 3),
(52, 13, 'D', 'October 1, 1963', NULL, 4),
(53, 14, 'A', 'Dr. Nnamdi Azikiwe', NULL, 1),
(54, 14, 'B', 'Sir Abubakar Tafawa Balewa', NULL, 2),
(55, 14, 'C', 'Chief Obafemi Awolowo', NULL, 3),
(56, 14, 'D', 'Sir Ahmadu Bello', NULL, 4),
(57, 15, 'A', '1958', NULL, 1),
(58, 15, 'B', '1960', NULL, 2),
(59, 15, 'C', '1963', NULL, 3),
(60, 15, 'D', '1966', NULL, 4),
(61, 16, 'A', 'Dr. Nnamdi Azikiwe', NULL, 1),
(62, 16, 'B', 'Sir Abubakar Tafawa Balewa', NULL, 2),
(63, 16, 'C', 'Chief Obafemi Awolowo', NULL, 3),
(64, 16, 'D', 'General Yakubu Gowon', NULL, 4),
(65, 17, 'A', '19', NULL, 1),
(66, 17, 'B', '20', NULL, 2),
(67, 17, 'C', '23', NULL, 3),
(68, 17, 'D', '24', NULL, 4),
(69, 18, 'A', 'Count all items in the list', NULL, 1),
(70, 18, 'B', 'Sum all positive numbers in the list', NULL, 2),
(71, 18, 'C', 'Multiply all numbers in the list', NULL, 3),
(72, 18, 'D', 'Find the average of the list', NULL, 4),
(73, 19, 'A', 'Iteration with conditional filtering', NULL, 1),
(74, 19, 'B', 'Recursion', NULL, 2),
(75, 19, 'C', 'Object-oriented programming', NULL, 3),
(76, 19, 'D', 'Exception handling', NULL, 4),
(77, 20, 'A', '19', NULL, 1),
(78, 20, 'B', '24', NULL, 2),
(79, 20, 'C', '27', NULL, 3),
(80, 20, 'D', '30', NULL, 4),
(81, 21, 'T', 'True', NULL, 1),
(82, 21, 'F', 'False', NULL, 2),
(83, 22, 'T', 'True', NULL, 1),
(84, 22, 'F', 'False', NULL, 2),
(85, 23, 'T', 'True', NULL, 1),
(86, 23, 'F', 'False', NULL, 2),
(87, 24, 'T', 'True', NULL, 1),
(88, 24, 'F', 'False', NULL, 2),
(89, 25, 'T', 'True', NULL, 1),
(90, 25, 'F', 'False', NULL, 2),
(91, 26, 'T', 'True', NULL, 1),
(92, 26, 'F', 'False', NULL, 2),
(93, 27, 'T', 'True', NULL, 1),
(94, 27, 'F', 'False', NULL, 2),
(95, 28, 'T', 'True', NULL, 1),
(96, 28, 'F', 'False', NULL, 2),
(97, 29, 'T', 'True', NULL, 1),
(98, 29, 'F', 'False', NULL, 2),
(99, 30, 'T', 'True', NULL, 1),
(100, 30, 'F', 'False', NULL, 2),
(101, 31, 'T', 'True', NULL, 1),
(102, 31, 'F', 'False', NULL, 2),
(103, 32, 'T', 'True', NULL, 1),
(104, 32, 'F', 'False', NULL, 2),
(105, 33, 'T', 'True', NULL, 1),
(106, 33, 'F', 'False', NULL, 2),
(107, 34, 'T', 'True', NULL, 1),
(108, 34, 'F', 'False', NULL, 2),
(109, 35, 'T', 'True', NULL, 1),
(110, 35, 'F', 'False', NULL, 2),
(111, 36, 'T', 'True', NULL, 1),
(112, 36, 'F', 'False', NULL, 2),
(113, 37, 'T', 'True', NULL, 1),
(114, 37, 'F', 'False', NULL, 2),
(115, 38, 'T', 'True', NULL, 1),
(116, 38, 'F', 'False', NULL, 2),
(117, 39, 'T', 'True', NULL, 1),
(118, 39, 'F', 'False', NULL, 2),
(119, 40, 'T', 'True', NULL, 1),
(120, 40, 'F', 'False', NULL, 2),
(121, 41, 'T', 'True', NULL, 1),
(122, 41, 'F', 'False', NULL, 2),
(123, 42, 'T', 'True', NULL, 1),
(124, 42, 'F', 'False', NULL, 2),
(125, 43, 'T', 'True', NULL, 1),
(126, 43, 'F', 'False', NULL, 2),
(127, 44, 'T', 'True', NULL, 1),
(128, 44, 'F', 'False', NULL, 2),
(129, 45, 'T', 'True', NULL, 1),
(130, 45, 'F', 'False', NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `composite_sub_questions`
--

CREATE TABLE `composite_sub_questions` (
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

--
-- Dumping data for table `composite_sub_questions`
--

INSERT INTO `composite_sub_questions` (`id`, `composite_question_id`, `sub_question_label`, `sub_question_text`, `sub_question_image`, `correct_answer`, `marks`, `sub_order`, `created_at`) VALUES
(1, 1, 'A', 'According to the passage, what is described as the \"most powerful weapon\"?', NULL, 'A', 1.00, 1, '2026-09-10 18:06:15'),
(2, 1, 'B', 'What does education help individuals develop according to the passage?', NULL, 'C', 1.00, 2, '2026-09-10 18:06:15'),
(3, 1, 'C', 'What does education help people break according to the passage?', NULL, 'B', 1.00, 3, '2026-09-10 18:06:15'),
(4, 1, 'D', 'Which statement best summarizes the author\'s main argument?', NULL, 'D', 1.00, 4, '2026-09-10 18:06:15'),
(5, 2, 'A', 'What is the perimeter of the rectangle?', NULL, 'C', 1.00, 1, '2026-09-10 18:06:15'),
(6, 2, 'B', 'What is the area of the rectangle?', NULL, 'B', 1.00, 2, '2026-09-10 18:06:15'),
(7, 2, 'C', 'What is the length of the diagonal of the rectangle?', NULL, 'B', 1.00, 3, '2026-09-10 18:06:15'),
(8, 2, 'D', 'If the length is doubled and width remains the same, what is the new area?', NULL, 'C', 1.00, 4, '2026-09-10 18:06:15'),
(9, 3, 'A', 'Which organ is the main pump of the circulatory system?', NULL, 'B', 1.00, 1, '2026-09-10 18:06:15'),
(10, 3, 'B', 'What is the average heart rate of an adult per minute?', NULL, 'B', 1.00, 2, '2026-09-10 18:06:15'),
(11, 3, 'C', 'Which of the following is NOT a component of the circulatory system?', NULL, 'D', 1.00, 3, '2026-09-10 18:06:15'),
(12, 3, 'D', 'What is the approximate volume of blood circulated in an adult body?', NULL, 'C', 1.00, 4, '2026-09-10 18:06:15'),
(13, 4, 'A', 'On what date did Nigeria gain independence?', NULL, 'C', 1.00, 1, '2026-09-10 18:06:15'),
(14, 4, 'B', 'Who was Nigeria\'s first Prime Minister?', NULL, 'B', 1.00, 2, '2026-09-10 18:06:15'),
(15, 4, 'C', 'When did Nigeria become a republic?', NULL, 'C', 1.00, 3, '2026-09-10 18:06:15'),
(16, 4, 'D', 'Who became the first President of Nigeria?', NULL, 'A', 1.00, 4, '2026-09-10 18:06:15'),
(17, 5, 'A', 'What is the output of the code when executed?', NULL, 'C', 1.00, 1, '2026-09-10 18:06:15'),
(18, 5, 'B', 'What is the purpose of the function `calculate_total`?', NULL, 'B', 1.00, 2, '2026-09-10 18:06:15'),
(19, 5, 'C', 'What programming concept does this code demonstrate?', NULL, 'A', 1.00, 3, '2026-09-10 18:06:15'),
(20, 5, 'D', 'What would be the output if the list was `[5, 3, 10, 1, 8]` (all positive)?', NULL, 'C', 1.00, 4, '2026-09-10 18:06:15'),
(21, 6, 'A', 'A CPU is the brain of the computer that processes instructions.', NULL, 'T', 1.00, 1, '2026-09-11 05:59:50'),
(22, 6, 'B', 'RAM is a type of permanent storage that retains data when power is off.', NULL, 'F', 1.00, 2, '2026-09-11 05:59:50'),
(23, 6, 'C', 'Python is both an interpreted and dynamically typed programming language.', NULL, 'T', 1.00, 3, '2026-09-11 05:59:50'),
(24, 6, 'D', 'Binary uses only the digits 0 and 1.', NULL, 'T', 1.00, 4, '2026-09-11 05:59:50'),
(25, 6, 'E', 'A compiler converts source code into machine code before execution.', NULL, 'T', 1.00, 5, '2026-09-11 05:59:50'),
(26, 7, 'A', 'The sum of angles in a triangle is always 180 degrees.', NULL, 'T', 1.00, 1, '2026-09-11 05:59:51'),
(27, 7, 'B', 'Zero is neither a positive nor a negative number.', NULL, 'T', 1.00, 2, '2026-09-11 05:59:51'),
(28, 7, 'C', 'The number 1 is a prime number.', NULL, 'F', 1.00, 3, '2026-09-11 05:59:51'),
(29, 7, 'D', 'The square root of 16 is 4 and -4.', NULL, 'T', 1.00, 4, '2026-09-11 05:59:51'),
(30, 7, 'E', 'Pi (π) is a rational number.', NULL, 'F', 1.00, 5, '2026-09-11 05:59:51'),
(31, 8, 'A', 'The human body has 206 bones in adulthood.', NULL, 'T', 1.00, 1, '2026-09-11 05:59:51'),
(32, 8, 'B', 'Photosynthesis occurs in the mitochondria of plant cells.', NULL, 'F', 1.00, 2, '2026-09-11 05:59:51'),
(33, 8, 'C', 'DNA stands for Deoxyribonucleic Acid.', NULL, 'T', 1.00, 3, '2026-09-11 05:59:51'),
(34, 8, 'D', 'Bacteria are multicellular organisms.', NULL, 'F', 1.00, 4, '2026-09-11 05:59:51'),
(35, 8, 'E', 'The heart has four chambers.', NULL, 'T', 1.00, 5, '2026-09-11 05:59:51'),
(36, 9, 'A', 'The Sahara Desert is the largest hot desert in the world.', NULL, 'T', 1.00, 1, '2026-09-11 05:59:51'),
(37, 9, 'B', 'Nigeria is located in West Africa.', NULL, 'T', 1.00, 2, '2026-09-11 05:59:51'),
(38, 9, 'C', 'Mount Everest is the tallest mountain above sea level.', NULL, 'T', 1.00, 3, '2026-09-11 05:59:51'),
(39, 9, 'D', 'The Nile is the longest river in the world.', NULL, 'T', 1.00, 4, '2026-09-11 05:59:51'),
(40, 9, 'E', 'The Atlantic Ocean is the largest ocean on Earth.', NULL, 'F', 1.00, 5, '2026-09-11 05:59:51'),
(41, 10, 'A', 'The United Nations was founded in 1945.', NULL, 'T', 1.00, 1, '2026-09-11 05:59:51'),
(42, 10, 'B', 'Nigeria gained independence on October 1, 1960.', NULL, 'T', 1.00, 2, '2026-09-11 05:59:51'),
(43, 10, 'C', 'The Great Wall of China is visible from space with the naked eye.', NULL, 'F', 1.00, 3, '2026-09-11 05:59:51'),
(44, 10, 'D', 'Water boils at 100 degrees Celsius at sea level.', NULL, 'T', 1.00, 4, '2026-09-11 05:59:51'),
(45, 10, 'E', 'The human body is made up of approximately 60% water.', NULL, 'T', 1.00, 5, '2026-09-11 05:59:51');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) UNSIGNED NOT NULL,
  `dept_code` varchar(20) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_code`, `dept_name`, `faculty`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CSC', 'Computer Science', 'Science and Technology', 1, '2026-09-06 17:28:47', '2026-09-06 17:28:47'),
(2, 'MTH', 'Mathematics', 'Science and Technology', 1, '2026-09-06 17:28:47', '2026-09-06 17:28:47'),
(3, 'PHY', 'Physics', 'Science and Technology', 1, '2026-09-06 17:28:47', '2026-09-06 17:28:47'),
(4, 'CHM', 'Chemistry', 'Science and Technology', 1, '2026-09-06 17:28:47', '2026-09-06 17:28:47'),
(5, 'BIO', 'Biology', 'Science and Technology', 1, '2026-09-06 17:28:47', '2026-09-06 17:28:47'),
(6, 'NRS', 'NURSING SCIENCE', 'HEALTH', 1, '2026-09-06 17:28:47', '2026-09-08 14:13:05'),
(7, 'MLS', 'MEDICAL LABORATORY SCIENCE', 'HEALTH SCIENCE', 1, '2026-09-06 17:28:47', '2026-09-08 13:46:39'),
(8, 'PAD', 'PUBLIC ADMINISTRATION', 'MANAGEMENT SCIENCES', 1, '2026-09-07 07:44:11', '2026-09-07 07:44:11');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
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

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `exam_code`, `exam_title`, `department_id`, `level_id`, `semester_id`, `session_id`, `question_limit`, `exam_date`, `start_time`, `duration`, `fingerprint_required`, `shuffle_questions`, `shuffle_options`, `show_result`, `overall_score`, `has_test_assessment`, `pass_mark`, `instruction`, `status`, `created_by`, `created_at`, `updated_at`, `has_batches`, `default_biometric_required`, `global_time_extension`) VALUES
(1, 'NRS-PP2', 'NURSING PP2', 6, 4, 1, 2, 200, '2026-09-12', '06:13:00', 60, 0, 1, 1, 1, 100, 0, 40, '', 'published', 3, '2026-09-08 14:14:29', '2026-09-18 16:00:11', 0, 0, 0),
(2, 'NRS-PP3', 'NURSING PP3', 6, 4, 1, 2, 20, '2026-09-12', '06:00:00', 5, 0, 1, 1, 1, 100, 0, 40, '', 'published', 3, '2026-09-09 11:24:53', '2026-09-12 05:23:56', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `exam_attempts`
--

CREATE TABLE `exam_attempts` (
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

--
-- Dumping data for table `exam_attempts`
--

INSERT INTO `exam_attempts` (`id`, `exam_id`, `batch_id`, `student_id`, `start_time`, `end_time`, `time_remaining`, `total_questions`, `question_ids`, `answered_count`, `correct_count`, `score`, `percentage`, `grade`, `status`, `ip_address`, `browser_info`, `submission_method`, `original_duration`, `time_extension_minutes`) VALUES
(22, 1, NULL, 23, '2026-09-18 21:10:40', NULL, NULL, 200, '[71,131,144,187,108,193,231,118,70,1,244,211,6,145,220,213,21,52,51,2,139,135,116,81,138,65,236,92,214,18,242,178,185,199,127,253,91,239,219,234,163,184,197,95,77,168,154,90,103,134,238,46,93,74,218,73,9,255,192,208,196,66,13,45,177,115,32,67,62,102,124,186,217,104,59,61,42,140,29,129,4,68,47,40,226,147,57,117,64,101,136,142,227,233,28,54,97,205,223,156,189,237,48,182,107,172,210,157,80,160,202,232,88,82,209,155,11,216,247,125,39,225,94,151,201,15,250,174,194,3,180,22,123,207,130,224,86,164,241,24,161,63,252,141,181,114,41,83,137,7,17,79,176,159,248,165,100,204,44,34,113,12,112,190,96,87,191,215,198,254,55,49,85,212,27,120,105,203,109,25,240,143,56,72,162,167,133,222,19,10,84,98,20,246,76,243,249,36,158,122]', 0, 0, 0.00, 0.00, NULL, 'in_progress', NULL, NULL, NULL, 60, 0);

-- --------------------------------------------------------

--
-- Table structure for table `exam_batches`
--

CREATE TABLE `exam_batches` (
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

-- --------------------------------------------------------

--
-- Table structure for table `exam_students`
--

CREATE TABLE `exam_students` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_id` int(11) UNSIGNED NOT NULL,
  `batch_id` int(11) UNSIGNED DEFAULT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `is_carryover` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('assigned','started','submitted','reset') NOT NULL DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_students`
--

INSERT INTO `exam_students` (`id`, `exam_id`, `batch_id`, `student_id`, `is_carryover`, `status`, `assigned_at`) VALUES
(1, 2, NULL, 3, 0, 'assigned', '2026-09-12 05:19:41'),
(2, 2, NULL, 11, 0, 'assigned', '2026-09-12 05:19:41'),
(3, 2, NULL, 16, 0, 'assigned', '2026-09-12 05:19:41'),
(4, 2, NULL, 24, 0, 'assigned', '2026-09-12 05:19:41'),
(5, 2, NULL, 10, 0, 'assigned', '2026-09-12 05:19:41'),
(6, 2, NULL, 2, 0, 'assigned', '2026-09-12 05:19:41'),
(7, 2, NULL, 23, 0, 'assigned', '2026-09-12 05:19:41'),
(8, 2, NULL, 9, 0, 'assigned', '2026-09-12 05:19:41'),
(9, 2, NULL, 27, 0, 'assigned', '2026-09-12 05:19:41'),
(10, 2, NULL, 5, 0, 'assigned', '2026-09-12 05:19:41'),
(11, 2, NULL, 15, 0, 'assigned', '2026-09-12 05:19:41'),
(12, 2, NULL, 1, 0, 'assigned', '2026-09-12 05:19:41'),
(13, 2, NULL, 17, 0, 'assigned', '2026-09-12 05:19:41'),
(14, 2, NULL, 25, 0, 'assigned', '2026-09-12 05:19:41'),
(15, 2, NULL, 13, 0, 'assigned', '2026-09-12 05:19:41'),
(16, 2, NULL, 7, 0, 'assigned', '2026-09-12 05:19:41'),
(17, 2, NULL, 12, 0, 'assigned', '2026-09-12 05:19:41'),
(18, 2, NULL, 26, 0, 'assigned', '2026-09-12 05:19:41'),
(19, 2, NULL, 4, 0, 'assigned', '2026-09-12 05:19:41'),
(20, 2, NULL, 8, 0, 'assigned', '2026-09-12 05:19:41'),
(21, 2, NULL, 14, 0, 'assigned', '2026-09-12 05:19:41'),
(22, 2, NULL, 6, 0, 'assigned', '2026-09-12 05:19:41'),
(23, 1, NULL, 3, 0, 'assigned', '2026-09-18 15:59:46'),
(24, 1, NULL, 11, 0, 'assigned', '2026-09-18 15:59:46'),
(25, 1, NULL, 16, 0, 'assigned', '2026-09-18 15:59:46'),
(26, 1, NULL, 24, 0, 'assigned', '2026-09-18 15:59:46'),
(27, 1, NULL, 10, 0, 'assigned', '2026-09-18 15:59:46'),
(28, 1, NULL, 2, 0, 'assigned', '2026-09-18 15:59:46'),
(29, 1, NULL, 23, 0, 'started', '2026-09-18 15:59:46'),
(30, 1, NULL, 9, 0, 'assigned', '2026-09-18 15:59:46'),
(31, 1, NULL, 27, 0, 'assigned', '2026-09-18 15:59:46'),
(32, 1, NULL, 5, 0, 'assigned', '2026-09-18 15:59:46'),
(33, 1, NULL, 15, 0, 'assigned', '2026-09-18 15:59:46'),
(34, 1, NULL, 1, 0, 'assigned', '2026-09-18 15:59:46'),
(35, 1, NULL, 17, 0, 'assigned', '2026-09-18 15:59:46'),
(36, 1, NULL, 25, 0, 'assigned', '2026-09-18 15:59:46'),
(37, 1, NULL, 13, 0, 'assigned', '2026-09-18 15:59:47'),
(38, 1, NULL, 7, 0, 'assigned', '2026-09-18 15:59:47'),
(39, 1, NULL, 12, 0, 'assigned', '2026-09-18 15:59:47'),
(40, 1, NULL, 26, 0, 'assigned', '2026-09-18 15:59:47'),
(41, 1, NULL, 4, 0, 'assigned', '2026-09-18 15:59:47'),
(42, 1, NULL, 8, 0, 'assigned', '2026-09-18 15:59:47'),
(43, 1, NULL, 14, 0, 'assigned', '2026-09-18 15:59:47'),
(44, 1, NULL, 6, 0, 'assigned', '2026-09-18 15:59:47');

-- --------------------------------------------------------

--
-- Table structure for table `fingerprint_templates`
--

CREATE TABLE `fingerprint_templates` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `template_data` longtext NOT NULL,
  `template_hash` varchar(255) NOT NULL,
  `finger_position` varchar(20) DEFAULT 'right_thumb',
  `quality_score` int(3) DEFAULT NULL,
  `enrolled_by` int(11) UNSIGNED DEFAULT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fingerprint_templates`
--

INSERT INTO `fingerprint_templates` (`id`, `student_id`, `template_data`, `template_hash`, `finger_position`, `quality_score`, `enrolled_by`, `enrolled_at`) VALUES
(1, 1, '{\"sdk\":\"simulated\",\"version\":\"1.0\",\"data\":\"c2ltdWxhdGVkX3RlbXBsYXRlXzE3ODg3MTk5Mjg1MzlfMC53aHQ0d3o3MW9i\",\"quality\":90,\"timestamp\":\"2026-09-06T18:38:48.540Z\"}', 'hash_1788719928543', 'right_thumb', 89, 3, '2026-09-06 18:38:50'),
(2, 608, '{\"sdk\":\"simulated\",\"version\":\"1.0\",\"data\":\"c2ltdWxhdGVkX3RlbXBsYXRlXzE3ODg3NzY2NjU2MDZfMC51enBqc3lhOGFpcg==\",\"quality\":72,\"timestamp\":\"2026-09-07T10:24:25.606Z\"}', 'hash_1788776665608', 'right_thumb', 91, 3, '2026-09-07 10:24:28');

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `id` int(11) UNSIGNED NOT NULL,
  `level_name` varchar(20) NOT NULL,
  `level_order` int(2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`id`, `level_name`, `level_order`, `status`) VALUES
(1, '100 Level', 1, 1),
(2, '200 Level', 2, 1),
(3, '300 Level', 3, 1),
(4, '400 Level', 4, 1),
(5, '500 Level', 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `promotion_history`
--

CREATE TABLE `promotion_history` (
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

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
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

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `question_image`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_answer`, `marks`, `question_order`, `status`, `created_at`) VALUES
(1, 1, 'A nurse is assessing a patient with hypoxia. Which finding is expected?', NULL, 'A. Bradycardia', 'B. Cyanosis', 'C. Hypothermia', 'D. Polyuria', NULL, 'B', 1.00, 1, 1, '2026-09-08 14:18:19'),
(2, 1, 'Which action should the nurse take first when entering a patient\'s room?', NULL, 'A. Check IV fluids', 'B. Introduce self and identify the patient', 'C. Take vital signs', 'D. Review medication chart', NULL, 'B', 1.00, 2, 1, '2026-09-08 14:18:19'),
(3, 1, 'What is the normal adult respiratory rate?', NULL, 'A. 8-12 breaths/min', 'B. 12-20 breaths/min', 'C. 22-30 breaths/min', 'D. 30-40 breaths/min', NULL, 'B', 1.00, 3, 1, '2026-09-08 14:18:19'),
(4, 1, 'Which electrolyte is most important in cardiac function?', NULL, 'A. Magnesium', 'B. Sodium', 'C. Potassium', 'D. Chloride', NULL, 'C', 1.00, 4, 1, '2026-09-08 14:18:19'),
(5, 1, 'A patient has a temperature of 39¬∞C. This is classified as:', NULL, 'A. Hypothermia', 'B. Normal', 'C. Fever', 'D. Hyperglycemia', NULL, 'C', 1.00, 5, 1, '2026-09-08 14:18:19'),
(6, 1, 'Which nursing action prevents infection?', NULL, 'A. Hand hygiene', 'B. Bed making', 'C. Documentation', 'D. Positioning', NULL, 'A', 1.00, 6, 1, '2026-09-08 14:18:19'),
(7, 1, 'The most appropriate site for intramuscular injection in adults is:', NULL, 'A. Deltoid', 'B. Ventrogluteal', 'C. Abdomen', 'D. Forearm', NULL, 'B', 1.00, 7, 1, '2026-09-08 14:18:19'),
(8, 1, 'Which pulse site is used during CPR?', NULL, 'A. Radial', 'B. Brachial', 'C. Carotid', 'D. Pedal', NULL, 'C', 1.00, 8, 1, '2026-09-08 14:18:19'),
(9, 1, 'What is the normal oxygen saturation level?', NULL, 'A. 70‚80%', 'B. 80-90%', 'C. 95-100%', 'D. 50-60%', NULL, 'C', 1.00, 9, 1, '2026-09-08 14:18:19'),
(10, 1, 'Which patient should the nurse assess first?', NULL, 'A. Stable patient awaiting discharge', 'B. Patient with chest pain', 'C. Patient requesting water', 'D. Patient sleeping', NULL, 'B', 1.00, 10, 1, '2026-09-08 14:18:19'),
(11, 1, 'What is the antidote for opioid overdose?', NULL, 'A. Epinephrine', 'B. Naloxone', 'C. Atropine', 'D. Insulin', NULL, 'B', 1.00, 11, 1, '2026-09-08 14:18:19'),
(12, 1, 'A blood pressure of 90/60 mmHg indicates:', NULL, 'A. Hypertension', 'B. Normal crisis', 'C. Hypotension', 'D. Shock only', NULL, 'C', 1.00, 12, 1, '2026-09-08 14:18:19'),
(13, 1, 'Which is a sign of dehydration?', NULL, 'A. Moist mucous membranes', 'B. Increased urine output', 'C. Dry skin and thirst', 'D. Weight gain', NULL, 'C', 1.00, 13, 1, '2026-09-08 14:18:19'),
(14, 1, 'What is the best position for a patient with breathing difficulty?', NULL, 'A. Supine', 'B. Trendelenburg', 'C. High Fowler\'s', 'D. Prone', NULL, 'C', 1.00, 14, 1, '2026-09-08 14:18:19'),
(15, 1, 'Which laboratory value indicates infection?', NULL, 'A. Elevated WBC count', 'B. Low hemoglobin', 'C. Low potassium', 'D. High calcium', NULL, 'A', 1.00, 15, 1, '2026-09-08 14:18:19'),
(16, 1, 'The normal adult pulse rate is:', NULL, 'A. 20-40 bpm', 'B. 40-60 bpm', 'C. 60-100 bpm', 'D. 120-140 bpm', NULL, 'C', 1.00, 16, 1, '2026-09-08 14:18:19'),
(17, 1, 'Which patient is at highest risk for pressure ulcers?', NULL, 'A. Ambulatory patient', 'B. Bedridden patient', 'C. Young athlete', 'D. Visitor', NULL, 'B', 1.00, 17, 1, '2026-09-08 14:18:19'),
(18, 1, 'What is the priority in fire safety?', NULL, 'A. Rescue', 'B. Alarm', 'C. Confine', 'D. Extinguish', NULL, 'A', 1.00, 18, 1, '2026-09-08 14:18:19'),
(19, 1, 'Which vitamin deficiency causes scurvy?', NULL, 'A. Vitamin A', 'B. Vitamin C', 'C. Vitamin D', 'D. Vitamin K', NULL, 'B', 1.00, 19, 1, '2026-09-08 14:18:19'),
(20, 1, 'A patient with diabetes has a blood glucose of 45 mg/dL. This indicates:', NULL, 'A. Hyperglycemia', 'B. Hypoglycemia', 'C. Normal glucose', 'D. Ketoacidosis', NULL, 'B', 1.00, 20, 1, '2026-09-08 14:18:19'),
(21, 1, 'Which intervention reduces aspiration risk?', NULL, 'A. Flat position', 'B. Side-lying position', 'C. Trendelenburg', 'D. Prone', NULL, 'B', 1.00, 21, 1, '2026-09-08 14:18:19'),
(22, 1, 'The abbreviation NPO means:', NULL, 'A. No physical observation', 'B. Nothing by mouth', 'C. Normal patient order', 'D. No pain observed', NULL, 'B', 1.00, 22, 1, '2026-09-08 14:18:19'),
(23, 1, 'Which electrolyte imbalance may cause muscle weakness?', NULL, 'A. Hypokalemia', 'B. Hypercalcemia', 'C. Hypernatremia', 'D. Hypermagnesemia', NULL, 'A', 1.00, 23, 1, '2026-09-08 14:18:19'),
(24, 1, 'A patient develops wheezing after medication administration. The nurse suspects:', NULL, 'A. Allergic reaction', 'B. Hypertension', 'C. Hypoglycemia', 'D. Dehydration', NULL, 'A', 1.00, 24, 1, '2026-09-08 14:18:19'),
(25, 1, 'Which chamber of the heart pumps blood to the body?', NULL, 'A. Right atrium', 'B. Left atrium', 'C. Right ventricle', 'D. Left ventricle', NULL, 'D', 1.00, 25, 1, '2026-09-08 14:18:19'),
(26, 1, 'The normal body temperature is approximately:', NULL, 'A. 35\'C', 'B. 37\'C', 'C. 39\'C', 'D. 40\'C', NULL, 'B', 1.00, 26, 1, '2026-09-08 14:18:19'),
(27, 1, 'Which is the most reliable indicator of fluid balance?', NULL, 'A. Pulse', 'B. Weight', 'C. Respirations', 'D. Height', NULL, 'B', 1.00, 27, 1, '2026-09-08 14:18:19'),
(28, 1, 'A patient receiving oxygen should avoid:', NULL, 'A. Smoking', 'B. Drinking water', 'C. Eating', 'D. Sleeping', NULL, 'A', 1.00, 28, 1, '2026-09-08 14:18:19'),
(29, 1, 'What is the first step of the nursing process?', NULL, 'A. Planning', 'B. Assessment', 'C. Evaluation', 'D. Implementation', NULL, 'B', 1.00, 29, 1, '2026-09-08 14:18:19'),
(30, 1, 'Which patient requires airborne precautions?', NULL, 'A. Tuberculosis patient', 'B. Diarrhea patient', 'C. MRSA patient', 'D. Wound infection patient', NULL, 'A', 1.00, 30, 1, '2026-09-08 14:18:19'),
(31, 1, 'What is the normal urine output for adults?', NULL, 'A. 30 mL/hr or more', 'B. 5 mL/hr', 'C. 10 mL/hr', 'D. 15 mL/hr', NULL, 'A', 1.00, 31, 1, '2026-09-08 14:18:19'),
(32, 1, '32. Which finding suggests hypoglycemia?', NULL, 'A. Sweating and tremors', 'B. Dry skin', 'C. Hypertension', 'D. Polyuria', NULL, 'A', 1.00, 32, 1, '2026-09-08 14:18:19'),
(33, 1, '33. What is the purpose of informed consent?', NULL, 'A. Protect equipment', 'B. Respect patient autonomy', 'C. Increase hospital revenue', 'D. Reduce workload', NULL, 'B', 1.00, 33, 1, '2026-09-08 14:18:19'),
(34, 1, '34. Which patient is at highest priority?', NULL, 'A. Stable postoperative patient', 'B. Patient with severe respiratory distress', 'C. Patient requesting pain medication', 'D. Patient awaiting discharge', NULL, 'B', 1.00, 34, 1, '2026-09-08 14:18:19'),
(35, 1, '35. Which nutrient is essential for wound healing?', NULL, 'A. Protein', 'B. Cholesterol', 'C. Alcohol', 'D. Caffeine', NULL, 'A', 1.00, 35, 1, '2026-09-08 14:18:19'),
(36, 1, '36. What does \"PRN\" mean?', NULL, 'A. Daily', 'B. As needed', 'C. Immediately', 'D. Before meals', NULL, 'B', 1.00, 36, 1, '2026-09-08 14:18:19'),
(37, 1, '37. Which site is preferred for subcutaneous insulin injection?', NULL, 'A. Abdomen', 'B. Deltoid', 'C. Dorsogluteal', 'D. Scalp', NULL, 'A', 1.00, 37, 1, '2026-09-08 14:18:19'),
(38, 1, '38. A patient reports chest pain rated 10/10. What is the priority action?', NULL, 'A. Document pain', 'B. Assess and notify provider', 'C. Give food', 'D. Reposition only', NULL, 'B', 1.00, 38, 1, '2026-09-08 14:18:19'),
(39, 1, '39. Which sign may indicate internal bleeding?', NULL, 'A. Hypertension', 'B. Tachycardia', 'C. Weight gain', 'D. Bradycardia', NULL, 'B', 1.00, 39, 1, '2026-09-08 14:18:19'),
(40, 1, '40. What is the purpose of PPE?', NULL, 'A. Prevent infection transmission', 'B. Increase comfort', 'C. Improve communication', 'D. Save time', NULL, 'A', 1.00, 40, 1, '2026-09-08 14:18:19'),
(41, 1, '41. Which condition requires immediate intervention?', NULL, 'A. SpO‚ of  86%', 'B. Temperature 37¬∞C', 'C. Pulse 80 bpm', 'D. BP 120/80 mmHg', NULL, 'A', 1.00, 41, 1, '2026-09-08 14:18:19'),
(42, 1, '42. What is the normal pH range of blood?', NULL, 'A. 6.8-7.0', 'B. 7.35-7.45', 'C. 7.5-8.0', 'D. 8.0-8.5', NULL, 'B', 1.00, 42, 1, '2026-09-08 14:18:19'),
(43, 1, '43. Which medication is used to treat fever?', NULL, 'A. Insulin', 'B. Paracetamol', 'C. Heparin', 'D. Digoxin', NULL, 'B', 1.00, 43, 1, '2026-09-08 14:18:19'),
(44, 1, '44. Which nursing action promotes patient safety?', NULL, 'A. Side rails as appropriate', 'B. Leaving floor wet', 'C. Ignoring call bell', 'D. Poor lighting', NULL, 'A', 1.00, 44, 1, '2026-09-08 14:18:19'),
(45, 1, '45. What does tachycardia mean?', NULL, 'A. Heart rate below 60 bpm', 'B. Heart rate above 100 bpm', 'C. Irregular breathing', 'D. Low blood pressure', NULL, 'B', 1.00, 45, 1, '2026-09-08 14:18:19'),
(46, 1, '46. Which patient is at greatest risk for falls?', NULL, 'A. Young adult', 'B. Confused elderly patient', 'C. Visitor', 'D. Nurse', NULL, 'B', 1.00, 46, 1, '2026-09-08 14:18:19'),
(47, 1, '47. Which assessment is most important before administering digoxin?', NULL, 'A. Apical pulse', 'B. Height', 'C. Weight', 'D. Temperature', NULL, 'A', 1.00, 47, 1, '2026-09-08 14:18:19'),
(48, 1, '48. What is the normal fasting blood glucose level?', NULL, 'A. 70-99 mg/dL', 'B. 150-200 mg/dL', 'C. 250-300 mg/dL', 'D. Above 400 mg/dL', NULL, 'A', 1.00, 48, 1, '2026-09-08 14:18:19'),
(49, 1, '49. Which sign suggests fluid overload?', NULL, 'A. Dry mucous membranes', 'B. Edema', 'C. Weight loss', 'D. Hypotension', NULL, 'B', 1.00, 49, 1, '2026-09-08 14:18:19'),
(50, 1, '50. The final step of the nursing process is:', NULL, 'A. Diagnosis', 'B. Planning', 'C. Evaluation', 'D. Assessment', NULL, 'C', 1.00, 50, 1, '2026-09-08 14:18:19'),
(51, 1, '51. The nurse\'s first action when a patient falls is to:', NULL, 'A. Complete incident report', 'B. Assess the patient for injury', 'C. Call the family', 'D. Return patient to bed immediately', NULL, 'B', 1.00, 51, 1, '2026-09-08 14:18:19'),
(52, 1, '52. Which isolation precaution requires wearing a mask and negative-pressure room?', NULL, 'A. Contact', 'B. Droplet', 'C. Airborne', 'D. Standard', NULL, 'C', 1.00, 52, 1, '2026-09-08 14:18:19'),
(53, 1, '53. Normal adult urine color is:', NULL, 'A. Red', 'B. Dark brown', 'C. Pale yellow', 'D. Green', NULL, 'C', 1.00, 53, 1, '2026-09-08 14:18:19'),
(54, 1, '54. Which organ regulates fluid and electrolyte balance?', NULL, 'A. Heart', 'B. Kidney', 'C. Lung', 'D. Liver', NULL, 'B', 1.00, 54, 1, '2026-09-08 14:18:19'),
(55, 1, '55. A patient with diabetes should monitor:', NULL, 'A. Blood glucose level', 'B. Height only', 'C. Hair growth', 'D. Vision only', NULL, 'A', 1.00, 55, 1, '2026-09-08 14:18:19'),
(56, 1, '56. The best method to prevent hospital-acquired infections is:', NULL, 'A. Hand hygiene', 'B. Antibiotics for all patients', 'C. Isolation of all patients', 'D. Bed rest', NULL, 'A', 1.00, 56, 1, '2026-09-08 14:18:19'),
(57, 1, '57. Which finding indicates respiratory distress?', NULL, 'A. Clear speech', 'B. Difficulty breathing', 'C. Normal oxygen level', 'D. Relaxed breathing', NULL, 'B', 1.00, 57, 1, '2026-09-08 14:18:19'),
(58, 1, '58. A patient with a nasogastric tube is at risk for:', NULL, 'A. Aspiration', 'B. Hypertension', 'C. Hyperactivity', 'D. Increased appetite', NULL, 'A', 1.00, 58, 1, '2026-09-08 14:18:19'),
(59, 1, '59. Which route provides the fastest medication action?', NULL, 'A. Oral', 'B. Intramuscular', 'C. Intravenous', 'D. Subcutaneous', NULL, 'C', 1.00, 59, 1, '2026-09-08 14:18:19'),
(60, 1, '60. The normal adult blood pressure is approximately:', NULL, 'A. 80/40 mmHg', 'B. 120/80 mmHg', 'C. 160/100 mmHg', 'D. 200/120 mmHg', NULL, 'B', 1.00, 60, 1, '2026-09-08 14:18:19'),
(61, 1, '61. Which of the following is not a function of management as described by Luther H. Gullick', NULL, 'Planning', 'Organization', 'Budgeting', 'Forecasting', NULL, 'D', 1.00, 61, 1, '2026-09-08 14:18:19'),
(62, 1, '62. A deliberate and conscious process of management that does not occur by chance is called', NULL, 'Planning', 'Organizing', 'Budgeting', 'Directing', NULL, 'A', 1.00, 62, 1, '2026-09-08 14:18:19'),
(63, 1, '63. Which of these is not a conflict resolution model', NULL, 'Win-win', 'Lose-lose', 'Win-lose', 'Lose-win-lose', NULL, 'D', 1.00, 63, 1, '2026-09-08 14:18:19'),
(64, 1, '64. One of the following is not necessary in planning a hospital', NULL, 'Race of the patients', 'Bed capacity', 'Acuteness of common cases', 'Turnover of patients', NULL, 'A', 1.00, 64, 1, '2026-09-08 14:18:19'),
(65, 1, '65. Parenteral nutrition provides nutrition to the patient through:', NULL, 'A) Naso-gastric route', 'B) Gastrostomy tube', 'C) Intravenous route', 'D) Jejunostomy', NULL, 'C', 1.00, 65, 1, '2026-09-08 14:18:19'),
(66, 1, '66. Varicocele is an abnormal', NULL, 'Collection of fluid in the scrotum.', 'Dilation of venous plexus and the testis.', 'Dilation of the venous plexus and the spermatic vein in the scrotum.', 'Collection of pus in the scrotum.', NULL, 'C', 1.00, 66, 1, '2026-09-08 14:18:19'),
(67, 1, '67. The following are specific clinical manifestations of Nephrotic Syndrome EXCEPT', NULL, 'Cyanosis', 'Hypoalbuminaemia', 'Proteinuria', 'Oedema', NULL, 'A', 1.00, 67, 1, '2026-09-08 14:18:19'),
(68, 1, '68. A drug of choice in the treatment of trichomonal infection is', NULL, 'Metronidazole (flagyl)', 'Nystatin (mycostatin)', 'Penicillin G', 'Gentian violet.', NULL, 'A', 1.00, 68, 1, '2026-09-08 14:18:19'),
(69, 1, '69. The following structures return blood to the right atrium EXCEPT', NULL, 'Inferior vena cava', 'Pulmonary arteries', 'Superior vena cava', 'Coronary sinus.', NULL, 'B', 1.00, 69, 1, '2026-09-08 14:18:19'),
(70, 1, '70. T waves on an ECG represents', NULL, 'Repolarization of the atria.', 'Depolarization of the atria.', 'Depolarization of the ventricles.', 'Repolarization of the ventricles', NULL, 'D', 1.00, 70, 1, '2026-09-08 14:18:19'),
(71, 1, '71. While the semilunar valves are open during a normal cardiac cycle, the pressure in the left ventricles is', NULL, 'The same as the pressure in the left atrium.', 'Lesser than the pressure in the left atrium.', 'Lesser than the pressure in the aorta.', 'Greater than the pressure in the aorta.', NULL, 'C', 1.00, 71, 1, '2026-09-08 14:18:19'),
(72, 1, '72. Which method of oxygen administration is acceptable for a client with carbon dioxide retention?', NULL, 'Face mask with reservoir bag.', 'Simple face mask.', 'Nasal cannula.', 'Venturi mask.', NULL, 'A', 1.00, 72, 1, '2026-09-08 14:18:19'),
(73, 1, '73. Carbon monoxide is a toxic inhalant that decreases the oxygen carrying capacity of blood by', NULL, 'Forming a strong bond with carbon dioxide.', 'Forming a weak bond with carbon dioxide.', 'Forming a weak bond with haemoglobin.', 'Forming a strong bond with haemoglobin.', NULL, 'D', 1.00, 73, 1, '2026-09-08 14:18:19'),
(74, 1, '74. Presbyopia is a progressive decline in', NULL, 'The ability of the eye to focus on near objects.', 'The ability to see in darkness.', 'The adaptation to abrupt changes from dark area to light areas.', 'Distinguishing between colours.', NULL, 'A', 1.00, 74, 1, '2026-09-08 14:18:19'),
(75, 1, '75. Kyphosis is a term applied to an abnormal', NULL, 'Anterior curvature of the spine.', 'Lateral curvature of the spine.', 'Inferior curvature of the spine.', 'Posterior curvature of the spine.', NULL, 'D', 1.00, 75, 1, '2026-09-08 14:18:19'),
(76, 1, '76. The instrument used to assess the response of an unconscious patient is known as the', NULL, 'Stethoscope', 'Blood pressure apparatus.', 'Thermometer.', 'Glasgow coma scale.', NULL, 'D', 1.00, 76, 1, '2026-09-08 14:18:19'),
(77, 1, '77. The uterine tubes are lined with', NULL, 'Fibrous tissue', 'Pavement epithelium.', 'Ciliated epithelium.', 'Mucosa membrane.', NULL, 'C', 1.00, 77, 1, '2026-09-08 14:18:19'),
(78, 1, '78. Which of the following changes occurs first in the pre-pubertal girl?', NULL, 'Growth spurt in height.', 'Development of the breast.', 'Increase in the size of the external genital.', 'Growth of axillary and pubic hair.', NULL, 'B', 1.00, 78, 1, '2026-09-08 14:18:19'),
(79, 1, '79. \"Hot flushes\" experienced by some women during and after menopause is caused by', NULL, 'Pituitary disturbances.', 'Psychological responses.', 'Vasomotor instability.', 'Hereditary factors.', NULL, 'A', 1.00, 79, 1, '2026-09-08 14:18:19'),
(80, 1, '80. In case of acute vulgaris a Nurse recommends that the patient should', NULL, 'Squeeze black heads as they appear.', 'Wash the skin frequently using soap.', 'Use cosmetics to cover black heads.', 'Use emollient on the area.', NULL, 'D', 1.00, 80, 1, '2026-09-08 14:18:19'),
(81, 1, '81. The urge to void is aroused when distention stimulates stretch receptors in the', NULL, 'Left kidney wall.', 'Urethral sphincter.', 'Urethral orifice.', 'Urinary bladder wall.', NULL, 'D', 1.00, 81, 1, '2026-09-08 14:18:19'),
(82, 1, '82. Which of the following hormones is contained in follicular fluid prior to ovulation.', NULL, 'Luteinizing hormone.', 'Follicle stimulating hormone.', 'Progestrone.', 'Oestrogen.', NULL, 'A', 1.00, 82, 1, '2026-09-08 14:18:19'),
(83, 1, '83. The following are functions of the amniotic fluid and sac EXCEPT', NULL, 'Providing the fetus with immune bodies.', 'Cleansing the birth canal.', 'Helping to dilate the cervix.', 'Protecting the fetus from injury.', NULL, 'B', 1.00, 83, 1, '2026-09-08 14:18:19'),
(84, 1, '84. Which of the following food constituents does the pregnant woman need most in supplementary amount?', NULL, 'Calcium.', 'Iron.', 'Vitamin C.', 'Potassium.', NULL, 'B', 1.00, 84, 1, '2026-09-08 14:18:19'),
(85, 1, '85. Metrorrhagia is a term for', NULL, 'Irregular menstruation.', 'Anovular menstruation.', 'Prolonged menstruation.', 'Irregular vaginal bleeding.', NULL, 'D', 1.00, 85, 1, '2026-09-08 14:18:19'),
(86, 1, '86. The blush discolouration of the vaginal mucosa which is a presumptive sign of pregnancy is called', NULL, 'Hegar\' sign.', 'Goodells sign.', 'Babcoch sign.', 'Chadwick sign.', NULL, 'D', 1.00, 86, 1, '2026-09-08 14:18:19'),
(87, 1, '87. The blood vessel in which the oxygen content is at the highest in the fetus is called', NULL, 'Aorta.', 'Inferior vena cava.', 'Umbilical vein', 'Pulmonary Artery.', NULL, 'C', 1.00, 87, 1, '2026-09-08 14:18:19'),
(88, 1, '88. Which organ in the mother\'s body acts to detoxify the waste products from the fetus?', NULL, 'Kidney.', 'Spleen.', 'Liver.', 'Lungs.', NULL, 'C', 1.00, 88, 1, '2026-09-08 14:18:19'),
(89, 1, '89. How many calories will protein contribute to the pregnant woman\'s daily diet?', NULL, '210 calories.', '280 calories.', '140 calories.', '350 calories.', NULL, 'A', 1.00, 89, 1, '2026-09-08 14:18:19'),
(90, 1, '90.The circulatory changes that normally occur in the infant at the time of birth are initiated by', NULL, 'Environmental temperature.', 'Cutting of the umbilical cord.', 'Pulmonary ventilation.', 'The birth process.', NULL, 'C', 1.00, 90, 1, '2026-09-08 14:18:19'),
(91, 1, '91.The following are methods of bandage application EXCEPT', NULL, 'Check circulation after application.', 'Apply from distal to proximal part.', 'Apply from medial to lateral.', 'Apply from proximal to distal.', NULL, 'A', 1.00, 91, 1, '2026-09-08 14:18:19'),
(92, 1, '92. The following are the emergency treatment for an unconscious casualty of a high voltage current EXCEPT', NULL, 'Place him in a dorsal position.', 'Check for external injuries.', 'Regularly monitor and record vital signs.', 'Open the airway and check breathing.', NULL, 'B', 1.00, 92, 1, '2026-09-08 14:18:19'),
(93, 1, '93. Death by drowning occurs as a result of', NULL, 'Water damaging the liver.', 'Water damaging the kidneys.', 'Water distending the abdomen.', 'Water getting in to the lungs.', NULL, 'D', 1.00, 93, 1, '2026-09-08 14:18:19'),
(94, 1, '94. The following are resuscitative methods for a casualty rescued from drowning EXCEPT', NULL, 'Open the airway and check breathing.', 'Attempt to force water from the stomach.', 'Treat for hypothermia if present.', 'Lay the casualty down on her back.', NULL, 'B', 1.00, 94, 1, '2026-09-08 14:18:19'),
(95, 1, '95. To ensure an open airway during resuscitation, which of these will you do?', NULL, 'Keep one hand on the casualty\'s head and one finger of the other hand under his chin.', 'Keep one hand on the casualty\'s forehead and two fingers of the other hand under the tip of the chin.', 'Keep one hand on the casualty\'s forehead and two fingers of the other hand under his chin.', 'Keep one hand on the casualty\'s head and two fingers of the other hand under his chin.', NULL, 'C', 1.00, 95, 1, '2026-09-08 14:18:19'),
(96, 1, '96. When there is a splash of chemical in the eye, the priority care the First Aider should carry out is', NULL, 'Application of a sterile dressing on the eye.', 'Sending the casualty to the hospital.', 'Gently but firmly pull the eyelids open.', 'Thorough irrigation of the eye.', NULL, 'D', 1.00, 96, 1, '2026-09-08 14:18:19'),
(97, 1, '97. Which of the following will achieve the best result when irrigating the ear?', NULL, 'Direct fluid against the side of the ear canal.', 'Direct fluid straight in to the ear canal.', 'Direct fluid in forward and backward version.', 'Direct fluid below the cerumen.', NULL, 'C', 1.00, 97, 1, '2026-09-08 14:18:19'),
(98, 1, '98. Light enters the eye in which of this following order?', NULL, 'Lens, cornea, aqueous humour and vitreous humour.', 'Lens, aqueous humour, vitreous humour and cornea.', 'Cornea, aqueous humour, lens and vitreous humour.', 'Cornea, lens, aqueous humour and vitreous humour.', NULL, 'C', 1.00, 98, 1, '2026-09-08 14:18:19'),
(99, 1, '99. OcculusDexti (OD) refers to', NULL, 'Both eyes.', 'Right eye.', 'Left eye.', 'Damaged eye.', NULL, 'B', 1.00, 99, 1, '2026-09-08 14:18:19'),
(100, 1, '100. The following are purposes of applied research EXCEPT', NULL, 'To make a decision.', 'To generate information for future use.', 'To evaluate a process.', 'To solve a problem.', NULL, 'B', 1.00, 100, 1, '2026-09-08 14:18:19'),
(101, 1, '101. When a researcher is just interested in investigating variables as they appear, it is called', NULL, 'Exploratory research.', 'Historical research.', 'Explanatory research.', 'Descriptive research.', NULL, 'D', 1.00, 101, 1, '2026-09-08 14:18:19'),
(102, 1, '102. In non-experimental research, the subjects are', NULL, 'Studied under controlled situation.', 'Randomly selected.', 'Studied in a classroom setting.', 'Studied in their natural setting.', NULL, 'D', 1.00, 102, 1, '2026-09-08 14:18:19'),
(103, 1, '103. The following are purposes of literature review EXCEPT', NULL, 'It improves existing techniques of research.', 'It helps in the formulation and clarification of a research problem', 'It reveals what has already been done in the problem area.', 'It indicates whether it is feasible to carry out the planned research.', NULL, 'A', 1.00, 103, 1, '2026-09-08 14:18:19'),
(104, 1, '104. A major advantage of a computer search is to', NULL, 'Provide the specific source in which the article was published.', 'Include the name of the author of the article.', 'Bring up print out of abstracts of articles in the problem area.', 'Save time especially during the preliminary source search.', NULL, 'C', 1.00, 104, 1, '2026-09-08 14:18:19'),
(105, 1, '105. The key aspect of the research that helps to focus the literature review is known as', NULL, 'The statement of the problem.', 'The objective of the study.', 'The significance of study.', 'The operational definitions.', NULL, 'B', 1.00, 105, 1, '2026-09-08 14:18:19'),
(106, 1, '106. A statistical hypothesis is also known as', NULL, 'Null hypothesis.', 'Alternative hypothesis.', 'Non directional hypothesis.', 'Directional hypothesis.', NULL, 'A', 1.00, 106, 1, '2026-09-08 14:18:19'),
(107, 1, '107. The following are TRUE of hypothesis EXCEPT', NULL, 'It can be directional or non -directional.', 'It is a tentative description of relationship between two variables.', 'The two variables in a hypothesis are the independent and dependent variables.', 'It is differentiated into scientific and alternative hypothesis.', NULL, 'D', 1.00, 107, 1, '2026-09-08 14:18:19'),
(108, 1, '108. Cervical softening and uterine souffl√© are classified as which of the following signs of pregnancy?', NULL, 'Positive signs.', 'Probable signs.', 'Presumptive signs.', 'Diagnostic signs.', NULL, 'B', 1.00, 108, 1, '2026-09-08 14:18:19'),
(109, 1, '109. Which of the following fundal height estimation indicate less than 12 week\'s gestation when the date of the LMP is unknown?', NULL, 'Uterus at the xiphoid.', 'Uterus in the abdomen.', 'Uterus in the pelvis.', 'Uterus at the umbilical cord.', NULL, 'C', 1.00, 109, 1, '2026-09-08 14:18:19'),
(110, 1, '110. Which of the following stages of labour would the nurse assess \"crowning\"?', NULL, 'Second stage.', 'First stage.', 'Third stage.', 'Fourth stage.', NULL, 'A', 1.00, 110, 1, '2026-09-08 14:18:19'),
(111, 1, '111. Before birth, which of the following structures connects the right and left auricles of the fetal heart?', NULL, 'Ductusarteriousus.', 'Ductusvenousus.', 'Foramen ovale.', 'Umblical vein.', NULL, 'A', 1.00, 111, 1, '2026-09-08 14:18:19'),
(112, 1, '112. Preterm labour is -------------------------------------------', NULL, 'Labour that begins after 28 weeks gestation and before 40 weeks gestation.', 'Labour that begins after 24 weeks gestation and before 28 weeks gestation.', 'Labour that begins after 15 weeks gestation and before 37 weeks gestation.', 'Labour that begins after 20 weeks gestation and before 37 weeks gestation.', NULL, 'D', 1.00, 112, 1, '2026-09-08 14:18:19'),
(113, 1, '113. Which of the following nursing diagnoses would be given priority in the care plan of a pregnant woman who is experiencing hyperemesis gravidarum?', NULL, 'Self-care deficit.', 'Self-esteem disturbance.', 'Fluid volume deficit.', 'Altered oral mucous membrane.', NULL, 'C', 1.00, 113, 1, '2026-09-08 14:18:19'),
(114, 1, '114. An 18 year old primigravida is admitted at 34 weeks gestation with pre-eclampsia. She should be monitored carefully for manifestation of eclampsia which includes:', NULL, 'A diastolic pressure of 90mmhg.', 'Convulsion, seizures and coma.', 'Proteinuria of 3+', 'A rise in systolic pressure of 30mmhg or more.', NULL, 'B', 1.00, 114, 1, '2026-09-08 14:18:19'),
(115, 1, '115. The main substance excreted by the fetus in utero is:', NULL, 'Carbon dioxide.', 'Urea.', 'Uric acid.', 'Bilirubin.', NULL, 'C', 1.00, 115, 1, '2026-09-08 14:18:19'),
(116, 1, '116. The purpose of administration of Vitamin K injection to a new born is to:', NULL, 'Prevent rapid haemolysis of the fetal red blood cells.', 'Prevent the development of small infarcts.', 'Prevent hyperbilirubinaemia.', 'Prevent a deficiency in blood coagulation.', NULL, 'A', 1.00, 116, 1, '2026-09-08 14:18:19'),
(117, 1, '117. An invasive surgical procedure involving the removal of interior fibroid is called:', NULL, 'Laparatomic myomectomy.', 'Laparoscopic myomectomy.', 'Hysteroscopic myomectomy.', 'Hysteroctomy.', NULL, 'A', 1.00, 117, 1, '2026-09-08 14:18:19'),
(118, 1, '118. The adipose tissue present in newborn is:', NULL, 'Fibrous.', 'Brown.', 'White', 'Dense connective', NULL, 'B', 1.00, 118, 1, '2026-09-08 14:18:19'),
(119, 1, '119. A physician prescribes 1000mls of normal saline to be infused over a period of 10hrs. the drop factor is 15 drops per ml. the nurse sets the flow rate at how many drops per minute?', NULL, '27 drops/minute.', '21 drops/minute.', '25 drops/minute.', '23 drops/minute.', NULL, 'D', 1.00, 119, 1, '2026-09-08 14:18:19'),
(120, 1, '120. Management by objectives (MBO) as an organizational tool was first explained by:', NULL, 'Peter F. Ducker.', 'Robert Owen.', 'Henry Taone.', 'Hugo Munsterberg.', NULL, 'A', 1.00, 120, 1, '2026-09-08 14:18:19'),
(121, 1, '121. The famous 14 principles of management was first defined by:', NULL, 'Adam Smith.', 'James Watt.', 'Henri Fayol.', 'Elton Mayo.', NULL, 'C', 1.00, 121, 1, '2026-09-08 14:18:19'),
(122, 1, '122. A staff nurse makes negative comments about a unit manager\'s leadership style and the unit manager over-hears the staff nurse. Which action by the unit manager would be appropriate?', NULL, 'Persuade the staff nurse to stop being so critical.', 'Encourage the staff nurse to discuss the comments.', 'Propose a tentative solution regarding the comments and discuss it with the staff nurse.', 'Tell the staff nurse to stop making the comments.', NULL, 'C', 1.00, 122, 1, '2026-09-08 14:18:19'),
(123, 1, '123. According to Maslow\'s Hierarchy of Human Needs, the order are as follows:', NULL, 'Physiological-Safety-Love-Self- actualization-Esteem.', 'Physiological-Safety-Love-Esteem-Self-actualization.', 'Physiological-Love-Safe-Esteem-Self-actualization.', 'Physiological-Self-actualization-Love-Safety-Esteem.', NULL, 'B', 1.00, 123, 1, '2026-09-08 14:18:19'),
(124, 1, '124. Examination of the pouch of douglas with the aid of endoscope is called', NULL, 'Laparascopy.', 'Culdoscopy.', 'Uteroscopy.', 'Pouchoscopy.', NULL, 'B', 1.00, 124, 1, '2026-09-08 14:18:19'),
(125, 1, '125. Rectal examination may be carried out in the following positions EXCEPT:', NULL, 'Sim position.', 'Knee chest position.', 'Lithotomy position.', 'Supine position.', NULL, 'A', 1.00, 125, 1, '2026-09-08 14:18:19'),
(126, 1, '126. A newborn who has an asymmetrical moro-reflex response should be further assessed for which of the following?', NULL, 'Congenital hypothyroidism.', 'Increased intracranial pressure.', 'Talipesequinovarus.', 'Fracture clavicle.', NULL, 'D', 1.00, 126, 1, '2026-09-08 14:18:19'),
(127, 1, '127. A newly delivered mother asked the nurse, \"what is wrong with my son\'s breast\'s? why are they so enlarged? Which of the following would be the best response by the nurse?', NULL, 'The breast tissue is inflamed from the trauma experienced with birth.', 'A decreased in maternal hormones present before birth causes enlargement.', 'You should discuss this with your doctor. It could be malignancy.', 'The tissue has hypertrophied while the baby was in the uterus.', NULL, 'B', 1.00, 127, 1, '2026-09-08 14:18:19'),
(128, 1, '128. When assessing the newborn\'s heart rate, which of the following ranges would be considered normal if the newborn is sleeping?', NULL, '100 beats per minute.', '80 beats per minute.', '120 beats per minute.', '140 beats per minute.', NULL, 'A', 1.00, 128, 1, '2026-09-08 14:18:19'),
(129, 1, '129. Sensorimotor stage of intellectual development is a stage between:', NULL, 'Birth to 2 years.', '2 to 7 years.', '7 to 11 years.', '12 to 15 years.', NULL, 'A', 1.00, 129, 1, '2026-09-08 14:18:19'),
(130, 1, '130. Occasionally, a baby may be injured in the process of birth or delivery. Which of the following is not a common birth injury?', NULL, 'Fracture of the clavicle.', 'Cephalhaematoma.', 'Nerve injuries.', 'Neonatal jaundice.', NULL, 'D', 1.00, 130, 1, '2026-09-08 14:18:19'),
(131, 1, '131. Neonatal mortality rate is defined as:', NULL, 'Number of death of children under 46 days of age per 1000 live birth.', 'Number of death of children under 48 days of age per 1000 live birth.', 'Number of death of children under 28 days of age per 1000 live birth.', 'Number of death of children under 38 days of age per 1000 live birth.', NULL, 'C', 1.00, 131, 1, '2026-09-08 14:18:19'),
(132, 1, '132. Sigmund Freud in his psycho-sexual stages of development believed that harsh toilet training in a child will make him in later life to be:', NULL, 'Very obstinate.', 'Unruly.', 'A non-conformist.', 'Secretive.', NULL, 'A', 1.00, 132, 1, '2026-09-08 14:18:19'),
(133, 1, '133. Which of the following is not a characteristics of a pre-schoolar.', NULL, 'Truthful.', 'Likes exploration.', 'Loves doing manual jobs at home.', 'Structured play.', NULL, 'D', 1.00, 133, 1, '2026-09-08 14:18:19'),
(134, 1, '134. At what age do you find the Oedipus complex more?', NULL, 'Pre-school age', 'School age', 'Toddler', 'Senescence.', NULL, 'B', 1.00, 134, 1, '2026-09-08 14:18:19'),
(135, 1, '135. Which of the following is not true about nursing?', NULL, 'Nursing is a science.', 'Nursing is an act.', 'Nursing is generalizable.', 'Nursing is caring.', NULL, 'C', 1.00, 135, 1, '2026-09-08 14:18:19'),
(136, 1, '136. Nursing is the act of utilizing the environment of the patient to assist him in his recovery. The above definition of nursing was by', NULL, 'Florence Nightingale.', 'Canadian Nurses Association (CAN).', 'American Nurses Association (ANA).', 'Virginia Henderson.', NULL, 'A', 1.00, 136, 1, '2026-09-08 14:18:19'),
(137, 1, '137. The four central concepts in nursing includes the following EXCEPT', NULL, 'The person', 'Nursing', 'Health', 'Hospital', NULL, 'D', 1.00, 137, 1, '2026-09-08 14:18:19'),
(138, 1, '138. The adaptation model of nursing is synonymous with', NULL, 'Virginia Henderson.', 'Florence Nightingale.', 'Calista Roy.', 'Dorothea Orem.', NULL, 'C', 1.00, 138, 1, '2026-09-08 14:18:19'),
(139, 1, '139. A client is to receive Ampicillin sodium 500mg in 100ml of normal saline over a period of 45minutes. The drop factor is 10 drops per mls. The nurse sets the flow rate at how many drops per minute.', NULL, '22.2 drops/minute.', '20.3 drops/minute.', '24.3 drops/minute.', '26.2 drops/minute.', NULL, 'A', 1.00, 139, 1, '2026-09-08 14:18:19'),
(140, 1, '140. Anticholinergic drugs are useful in managing gastro-intestinal problem such as duodenal ulcer. The major action of this drugs is to', NULL, 'Absorb excess gastric secretions.', 'Neutralize hydrochloric acid in the stomach.', 'Reduce production of gastric secretions.', 'Increased gastric motility.', NULL, 'C', 1.00, 140, 1, '2026-09-08 14:18:19'),
(141, 1, '141. Mr Jacob has an order for antacids every 2 hours, at 2am when the night nurse goes to his room to give him the antacid, he is asleep. What action is most appropriate?', NULL, 'Wake him but give him a double dose in order to avoid waking him at 4am.', 'Let him sleep until he wakes, and then resume the antacid every 2 hours.', 'Let him sleep, but wake him at 4am and give him a double dose of the medicine.', 'Wake him and give the medication.', NULL, 'B', 1.00, 141, 1, '2026-09-08 14:18:19'),
(142, 1, '142. Best treatment for a tuberculosis patient is', NULL, 'Broad spectrum.', 'Haematinics.', 'Single drug therapy', 'Multi drug therapy', NULL, 'D', 1.00, 142, 1, '2026-09-08 14:18:19'),
(143, 1, '143. Which of the following is NOT a components of multi drug therapy for Pulmonary TB', NULL, 'Isoniazid.', 'Rifampicin.', 'Ethambutol', 'Coxycilin', NULL, 'D', 1.00, 143, 1, '2026-09-08 14:18:19'),
(144, 1, '144. The symptoms of mental illness are clustered according to their effects on the mind, which of the following clusters are related to disturbance of affect disorder?', NULL, 'Confusion, stupor, apathy and flat.', 'Dejavuc, inappropriate, neologism and flat.', 'Neologism , apathy, fight of ideas and flat.', 'Anhedonia, apathy, inappropriate and flat.', NULL, 'A', 1.00, 144, 1, '2026-09-08 14:18:19'),
(145, 1, '145. Which of the following clusters are related to thought content disorder?', NULL, 'Paramnesia, delirium, illusions and obsessions.', 'Circumstantiality, hypochondriasis, delusions and obsessions.', 'Hypochondriasis, delusions, phobias and obsessions.', 'Hallucinations, illusions, phobia and obsessions.', NULL, 'B', 1.00, 145, 1, '2026-09-08 14:18:19'),
(146, 1, '146. Which clusters of symptoms do thought blocking tangentiality, preservation and loose association belongs?', NULL, 'Affect.', 'Thought process.', 'Thought content.', 'Memory.', NULL, 'C', 1.00, 146, 1, '2026-09-08 14:18:19'),
(147, 1, '147. What strategy of the nurse is more helpful to a client who is hallucinating.', NULL, 'Tell the client that you do not understand what is happening.', 'Tell the client that you are not experiencing the hallucination.', 'Ask the client to go and stay in his room until the hallucination disappears.', 'Ask the client to stop focusing on the hallucination.', NULL, 'D', 1.00, 147, 1, '2026-09-08 14:18:19'),
(148, 1, '148. A significant change in the treatment of persons with mental illness occurred in the 1950s when', NULL, 'Psychotropic drugs became available for use.', 'The patient\'s bill of rights was enacted.', 'Legislation dramatically changed civil commitment procedures.', 'Community support services were established.', NULL, 'A', 1.00, 148, 1, '2026-09-08 14:18:19'),
(149, 1, '149. The nurse is working with a client who doesn\'t take prescribed medication once he is discharged from the hospital. The client states \"there is nothing wrong with me. I don\'t need medication\" this is an example of', NULL, 'Denial.', 'Projection.', 'Displacement.', 'Regression.', NULL, 'A', 1.00, 149, 1, '2026-09-08 14:18:19'),
(150, 1, '150. The chronic depressive disorder that has no psychotic features but lasts for at least 2 years and often referred to as chronic unhappiness is which of the following?', NULL, 'Bipolar disorder.', 'Cyclothymic disorder.', 'Hypomanic disorder.', 'Dysthymic disorder.', NULL, 'A', 1.00, 150, 1, '2026-09-08 14:18:19'),
(151, 1, '151. The main treatment of incomplete abortion is', NULL, 'Ergometrine.', 'Syntocinon infusion.', 'Evacuation of the uterus.', 'Bed rest and sedation.', NULL, 'C', 1.00, 151, 1, '2026-09-08 14:18:19'),
(152, 1, '152. Immediate postpartum shock may be due to', NULL, 'Deep vein thrombosis.', 'Ruptured uterus.', 'Acute inversion of the uterus.', 'Primary postpartum haemorrhage.', NULL, 'D', 1.00, 152, 1, '2026-09-08 14:18:19'),
(153, 1, '153. The following are tubal patency test EXCEPT', NULL, 'Tubal insufflation.', 'Laparoscopy and dye test.', 'Hysterosalpingogram.', 'Pelvic angiogram.', NULL, 'D', 1.00, 153, 1, '2026-09-08 14:18:19'),
(154, 1, '154. Lateral abdominal palpation is use to locate the fetal back in order to determine the', NULL, 'position', 'attitude', 'lie', 'restitution', NULL, 'A', 1.00, 154, 1, '2026-09-08 14:18:19'),
(155, 1, '155. Heart burns in pregnancy is most troublesome between', NULL, '12-16 weeks of gestation.', '16-24 weeks of gestation.', '24-30 weeks of gestation.', '30-40 weeks of gestation.', NULL, 'A', 1.00, 155, 1, '2026-09-08 14:18:19'),
(156, 1, '156. Colostrum can be expressed from the breast during pregnancy from', NULL, '12 weeks of gestation.', '16 weeks of gestation.', '30 weeks of gestation.', '36 weeks of gestation.', NULL, 'B', 1.00, 156, 1, '2026-09-08 14:18:19'),
(157, 1, '157. The expected date of delivery of a pregnant woman whose first day of last menstrual period is 21st January 2014 is', NULL, '27th October, 2014.', '27th November, 2014.', '28th October, 2014.', '28th November, 2014.', NULL, 'C', 1.00, 157, 1, '2026-09-08 14:18:19'),
(158, 1, '158. Physiological anaemia in pregnancy occurs as a result of', NULL, 'Increased plasma volume.', 'Nausea and vomiting.', 'Severe bleeding.', 'Loss of appetite.', NULL, 'A', 1.00, 158, 1, '2026-09-08 14:18:19'),
(159, 1, '159. Puerperium can be described as a period after the birth of a baby up to', NULL, '6 weeks.', '16 weeks.', '27 weeks.', '3 months.', NULL, 'A', 1.00, 159, 1, '2026-09-08 14:18:19'),
(160, 1, '160. Involution of the uterus takes place by the process of', NULL, 'Phagocytosis.', 'Autolysis.', 'Autolysis and Ischaemia.', 'Autolysis and Haemodilution.', NULL, 'C', 1.00, 160, 1, '2026-09-08 14:18:19'),
(161, 1, '161. Sub involution can be diagnosed if the uterus is', NULL, 'The same size for several days.', 'Increasing in length and weight.', 'Anteverted and antiflexed.', 'Hard and mobile.', NULL, 'A', 1.00, 161, 1, '2026-09-08 14:18:19'),
(162, 1, '162. Vaginal examination is contraindicated in', NULL, 'Normal labour.', 'False labour.', 'Placenta praevia.', 'Complete abortion.', NULL, 'C', 1.00, 162, 1, '2026-09-08 14:18:19'),
(163, 1, '163. The following are functions of the vagina EXCEPT', NULL, 'Receives the ejaculated sperm.', 'Produce menstruation.', 'Receives the penis during coitus.', 'Serves as exit for the foetus during delivery.', NULL, 'B', 1.00, 163, 1, '2026-09-08 14:18:19'),
(164, 1, '164. One of the following is NOT a clinical manifestation of diptheria', NULL, 'Thick grey white patch at the back of throat', 'Nasal discharge', 'Fever', 'Thick red patches at rectum', NULL, 'D', 1.00, 164, 1, '2026-09-08 14:18:19'),
(165, 1, '165. The incubation period of diptheria is', NULL, '2-7days', '1-10days', '10-15days', '1-3days', NULL, 'B', 1.00, 165, 1, '2026-09-08 14:18:19'),
(166, 1, '166. A patient blood pressure is 150/90mmHg, his pulse pressure will be', NULL, 'A) 50', 'B) 60', 'C) 70', 'D) 80', NULL, 'B', 1.00, 166, 1, '2026-09-08 14:18:19'),
(167, 1, '167. A Director Nursing Services who listens to suggestions sometimes but takes decision alone at other times is said to be', NULL, 'A) Situational leader', 'B) Autocratic leader', 'C) Democratic', 'D) Laissez-faire', NULL, 'A', 1.00, 167, 1, '2026-09-08 14:18:19'),
(168, 1, '168. A Nurse checked a client\'s pulse and found the rate to be above normal. The Nurse document his finding as', NULL, 'A) Tachypnoea', 'B) Hypercapnoea', 'C) Arrhythmia', 'D) Tarchycardia', NULL, 'D', 1.00, 168, 1, '2026-09-08 14:18:19'),
(169, 1, '169. The following are chest deformities can be detected during thorax examination EXCEPT', NULL, 'Tunnel chest', 'Barrel chest', 'Pigeon chest', 'Funnel chest', NULL, 'A', 1.00, 169, 1, '2026-09-08 14:18:19'),
(170, 1, '170. Pectus carinatum refers to:', NULL, 'Barrel chest', 'Funnel chest', 'Pigeon chest', 'Kyphoscoliosis', NULL, 'C', 1.00, 170, 1, '2026-09-08 14:18:19'),
(171, 1, '171. Common causes of hemoptysis in respiratory disorders include the following EXCEPT', NULL, 'Carcinoma of the lungs', 'Myocardial infarction', 'Pulmonary infarction', 'Pulmonary infection', NULL, 'B', 1.00, 171, 1, '2026-09-08 14:18:19'),
(172, 1, '172. Severe stabbing chest pain in pneumonia is as a result of:', NULL, 'Inflamed tissues of the lungs rubbing against each other with expiration', 'Inflamed tissues of the lungs rubbing against each other with inspiration', 'Irritation of mucus membrane', 'Secretion and cough', NULL, 'B', 1.00, 172, 1, '2026-09-08 14:18:19'),
(173, 1, '173. The following are disorders of myocardial blood supply EXCEPT', NULL, 'Myocardial infarction', 'Pericarditis', 'Angina pectoris', 'Coronary atherosclerosis', NULL, 'B', 1.00, 173, 1, '2026-09-08 14:18:19'),
(174, 1, '174. An irreversible intra-cellular injury to myocardium due to ischaemia resulting to necrosis is called', NULL, 'Angina pectoris', 'Coronary atherosclesrosis', 'Myocardial infarction', 'Myocarditis', NULL, 'C', 1.00, 174, 1, '2026-09-08 14:18:19'),
(175, 1, '175. The discrepancy between oxygen supply to the myocardium and energy expended causes which of the following problem of myocardial blood supply', NULL, 'Myocardial infarction', 'Angina pectoris', 'Coronary atherosclerosis', 'Myocarditis', NULL, 'B', 1.00, 175, 1, '2026-09-08 14:18:19'),
(176, 1, '176. Which of the following heart diseases is caused by complication of group A betahaemolytic streptococci', NULL, 'Pericarditis', 'Rheumatic heart disease', 'Rheumatoid arthritis', 'Endocarditis', NULL, 'B', 1.00, 176, 1, '2026-09-08 14:18:19'),
(177, 1, '177. A client with COPD reports steady weight loss and being \"too tired from just breathing to eat.\" Which of the following nursing diagnoses would be most appropriate when planning nutritional interventions for this client?', NULL, 'Altered nutrition: less than body requirements related to fatigue', 'Activity intolerance related to dyspnea', 'Weight loss related to COPD', 'Ineffective breathing pattern related to alveolar hypoventilation', NULL, 'A', 1.00, 177, 1, '2026-09-08 14:18:19'),
(178, 1, '178. Which of the following parameters should be checked before administering digoxin?', NULL, 'Apical pulse', 'Blood pressure', 'Radial pulse', 'Respiratory rate', NULL, 'C', 1.00, 178, 1, '2026-09-08 14:18:19'),
(179, 1, '179. ----------- are those diseases that cannot be transferred from person to person.', NULL, 'A) Communicable diseases', 'B) Non infectious diseases', 'C) Infectious diseases', 'D) Contagious diseases', NULL, 'B', 1.00, 179, 1, '2026-09-08 14:18:19'),
(180, 1, '180. Which of the following is/are the main reasons of malnutrition?', NULL, 'A) Limited access to adequate health care facilities', 'B) Limited access to food and its affordability', 'C) Geographical segregation and poor accessibility to markets due to lack of roads', 'D) All of these', NULL, 'D', 1.00, 180, 1, '2026-09-08 14:18:19'),
(181, 1, '181. A contagious sexually transmitted systemic disease characterized by clinical stages with intervening years of symptomless latency is called.', NULL, 'A) Gonorrhea', 'B) Vaginitis', 'C) PUD', 'D) Syphilis', NULL, 'D', 1.00, 181, 1, '2026-09-08 14:18:19'),
(182, 1, '182. A student is said to enumerate 4 endemic diseases, which one is odd?', NULL, 'A) HIV', 'B) Malaria', 'C) Pertussis', 'D) Meningitis', NULL, 'A', 1.00, 182, 1, '2026-09-08 14:18:19'),
(183, 1, '183. AIDS, Common cold, dengue fever, and influenza are good examples of:', NULL, 'A) Deficiency diseases', 'B) Infectious diseases', 'C) Physiological disorders', 'D) Non infectious diseases', NULL, 'B', 1.00, 183, 1, '2026-09-08 14:18:19'),
(184, 1, '184. Alzheimer\'s and Osteoporosis are examples of:', NULL, 'A) Communicable diseases', 'B) Non communicable diseases', 'C) Degenerative diseases', 'D) Hereditary diseases', NULL, 'C', 1.00, 184, 1, '2026-09-08 14:18:19'),
(185, 1, '185. Amoxicillin, Doxycycline, Azithromycin, And Penicillin are good examples of:', NULL, 'A) Prophylaxis', 'B) Vaccines', 'C) Antibiotics', 'D) Antisteroid.', NULL, 'C', 1.00, 185, 1, '2026-09-08 14:18:19'),
(186, 1, '186. An acute infectious disease of the central nervous system characterized by stiffness of the jaw, opisthotonos intermittent tonic spasm of the voluntary muscles is referred to as:', NULL, 'A) Tetanus', 'B) Poliomyelitis', 'C) Epilepsy', 'D) Convulsion', NULL, 'A', 1.00, 186, 1, '2026-09-08 14:18:19'),
(187, 1, '187. Canned food poisoning is commonly linked to which of the following microorganisms?', NULL, 'A) Staphylococcus aureus', 'B) Vibrio cholera', 'C) Taeniasolium', 'D) Clostridium botulinum', NULL, 'D', 1.00, 187, 1, '2026-09-08 14:18:19'),
(188, 1, '188. Certain lifestyles, habits or modes of living increase the risk of premature development of non communicable diseases', NULL, 'A) smoking', 'B) alcoholism', '(c) Drug abuse', '(d) All of the above', NULL, 'D', 1.00, 188, 1, '2026-09-08 14:18:19'),
(189, 1, '189. Cirrhosis is a condition that commonly affects:', NULL, 'A) The liver', 'B) The heart', 'C) The spleen', 'D) The kidneys', NULL, 'A', 1.00, 189, 1, '2026-09-08 14:18:19'),
(190, 1, '190. Cystic fibrosis and hemophilia are examples of:', NULL, 'A) Hereditary diseases', 'B) Degenerative diseases', 'C) Deficiency diseases', 'D) All of the above.', NULL, 'A', 1.00, 190, 1, '2026-09-08 14:18:19'),
(191, 1, '191. Deficiency in Vitamin E leads to:', NULL, 'A) Soft bone tissue', 'B) Bleeding gums', 'C) Weakness of muscles', 'D) Neurological disorder.', NULL, 'C', 1.00, 191, 1, '2026-09-08 14:18:19'),
(192, 1, '192. Diabetes mellitus is a disorder characterized by hyperglycemia. Which of the following is not the common characteristic feature of type2 diabetes mellitus?', NULL, 'A) Impaired insulin secretion', 'B) Increased Insulin resistance', 'C) Diabetic keto acidosis', 'D) Excessive hepatic glucose production', NULL, 'C', 1.00, 192, 1, '2026-09-08 14:18:19'),
(193, 1, '193. Environmental factor affecting the health of the community include the following, except:', NULL, 'A) Air pollution', 'B) Industrial waste disposal', 'C) Poor refuse disposal', 'D) Water pollution', NULL, 'A', 1.00, 193, 1, '2026-09-08 14:18:19'),
(194, 1, '194. Environmental factors affecting the health of the community include all, except', NULL, 'A) Air pollution', 'B) Poor refuse disposal', 'C) Industrial waste disposal', 'D) Water pollution', NULL, 'C', 1.00, 194, 1, '2026-09-08 14:18:19'),
(195, 1, '195. Furosemide is often used to treat hypertension. The reason for its administration is:', NULL, 'A) It blocks the sympathetic stimulation of the heart', 'B) It inhibits reabsorption of sodium and water in the kidney tubules and this control blood volume.', 'C) It helps in reabsorption of sodium and excretion of potassium.', 'D) It dilates peripheral blood vessels', NULL, 'B', 1.00, 195, 1, '2026-09-08 14:18:19'),
(196, 1, '196. Which of the following respiratory disease have the pathophysiology that involves the release of immunoglobulin E that may potentiate smooth muscle spasm causing narrowing of airways', NULL, 'A) Bronchiectasis', 'B) Bronchitis', 'C) COPD', 'D) Asthma', NULL, 'A', 1.00, 196, 1, '2026-09-08 14:18:19'),
(197, 1, '197. Which of the following risk factors for coronary artery disease cannot be corrected?', NULL, 'A) Cigarette smoking', 'B) Diabetes mellitus', 'C) Heredity', 'D) Hypertension', NULL, 'C', 1.00, 197, 1, '2026-09-08 14:18:19'),
(198, 1, '198. Widespread narrowing with spasm of the airways is known as', NULL, 'A) Bronchitis', 'B) Bronchiectasis', 'C) Atelectasis', 'D) Asthma', NULL, 'D', 1.00, 198, 1, '2026-09-08 14:18:19'),
(199, 1, '199. Name the association established to develop, refine, and promote the taxonomy of nursing diagnostic terminology used by nurses.', NULL, 'North America Nurses Association', 'America Nurses Association', 'ICAN', 'NMCN', NULL, 'A', 1.00, 199, 1, '2026-09-08 14:18:19'),
(200, 1, '200. This step of the nursing process includes the systematic collection of all subjective and objective data about the client in which the nurse focuses holistically on the client- physical, psychological, emotional, sociocultural, and spiritual.', NULL, 'Assessment', 'Planning', 'Implementation', 'Evaluation', NULL, 'A', 1.00, 200, 1, '2026-09-08 14:18:19'),
(201, 1, '201. Gordons typology is made up of ----------- components for human assessment', NULL, '8', '9', '10', '11', NULL, 'D', 1.00, 201, 1, '2026-09-08 14:18:19');
INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `question_image`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_answer`, `marks`, `question_order`, `status`, `created_at`) VALUES
(202, 1, '202. A ________________________ is performed to identify a life-threatening problem (choking, stab wound, heart attack etc).', NULL, 'Initial assessment', 'Focus assessment', 'Emergency assessment', 'Critical assessment', NULL, 'C', 1.00, 202, 1, '2026-09-08 14:18:19'),
(203, 1, '203. Information verbalized or stated by the patient is called', NULL, 'Objective', 'Subjective', 'Integral', 'Holistic', NULL, 'B', 1.00, 203, 1, '2026-09-08 14:18:19'),
(204, 1, '204. Observable and measurable information is known as', NULL, 'Objective data', 'Subjective data', 'Visible data', 'Obscured data', NULL, 'A', 1.00, 204, 1, '2026-09-08 14:18:19'),
(205, 1, '205. Identify the stage where you determine if the patient has achieved the expected outcomes.', NULL, 'Implementation', 'Assessment', 'Diagnosis', 'Evaluation', NULL, 'D', 1.00, 205, 1, '2026-09-08 14:18:19'),
(206, 1, '206. Which of the following is an objective data', NULL, 'Temperature reading of 38 degree centigrade', 'Nausea and vomiting', 'Feeling of pain', 'Feeling of sleepiness', NULL, 'A', 1.00, 206, 1, '2026-09-08 14:18:19'),
(207, 1, '207. A 30-year-old man is placed on 5% Dextrose Saline 500mls to run for 6hours. Given that the drip factor is 15 drops/min. what will be flow rate?', NULL, 'A) 21 drops/min', 'B) 24 drops/min', 'C) 28 drops/min', 'D) 30 drops/min', NULL, 'A', 1.00, 207, 1, '2026-09-08 14:18:19'),
(208, 1, '208. A diet high in saturated fats can be linked to', NULL, 'A) Renal failure', 'B) Diabetes mellitus', 'C) Cardiovascular diseases', 'D) Ulcerative colitis', NULL, 'C', 1.00, 208, 1, '2026-09-08 14:18:19'),
(209, 1, '209. A sprain refers to an injury to', NULL, 'A) Muscles', 'B) Ligaments', 'C) Tendons', 'D) Fascia', NULL, 'B', 1.00, 209, 1, '2026-09-08 14:18:19'),
(210, 1, '210. A unit of energy indicating the amount contained in food is', NULL, 'A) kilojoule', 'B) Calorie', 'C) kilogram', 'D) Ounces', NULL, 'B', 1.00, 210, 1, '2026-09-08 14:18:19'),
(211, 1, '211. Amylases in the saliva begin the breakdown of carbohydrates into', NULL, 'A) Simple sugar', 'B) Compound sugar', 'C) Polypeptides', 'D) glycerol', NULL, 'A', 1.00, 211, 1, '2026-09-08 14:18:19'),
(212, 1, '212. An adult is been treated for peptic ulcer. The physician prescribed Cimetidine (Targamet) for one of the following reasons', NULL, 'A) It blocks the secretion of gastric hydrochloric acids', 'B) It coats the gastric mucosa with a protective membrane', 'C) It increases the sensitivity of histamine (H2) receptor', 'D) It neutralizes acids in the stomach', NULL, 'A', 1.00, 212, 1, '2026-09-08 14:18:19'),
(213, 1, '213. An agonist that produces maximal effect and has high efficacy is called', NULL, 'A) Partial agonist', 'B) Antagonist', 'C) Agonist-antagonist', 'D) Full agonist', NULL, 'D', 1.00, 213, 1, '2026-09-08 14:18:19'),
(214, 1, '214. An ineffective human relations skill of a manager is', NULL, 'A) Indifference', 'B) Broadmindedness', 'C) Empathy', 'D) Selflessness', NULL, 'A', 1.00, 214, 1, '2026-09-08 14:18:19'),
(215, 1, '215. Another name for knee-chest position is', NULL, 'A) Genu-dorsal', 'B) Genu-pectoral', 'C) Lithotomy', 'D) Sim\'s', NULL, 'B', 1.00, 215, 1, '2026-09-08 14:18:19'),
(216, 1, '216. Antidiuretic hormone (ADH)', NULL, 'A) Increases water absorption', 'B) Increases sodium reabsorption', 'C) Increases water and sodium reabsorption', 'D) Decreases water reabsorption', NULL, 'C', 1.00, 216, 1, '2026-09-08 14:18:19'),
(217, 1, '217. Back care is best described as', NULL, 'A) Caring for the back by means of massage', 'B) Washing of the back', 'C) Application of cold compress to the back', 'D) Application of hot compress to the back', NULL, 'A', 1.00, 217, 1, '2026-09-08 14:18:19'),
(218, 1, '218. Which of the following neurotransmitters exhibits excitatory action, transmit pleasurable sensation and inhibits pain transmission?', NULL, 'Gama-aminobutyric acid (GABA)', 'Dopamine', 'Serotonin', 'Endorphine', NULL, 'D', 1.00, 218, 1, '2026-09-08 14:18:19'),
(219, 1, '219. After a head injury a patient has lost his recent memory. Which lobe of the brain is affected?', NULL, 'Frontal', 'Occipital', 'Parietal', 'Temporal', NULL, 'D', 1.00, 219, 1, '2026-09-08 14:18:19'),
(220, 1, '220. What is the first thing a Nurse should assess to determine the presence of neurological changes?', NULL, 'Vital signs', 'Level of consciousness', 'Mental status', 'Reflexes', NULL, 'B', 1.00, 220, 1, '2026-09-08 14:18:19'),
(221, 1, '221. A patient is admitted with epilepsy for investigation suddenly started having a tonic-clonic seizure. The nurse should:', NULL, 'Lay the patient on the bed, remove pillows, raise side rails and insert an oral airways', 'Lay the patient on the bed, remove pillows, raise side rails and retrain his arms', 'Lay the patient on the bed, place patient on one side, remove pillows, raise side rails and ensure patent airways', 'Lay the patient to the bed, place the patient on his back, remove pillows, raise the side rails and insert an oral airways', NULL, 'C', 1.00, 221, 1, '2026-09-08 14:18:19'),
(222, 1, '222. The presence of which of the following antibodies to hepatitis A virus confirm the diagnosis of acute hepatitis A', NULL, 'IgG', 'IgM', 'IgE', 'IgD', NULL, 'B', 1.00, 222, 1, '2026-09-08 14:18:19'),
(223, 1, '223. The risk of nosocomial infection of urinary tract infection in spinal cord injury patients is BEST reduced by:', NULL, 'Prophylactic antibiotics', 'Bladder instillation of antiseptic', 'Intermittent catheterization', 'Placement of all patients with urinary catheter in the same area', NULL, 'C', 1.00, 223, 1, '2026-09-08 14:18:19'),
(224, 1, '224. A patient complain of chest pain, difficulty in breathing, nausea and vomiting. The nurse will be correct if he/she document these data as:', NULL, 'Subjective data', 'Observable data', 'Objective data', 'General information', NULL, 'C', 1.00, 224, 1, '2026-09-08 14:18:19'),
(225, 1, '225. Which of the following is not a blood-borne hepatitis', NULL, 'Hepatitis A', 'Hepatitis B', 'Hepatitis C', 'Hepatitis D', NULL, 'A', 1.00, 225, 1, '2026-09-08 14:18:19'),
(226, 1, '226. What is the body mass index (BMI) of Mrs Bobai whose weight is 60kg and measuring 1.6m in height?', NULL, '23.44mg/m2', '23.44kg/cm3', '23.44kg/m2', '23.44kg/mm3', NULL, 'C', 1.00, 226, 1, '2026-09-08 14:18:19'),
(227, 1, '227. The disease causing Ebola virus in humans include the following EXCEPT', NULL, 'Bundibugyo virus', 'Sudan virus', 'Tai virus', 'Reston virus', NULL, 'D', 1.00, 227, 1, '2026-09-08 14:18:19'),
(228, 1, '228. The following constitute peripheral organs of the immune system EXCEPT', NULL, 'Thymus', 'Spleen', 'Appendix', 'Lymphnodes', NULL, 'A', 1.00, 228, 1, '2026-09-08 14:18:19'),
(229, 1, '229. Immunoglobilings such as IgG, IgM, IgA etc constitute what kind of immunity', NULL, 'Humoral immunity', 'Cell mediated immune response', 'Non-specific defence', 'Lymphokine chemotactic factor', NULL, 'A', 1.00, 229, 1, '2026-09-08 14:18:19'),
(230, 1, '230. The act of taking a life to relieve suffering is known as', NULL, 'Eutanasia', 'Euthernasia', 'Euthanasia', 'Euthanacia', NULL, 'C', 1.00, 230, 1, '2026-09-08 14:18:19'),
(231, 1, '231. The combination of drug that interacts with another to produce increased activity which is greater than the sum of the effects of the two drugs given separately is called', NULL, 'Synergism', 'Summation', 'Addition', 'Combined therapy', NULL, 'A', 1.00, 231, 1, '2026-09-08 14:18:19'),
(232, 1, '232. A condition associated with repeated administration of a drug harmful to the individual and sometimes to the society is known as', NULL, 'Drug repetition', 'Drug dependence', 'Drug reliance', 'Drug hazards', NULL, 'D', 1.00, 232, 1, '2026-09-08 14:18:19'),
(233, 1, '233. When dairy products like milk are taken with antibiotics like tetracycline', NULL, 'There is decreased absorption of the drug in the stomach', 'There is increased absorption of the drug in the stomach', 'The potency of the drug is increased', 'The potency of the drug is decreased', NULL, 'A', 1.00, 233, 1, '2026-09-08 14:18:19'),
(234, 1, '234. Which of the following immunoglobulins is secreted into the colostrum, saliva and intestinal juice', NULL, 'IgA', 'IgG', 'IgM', 'IgE', NULL, 'A', 1.00, 234, 1, '2026-09-08 14:18:19'),
(235, 1, '235. Body Mass Index is a -------- method of nutritional assessment', NULL, 'Anthropometric', 'Biochemical', 'Clinical', 'Daily dietary intake', NULL, 'A', 1.00, 235, 1, '2026-09-08 14:18:19'),
(236, 1, '236. Pathophysiologically, HIV primarily affect which of the immune cell', NULL, 'Humoral immunity', 'Cell mediated immune response', 'Platelets', 'Innate immunity', NULL, 'B', 1.00, 236, 1, '2026-09-08 14:18:19'),
(237, 1, '237. Stage four (4) of HIV infection is considered an indicator of full blown AIDS with the following clinical manifestation', NULL, 'Minor cutaneous manifestation and recurrent upper respiratory tract infection', 'Asymptomatic', 'Toxoplasmosis of the brain candidiasis of oesophagus, bronchi or lungs and kaposis sarcoma', 'Unexplained chronic diarrhea longer than a month, severe bacterial infection and pulmonary tuberculosis', NULL, 'C', 1.00, 237, 1, '2026-09-08 14:18:19'),
(238, 1, '238. Which of the following HIV test is more specific and very sensitive and is used to confirm positive ELISA test result', NULL, 'Western blood test', 'Western blot test', 'ELISA test', 'CD4 count and viral load test', NULL, 'B', 1.00, 238, 1, '2026-09-08 14:18:19'),
(239, 1, '239. In an outbreak of probable food-borne illness, patient developed signs and symptoms 2 to 4 hours after eating salad. The MOST likely causative organism is', NULL, 'Salmonella enteritis', 'Staphylococcus aureus', 'Vibrio parahaemolyticus', 'Clostridium perfringes', NULL, 'B', 1.00, 239, 1, '2026-09-08 14:18:19'),
(240, 1, '240. A weak or bounding pulse signifies which pulse characteristics', NULL, 'Rhythm', 'Pulse volume', 'Rate', 'Condition of the arterial wall', NULL, 'B', 1.00, 240, 1, '2026-09-08 14:18:19'),
(241, 1, '241. When hernia can be pushed back into the abdominal cavity, it is called', NULL, 'Incarcerated', 'Irreducible', 'Reducible', 'Strangulated', NULL, 'C', 1.00, 241, 1, '2026-09-08 14:18:19'),
(242, 1, '242. In acute pancreatitis, the most specific pathophysiology is', NULL, 'Autodigestion', 'Over production of enzymes', 'Lack of secretion of enzymes', 'Cellular disorganization', NULL, 'B', 1.00, 242, 1, '2026-09-08 14:18:19'),
(243, 1, '243. The following are prerenal causes of acute renal failure EXCEPT', NULL, 'Haemorrhage', 'Urinary tract infection', 'Myocardial infarction', 'Sepsis', NULL, 'C', 1.00, 243, 1, '2026-09-08 14:18:19'),
(244, 1, '244. The correct position to palpate the apical pulse is at:', NULL, 'Left mid-clavicular line at the level of second intercostal space', 'Left mid-clavicular line at the level of fifth intercostal space', 'Left axillary line at the level of fifth intercostal space', 'Left mid-clavicular line at the level of seventh intercostal space', NULL, 'B', 1.00, 244, 1, '2026-09-08 14:18:19'),
(245, 1, '245. Furosemide is often used to treat hypertension. The reason for furosemide administration is:', NULL, 'It blocks the sympathetic stimulation of the heart', 'It inhibits reabsorption of sodium and water in the kidney tubule and thus controls blood volume', 'It helps in reabsorption of sodium and excretion of potassium', 'It dilates peripheral blood vessels', NULL, 'B', 1.00, 245, 1, '2026-09-08 14:18:19'),
(246, 1, '246. An elderly patient with generalized arteriosclerosis complains to the nurse about inability to sleep at night due to coldness of feet. What should the nurse adviced', NULL, 'To rub the feet gently to improve circulation before sleep', 'To place light blanket over feet at night', 'To place a covered hot water bottle below the feet at night', 'To take a warm footbath before going to bed', NULL, 'B', 1.00, 246, 1, '2026-09-08 14:18:19'),
(247, 1, '247. A nurse is assessing a 70-year old patient who complains of recent chest pain. Which statement by the patient is strongly indicate angina pain', NULL, 'The pain occurred when I was walking briskly in the morning\"', 'The pain increased when I was taking deep breath\"', 'The pain resolved after I drank a cup of cold milk\"', 'The pain lasted about 50 minutes\"', NULL, 'B', 1.00, 247, 1, '2026-09-08 14:18:19'),
(248, 1, '248. Which of the following compensatory mechanism is involved in congestic heart failure that lead to inappropriate fluid retention and additional workload of the heart', NULL, 'Ventricular dilation', 'Ventricular hypertrophy', 'Neurohormonal response', 'Sympathetic nervous system activation', NULL, 'C', 1.00, 248, 1, '2026-09-08 14:18:19'),
(249, 1, '249. The most accurate reflection of the body temperature is achieved by', NULL, 'Taking temperature by mouth', 'Taking temperature by rectum', 'Taking temperature per axilla', 'Taking temperature at cubita fossa', NULL, 'B', 1.00, 249, 1, '2026-09-08 14:18:19'),
(250, 1, '250. Which of the following is incorrect regarding the establishment of priorities in patients', NULL, 'Airways should always be given highest priority', 'Clients with unstable condition should be given priority over those who are stable', 'Attend to equipment and apparatus like IV fluids and drainage tubes', 'Actual problems take precedence over potential problems', NULL, 'C', 1.00, 250, 1, '2026-09-08 14:18:19'),
(251, 1, '251. A patient is positioned by the nurse because of complaints of difficulty in breathing. The action of the nurse indicate what phase of nursing process', NULL, 'Implementation', 'Evaluation', 'Assessment', 'Planning', NULL, 'A', 1.00, 251, 1, '2026-09-08 14:18:19'),
(252, 1, '252. The first component of a nursing diagnosis statement consist of', NULL, 'Etiology', 'Signs and symptoms', 'Problem', 'Related factors', NULL, 'C', 1.00, 252, 1, '2026-09-08 14:18:19'),
(253, 1, '253. In the nursing diagnosis \"impaired physical mobility related to joint stiffness as evidenced by limited range of motion and difficulty turning\", the etiology of the problem is', NULL, 'Limited range of motion', 'Joint stiffness', 'Physical mobility', 'Difficulty turning', NULL, 'B', 1.00, 253, 1, '2026-09-08 14:18:19'),
(254, 1, '254. The nursing diagnosis of a patient with myocardial infarction is activity intolerance. The plan of care include the patient outcome criterion of', NULL, 'Agreeing to discontinue smoking', 'Ambulating 50 feet without experiencing dyspnoea', 'Experiencing no dyspnoea on exertion', 'Tolerating activity well', NULL, 'B', 1.00, 254, 1, '2026-09-08 14:18:19'),
(255, 1, '255. Which action occur primarily during evaluation phase of nursing process', NULL, 'Data collection', 'Decision making and judgement', 'Priority-setting and expected outcome', 'Reassessment and audit', NULL, 'D', 1.00, 255, 1, '2026-09-08 14:18:19'),
(256, 2, 'The following are essential needs of a child except …', NULL, 'Security needs', 'Ethical needs', 'Emotional needs', 'Self-esteem needs', NULL, 'D', 1.00, 1, 1, '2026-09-09 11:30:23'),
(257, 2, 'Weaning is best started at …', NULL, '6 months', '4-6 months', '9 months', '9-12 months', NULL, 'A', 1.00, 2, 1, '2026-09-09 11:30:23'),
(258, 2, 'The following are possible causes of malnutrition in West Africa except …', NULL, 'Poverty', 'Ignorance', 'Variability of food', 'Harmful food habits', NULL, 'C', 1.00, 3, 1, '2026-09-09 11:30:23'),
(259, 2, 'The causative organism for urinary tract infection is usually …', NULL, 'Hemolytic streptococcus', 'Streptococcal faecalis', 'Staphylococcus aureus', 'Escherichia coli', NULL, 'D', 1.00, 4, 1, '2026-09-09 11:30:23'),
(260, 2, 'All the following conditions increase the likelihood of venous thrombosis and danger of pulmonary embolism except …', NULL, 'Caesarian section', 'Clients on hematinic', 'Age over 35 years', 'Use of estrogen to suppress lactation', NULL, 'D', 1.00, 5, 1, '2026-09-09 11:30:23'),
(261, 2, 'Heparin is the drug of choice in the management of deep venous thrombosis. it\'s effect is reversed by intravenous injection of …', NULL, 'Ferrous sulphate', 'Potassium sulphate', 'Magnesium sulphate', 'Protamine sulphate', NULL, 'D', 1.00, 6, 1, '2026-09-09 11:30:23'),
(262, 2, 'Indications for classical caesarian section include gestation of less than about …', NULL, '32 weeks', '28 weeks', '34 weeks', '36 weeks', NULL, 'B', 1.00, 7, 1, '2026-09-09 11:30:23'),
(263, 2, 'The definite indications for elective caesarian section include …', NULL, 'Cephalon-pelvic disproportion', 'Ante-partum hemorrhage', 'Major degrees of placenta previa', 'Triplets or quadruplets', NULL, 'C', 1.00, 8, 1, '2026-09-09 11:30:23'),
(264, 2, '………………………. Is effective in neutralizing the stomach acid prior to caesarian section', NULL, 'Proton-pump inhibitor', 'Antacid', 'Diuretics', 'Propranolol', NULL, 'B', 1.00, 9, 1, '2026-09-09 11:30:23'),
(265, 2, 'An inborn error of metabolism is a permanently inherited condition which is due to ……', NULL, 'Hormonal abnormality', 'Galactose abnormality', 'An enzyme abnormality', 'Chromosomal abnormality', NULL, 'C', 1.00, 10, 1, '2026-09-09 11:30:23'),
(266, 2, 'The test most frequently used in the detection of phenyl ketonuria is …', NULL, 'Tyrosine test', 'Roman\'s test', 'Calista Roy\'s test', 'Guthrie test', NULL, 'A', 1.00, 11, 1, '2026-09-09 11:30:23'),
(267, 2, 'Hospice is example of a …', NULL, 'Rehabilitation center', 'Primary health care center', 'Hospital', 'Extended long term care facilities', NULL, 'D', 1.00, 12, 1, '2026-09-09 11:30:23'),
(268, 2, 'What factors will you consider before advising a patient for amputation of a limb?', NULL, 'The part affected weight and age', 'Age of the patients, severity of injury and time', 'Source heights and weights', 'Sex, height, weight of patient', NULL, 'B', 1.00, 13, 1, '2026-09-09 11:30:23'),
(269, 2, 'The posterior 5/6th of the outer coat of the eye ball is called …', NULL, 'Sclera', 'Retina', 'Conica', 'Iris', NULL, 'A', 1.00, 14, 1, '2026-09-09 11:30:23'),
(270, 2, 'Which of the following bile component is responsible for effective digestion of fat in the small intestine?', NULL, 'Bile salt', 'Cholesterol', 'Lecithin', 'Bilirubin', NULL, 'A', 1.00, 15, 1, '2026-09-09 11:30:23'),
(271, 2, 'A child with wasted muscle hair change and oedema may be suffering from …', NULL, 'marasmus', 'kwashiorkor', 'pellagra', 'vitamin A deficiency', NULL, 'B', 1.00, 16, 1, '2026-09-09 11:30:23'),
(272, 2, 'The food item which contains the lowest potassium is …', NULL, 'spinach', 'apple', 'carrot', 'avocado', NULL, 'B', 1.00, 17, 1, '2026-09-09 11:30:23'),
(273, 2, 'All patients with \'gout\' should avoid …except', NULL, 'processed cheese', 'organ meat', 'yellow root vegetable', 'shell fish', NULL, 'A', 1.00, 18, 1, '2026-09-09 11:30:23'),
(274, 2, 'A diagnosis of pneumonia was is typically achieved by a diagnostic test called …', NULL, 'sputum culture and sensitivity', 'blood culture', 'arterial blood gas analysis', 'chest X-Ray', NULL, 'A', 1.00, 19, 1, '2026-09-09 11:30:23'),
(275, 2, 'Respiratory disorder most commonly seen in the first 24 to 48 hours after surgery is …', NULL, 'pneumonia', 'pneumothorax', 'atelectasis', 'bronchitis', NULL, 'A', 1.00, 20, 1, '2026-09-09 11:30:23'),
(276, 2, 'Loss of water through the skin and the respiratory tract is referred to as …', NULL, 'sensible water loss', 'hemorrhage', 'insensible water', 'sweating', NULL, 'C', 1.00, 21, 1, '2026-09-09 11:30:23'),
(277, 2, 'Eversion movement means', NULL, '(a) Moving toward the midline of the body', '(b) Turning the palm of the hand down', '(c) Turning the sole of the foot outwards', '(d) Turning the palm of the hand up', NULL, 'C', 1.00, 22, 1, '2026-09-09 11:30:23'),
(278, 2, 'Male infertility may be due to the following EXCEPT', NULL, '(a) Endocrine disorder', '(b) Obstruction of the deferent duct', '(c) Malnutrition', '(d) Vasectomy', NULL, 'C', 1.00, 23, 1, '2026-09-09 11:30:23'),
(279, 2, 'The narrowing of the prepuce in a male child which may cause straining at micturition is called', NULL, '(a) Ectopic bladder', '(b) Polydactylism', '(c) Phimosis', '(d) Talipes', NULL, 'C', 1.00, 24, 1, '2026-09-09 11:30:23'),
(280, 2, 'A condition in which the male urethra opens on the dorsum of the penis is called', NULL, '(a) Persistent urachus', '(b) Hypospadias', '(c) Epispadius', '(d) Achondroplasia', NULL, 'B', 1.00, 25, 1, '2026-09-09 11:30:23'),
(281, 2, 'Ophthalmia neonatorum is defined as a purulent discharge from the eyes of an infant within the first …', NULL, '(a) 7 days', '(b) 14 days', '(c) 21 days', '(d) 30 days', NULL, 'D', 1.00, 26, 1, '2026-09-09 11:30:23'),
(282, 2, 'Omphalitis is an infection of the …', NULL, 'Meninges', 'Umbilical cord', 'Bones', 'Blood', NULL, 'B', 1.00, 27, 1, '2026-09-09 11:30:23'),
(283, 2, 'Immediate clinical manifestation of diarrhea is …', NULL, 'Heart failure', 'Stupor', 'Dehydration', 'Shock', NULL, 'C', 1.00, 28, 1, '2026-09-09 11:30:23'),
(284, 2, 'The largest gland in the body is the …', NULL, 'Brain', 'Kidney', 'Liver', 'Pancreas', NULL, 'C', 1.00, 29, 1, '2026-09-09 11:30:23'),
(285, 2, 'The following are endocrine glands except …', NULL, 'The brain', 'The liver', 'The eyes', 'The heart', NULL, 'D', 1.00, 30, 1, '2026-09-09 11:30:23'),
(286, 2, 'The following hormones are protein except …', NULL, 'Prolactin', 'Growth hormone', 'Parathyroid hormone', 'Anti-diuretic hormone', NULL, 'B', 1.00, 31, 1, '2026-09-09 11:30:23'),
(287, 2, 'The process in which nervous system converts information to a meaningful patter of action potential in the sense organ is called …', NULL, 'Sensible coining', 'Sensible coding', 'Sensory coding', 'Nervous coding', NULL, 'D', 1.00, 32, 1, '2026-09-09 11:30:23'),
(288, 2, 'Failure of the testicle to descend in a male infant is called', NULL, '(a) Hernia', '(b) Phimosis', '(c) Cryptorchidism', 'd) Meniere disease', NULL, 'C', 1.00, 33, 1, '2026-09-09 11:30:23'),
(289, 2, 'Persistent painful erection of the penis without sexual arousal is called …', NULL, '(a Hernia', '(b) Monordism', '(c) Circumcision erection', '(d) Priapism', NULL, 'D', 1.00, 34, 1, '2026-09-09 11:30:23'),
(290, 2, 'The adult human skeleton usually consists...... Bones', NULL, 'a) 406', '(b) 306', '(c) 206', '(d) 106', NULL, 'C', 1.00, 35, 1, '2026-09-09 11:30:23'),
(291, 2, 'The oedema which occurs in nephrotic syndrome is due to', NULL, '(a) Increase nephrotic pressure caused by sodium excretion', '(b) Decreasedosmotic pressure caused by decreased serum proteins', '(c) Increasedhydrostatic pressure caused by sodium retention', '(d) Decreased aldosterone secretion caused by adrenal insufficiency', NULL, 'C', 1.00, 36, 1, '2026-09-09 11:30:23'),
(292, 2, 'Air that develops in the pleural space is referred to as', NULL, 'Pleural effusion', 'Haemothorax', 'Pneumothorax', 'Air in situ', NULL, 'C', 1.00, 37, 1, '2026-09-09 11:30:23'),
(293, 2, 'Rectal examination may be carried out in the following positions EXCEPT', NULL, '(a) Sim\'s position', '(b) Supine position', '(c) Lithotomy position', '(d) knee-chest position', NULL, 'B', 1.00, 38, 1, '2026-09-09 11:30:23'),
(294, 2, 'Examination of pouch of Douglas with the aid of endoscope is called …', NULL, '(a) Uteroscopy', '(b) Culdoscopy', '(c) Laparoscopy', '(d) Pouchoscopy', NULL, 'B', 1.00, 39, 1, '2026-09-09 11:30:23'),
(295, 2, 'Galactorrhea is a result of over production of', NULL, '(a) Growth hormone', 'b) Thyroid stimulating hormone', '(c) Protactin', '(d) Adrenocorticotrophic hormone', NULL, 'C', 1.00, 40, 1, '2026-09-09 11:30:23'),
(296, 2, 'Projectile vomiting is mostly seen in', NULL, '(a) Pyloric stenosis', '(b) intussusception', '(c) Oesophageal stenosis', '(d Overweight', NULL, 'A', 1.00, 41, 1, '2026-09-09 11:30:23'),
(297, 2, 'The Body Mass Index range of 30-34.9 can be classified as', NULL, '(a) Obesity classi', '(b) obesity classii', '(c) extreme obesity', 'd) Overweight', NULL, 'B', 1.00, 42, 1, '2026-09-09 11:30:23'),
(298, 2, 'The main treatment of incomplete abortion is …', NULL, '(a) Ergometrine injection', '(b) Evacuation of the uterus', '(c) Bed rest and sedation', 'd) Syntocinon infusion', NULL, 'B', 1.00, 43, 1, '2026-09-09 11:30:23'),
(299, 2, 'The spinal cord extends from magnum at the skull to the', NULL, '(a) Second cervical vertebra', '(b) Fifth thoracic vertebra', '(c) First lumber vertebra', '(d) Eleventh thoracic vertebra', NULL, 'C', 1.00, 44, 1, '2026-09-09 11:30:23'),
(300, 2, 'The term used in describing hypoactive sexual desire is …', NULL, '(a) Organism disorder', '(b) Sexual desire disorder', '(c) Sexual tolerance disorder', '(d) Sexual arousal disorder', NULL, 'D', 1.00, 45, 1, '2026-09-09 11:30:23'),
(301, 2, 'Which of the following are ongoing problem for many homeless individuals?', NULL, '(a) Alcoholism', '(b)HIV and other STDs', '(c) Conditions related to dietary deficiencies', '(d) All of the above', NULL, 'D', 1.00, 46, 1, '2026-09-09 11:30:23'),
(302, 2, 'Using drugs that is harmful to self refers to …', NULL, '(a Drug dependence', '(b) Drug habituation', '(c) Drug tolerance', 'd) Drug abuse', NULL, 'D', 1.00, 47, 1, '2026-09-09 11:30:23'),
(303, 2, 'An individual whose drug history include a sensation of floating in space may be abusing …', NULL, '(a) Cocaine', '(h) Cannabis', '(c) Heroine', '(d) Lebrium', NULL, 'A', 1.00, 48, 1, '2026-09-09 11:30:23'),
(304, 2, 'A rape victim does not manifest one of these signs', NULL, '(c) Confusion', '(b) Fear and anxiety', 'c) Over complaints to adult authority', '(d) Agitation', NULL, 'D', 1.00, 49, 1, '2026-09-09 11:30:23'),
(305, 2, 'Which of the following is not a complication of alcoholism?', NULL, 'a) Convert sensitization', '(b) Confabulation', '(c) Korskoff syndrome', '(d) Delirium tremens', NULL, 'C', 1.00, 50, 1, '2026-09-09 11:30:23'),
(306, 2, 'Tolerance is the tendency to …', NULL, '(a) Increase drug dosage consumption', '(b) Decrease drug dosage consumption', '(c) Maintain normal drug dosage', '(d) Maintain low drug dosage', NULL, 'C', 1.00, 51, 1, '2026-09-09 11:30:23'),
(307, 2, 'The Defense mechanism that makes an individual to retreat to earlier age is …', NULL, '(a) Rationalization', '(b) Regression', '(c) Suppression', '(d) Sublimation', NULL, 'B', 1.00, 52, 1, '2026-09-09 11:30:23'),
(308, 2, 'Involuntary rhythmic eye movement is referred toas …', NULL, '(a) Tinnitus', 'b) Nystagmus', '(c) Otorrhoea', '(d) Otalgia', NULL, 'B', 1.00, 53, 1, '2026-09-09 11:30:23'),
(309, 2, 'ln sickle cell disease glutamic acid is replaced by another amino acid called …', NULL, '(a) valine', '(b) Niacin', '(c) Isoleucine', '(d) Myosin', NULL, 'A', 1.00, 54, 1, '2026-09-09 11:30:23'),
(310, 2, 'The diagnosis of Hepatitis B infection is usually confirmed by …', NULL, '(a) Stool culture', '(b) Serum profile', '(c) Blood culture', '(d) Urine culture', NULL, 'C', 1.00, 55, 1, '2026-09-09 11:30:23'),
(311, 2, 'Angina pectoris most likely occur as a result of ………………………………..', NULL, 'Dehydration', 'Coronary artery disease', 'Hemorrhage', 'lysis', NULL, 'B', 1.00, 56, 1, '2026-09-09 11:30:23'),
(312, 2, 'Which of the following is NOT an example of dry heat?', NULL, '(a) Hot air oven.', 'b) Incineration', '(c) Steam under pressure', '(d) Burning', NULL, 'D', 1.00, 57, 1, '2026-09-09 11:30:23'),
(313, 2, 'The following are effects of burns EXCEPT', NULL, '(a) Infection', '(b) Deformity', 'c. Thrombosis', '(d) Shock', NULL, 'C', 1.00, 58, 1, '2026-09-09 11:30:23'),
(314, 2, 'The following are pressure points on the body EXCEPT', NULL, '(a)Vaginal artery', '(b) Temporal artery', '(c) facial artery', '(d) Brachial artery.', NULL, 'C', 1.00, 59, 1, '2026-09-09 11:30:23'),
(315, 2, 'Kopliks spots are seen in one of the', NULL, '(a) Malaria', '(b) Measles.', '(c) Diphtheria', '(d) Yellow fever', NULL, 'B', 1.00, 60, 1, '2026-09-09 11:30:23'),
(316, 2, 'The following are layers of the heart EXCEPT', NULL, '(a)Endocardium', '(b) Myocardium', 'C) Epicardium', 'd. Epimetrium', NULL, 'D', 1.00, 61, 1, '2026-09-09 11:30:23'),
(317, 2, 'One of the following is a normal female pelvis', NULL, '(a) Android', '(b) Gynaecoid', '(c) Anthropoid', '(d) Platypelloid', NULL, 'B', 1.00, 62, 1, '2026-09-09 11:30:23'),
(318, 2, 'Health of the community is affected by the following EXCEPT', NULL, '(a) ecconomy, culture and disease prevalence', '(b) Nutrition, climate and source of water supply', '(c) Refuse disposal, environmental pollution and road network', '(d)Landmass, presence of public and private schools.', NULL, 'A', 1.00, 63, 1, '2026-09-09 11:30:23'),
(319, 2, 'Administration of drug into the sub-arachnoid space is referred to as …………. administration', NULL, '(a) intradermal', '(b) Hypodermal', '(c) intramuscular', '(d) intrathecal', NULL, 'D', 1.00, 64, 1, '2026-09-09 11:30:23'),
(320, 2, 'Which of the following best describes essential drugs?', NULL, '(a) Drugs that must be acquired at all cost', 'b) Drugs that reflect the true health needs of the people', '(c Drug that must be imported to save the lives of people', '(d) Drugs that are very potent', NULL, 'B', 1.00, 65, 1, '2026-09-09 11:30:23'),
(321, 2, 'BSA mean', NULL, '(a) Basal Surface Area', '(b) Basic Surface Area', '(c) Body Surface Area', '(d) body Size Area', NULL, 'C', 1.00, 66, 1, '2026-09-09 11:30:23'),
(322, 2, 'The drug administered for systemic effect can be given through the following routes EXCEPT-', NULL, '(a) Intravenous', '(b) Intramuscular', '(C) Oral', '(d Topical', NULL, 'D', 1.00, 67, 1, '2026-09-09 11:30:23'),
(323, 2, 'One of the following is not a sign/symptom of pressure sore', NULL, '(a) Discoloration of the skin.', '(b) Smarting or stinging pain over the area.', 'C) Development of lump over the area', '(d) Abrasion of the skin.', NULL, 'C', 1.00, 68, 1, '2026-09-09 11:30:23'),
(324, 2, 'The name given to insufficiency of oxygen in the blood is:', NULL, '(a) shock', '(b) cardiac failure.', '(c) hypotonic.', '(d) Hypoxia', NULL, 'D', 1.00, 69, 1, '2026-09-09 11:30:23'),
(325, 2, 'Response of the body tissues to injury is:', NULL, '(a) Adaptation.', '(6) Resistance', '(c) Inflammation', '(d) Reaction formation.', NULL, 'D', 1.00, 70, 1, '2026-09-09 11:30:23'),
(326, 2, 'PUS consists of the following EXCEPT', NULL, '(a) Normal tissue cells', '(b) Damaged tissue cells', '(c) Dead leucocytes', '(d) Living micro-organisms', NULL, 'A', 1.00, 71, 1, '2026-09-09 11:30:23'),
(327, 2, 'Mental illness is defined as', NULL, '(a) A disorder of body and mind', '(b) A disorder of mind resulting in changes in behavior, attitude and interaction', '(c) Splitting of personality', '(d) inability to cope with societal expectations', NULL, 'B', 1.00, 72, 1, '2026-09-09 11:30:23'),
(328, 2, 'The ability of an individual to adjust to varied situations and to respond to varied experience with a sense of purpose is', NULL, '(a) Mental status', '(b) Mental state', '(c) Mental health', '(d) Mental ability', NULL, 'C', 1.00, 73, 1, '2026-09-09 11:30:23'),
(329, 2, 'Zidovudine is prescribed as an', NULL, '(a) Anti-tuberculous drug', '(b) Antiviral agent to decrease replication of HIV', '(c) Antifungal for thrush', '(d) Antibiotic against pneumocystic carinii', NULL, 'B', 1.00, 74, 1, '2026-09-09 11:30:23'),
(330, 2, 'Who among the following is referred to as the father of microbiology?', NULL, '(a) Father Spallanzane', '(b) Anton van Lauwenhork', '(c) Robert Koch', '(d) John Tyndall', NULL, 'B', 1.00, 75, 1, '2026-09-09 11:30:23'),
(331, 2, 'The following are ways of infection prevention EXCEPT', NULL, '(a) Hand washing', '(b) Repeated use of needles and syringes', '(c) Disinfection/sterilization', '(d) Sanitation', NULL, 'B', 1.00, 76, 1, '2026-09-09 11:30:23'),
(332, 2, 'The right coronary artery is subdivided into', NULL, '(a) Marginal and ascending branch', '(b) Circumflex and descending branch', '(c) Marginal and descending branch', 'd) Circumflex and ascending branch', NULL, 'C', 1.00, 77, 1, '2026-09-09 11:30:23'),
(333, 2, 'Exchange of gases and food nutrients take place in', NULL, '(a) Arteries', '(b) Veins', '(c) Capillaries', '(d) Arterioles', NULL, 'C', 1.00, 78, 1, '2026-09-09 11:30:23'),
(334, 2, 'The average haemoglobin (Hb) count in an adult is', NULL, '(a) 7-ligms', '(b) 12-16mgs', '(c) 17-21gm', '(d) 22-26gms', NULL, 'B', 1.00, 79, 1, '2026-09-09 11:30:23'),
(335, 2, 'T-Lymphocytes are processed in the', NULL, '(a) Bone marrow', 'b) Thymus gland', 'c) Le Thyroid gland', '(d) Spleen', NULL, 'A', 1.00, 80, 1, '2026-09-09 11:30:23'),
(336, 2, 'Salivary amylase helps to convert', NULL, '(a) Cooked starch to glucose', '(b) Uncooked starch to glucose', '(c) Cooked starch to maltose', '(d) Uncooked starch to maltose', NULL, 'C', 1.00, 81, 1, '2026-09-09 11:30:23'),
(337, 2, 'The part of the stomach that connect with the Esophagus is known as', NULL, '(a) Fundus', '(b) Pylorus', '(c) Cardial', '(d) Body', NULL, 'B', 1.00, 82, 1, '2026-09-09 11:30:23'),
(338, 2, 'Mucin Layer in the stomach helps to', NULL, '(a) Prevent indigestion', 'b) Prevent auto digestion', '(c) Prevent excessive hydrochloric acid (Hel) production', '(d) Secrete the enzyme pepsinogen', NULL, 'C', 1.00, 83, 1, '2026-09-09 11:30:23'),
(339, 2, 'The terminal portion of the large intestine are:', NULL, '(a) Sigmoid colon-rectum-anus', '(b) Transverse colon-sigmoid colon-anus', '(c) Rectum-sigmoid colon-anus', '(d) Descending colon-rectum-anus', NULL, 'D', 1.00, 84, 1, '2026-09-09 11:30:23'),
(340, 2, 'The enzyme trypsin is secreted in the', NULL, '(a) Stomach', '(b) Duodenum', '(c) Pancreas', '(d) Liver', NULL, 'C', 1.00, 85, 1, '2026-09-09 11:30:23'),
(341, 2, 'Bilirubin is', NULL, '(a) Fat soluble', '(b) Water soluble', '(c) Water insoluble', '(d) Fat insoluble', NULL, 'A', 1.00, 86, 1, '2026-09-09 11:30:23'),
(342, 2, 'Kidneys are located between the', NULL, '(a) 10th thoracic-Ist Lumbar Vertebrae.', '(b) 11th thoracic- 2nd Lumbar Vertebrae', '(c) 12th thoracic-3rd Lumbar Vertebrae', '(d) 13th thoracic- 4th Lumbar Vertebrae', NULL, 'C', 1.00, 87, 1, '2026-09-09 11:30:23'),
(343, 2, 'Urea is a product of', NULL, '(a) Carbohydrate metabolism', 'b) Protein metabolism', '(c) Fat metabolism', '(d) Vitamin metabolism', NULL, 'B', 1.00, 88, 1, '2026-09-09 11:30:23'),
(344, 2, 'The largest gland in the body is the.', NULL, '(a) Brain', '(b) Kidney', '(c) Liver.', '(d) Pancreas', NULL, 'C', 1.00, 89, 1, '2026-09-09 11:30:23'),
(345, 2, 'The basic functional unit of the brain is called', NULL, 'a) Neuroglia', '(b) Axion dendrite', 'c) Neuron', '(d) Cell body', NULL, 'C', 1.00, 90, 1, '2026-09-09 11:30:23'),
(346, 2, 'Hormones that lower the level of calcium and phosphate in the blood is known as', NULL, '(a) Parathyroid hormone', '(b) Calcinogen', '(c) Calcitonin', '(d) Thyroxin', NULL, 'A', 1.00, 91, 1, '2026-09-09 11:30:23'),
(347, 2, 'Which of the following hormones plays a major role in circulation rhythm?', NULL, '(a) Melatonin and cortisol', '(b) Serotonin', '(c) Dopamine', '(d) Acetylcholine', NULL, 'A', 1.00, 92, 1, '2026-09-09 11:30:23'),
(348, 2, 'Which action is the priority when a patient has difficulty breathing?', NULL, 'A. Offer fluids', 'B. Assess airway and breathing', 'C. Check weight', 'D. Provide food', NULL, 'B', 1.00, 93, 1, '2026-09-09 11:30:23'),
(349, 2, 'Which position promotes lung expansion?', NULL, 'A. High Fowler\'s', 'B. Flat supine', 'C. Trendelenburg', 'D. Prone only', NULL, 'A', 1.00, 94, 1, '2026-09-09 11:30:23'),
(350, 2, '103. A patient with diabetes mellitus should be taught to:', NULL, 'A. Skip meals after insulin', 'B. Monitor blood glucose regularly', 'C. Avoid all exercise', 'D. Stop medications when feeling better', NULL, 'B', 1.00, 95, 1, '2026-09-09 11:30:23'),
(351, 2, '104. Which assessment finding indicates hypoglycemia?', NULL, 'A. Sweating and shakiness', 'B. Increased thirst only', 'C. Warm dry skin', 'D. Slow breathing', NULL, 'A', 1.00, 96, 1, '2026-09-09 11:30:23'),
(352, 2, '105. The main function of red blood cells is:', NULL, 'A. Fight infection', 'B. Carry oxygen', 'C. Form antibodies', 'D. Produce hormones', NULL, 'B', 1.00, 97, 1, '2026-09-09 11:30:23'),
(353, 2, '106. Which precaution is used for a patient with Clostridium difficile (C. diff)?', NULL, 'A. Contact precautions', 'B. Airborne precautions', 'C. Droplet precautions', 'D. No precautions', NULL, 'A', 1.00, 98, 1, '2026-09-09 11:30:23'),
(354, 2, '107. Which finding indicates fluid overload?', NULL, 'A. Edema', 'B. Dry mouth', 'C. Weight loss', 'D. Decreased blood pressure only', NULL, 'A', 1.00, 99, 1, '2026-09-09 11:30:23'),
(355, 2, '108. The nurse should assess pain:', NULL, 'A. Before and after pain medication', 'B. Only once daily', 'C. Only when patient complains', 'D. Never in unconscious patients', NULL, 'A', 1.00, 100, 1, '2026-09-09 11:30:23'),
(356, 2, '109. Which route is commonly used for insulin administration?', NULL, 'A. Subcutaneous', 'B. Oral', 'C. Intradermal', 'D. Rectal', NULL, 'A', 1.00, 101, 1, '2026-09-09 11:30:23'),
(357, 2, '110. Which organ is responsible for gas exchange?', NULL, 'A. Heart', 'B. Lungs', 'C. Liver', 'D. Kidney', NULL, 'B', 1.00, 102, 1, '2026-09-09 11:30:23'),
(358, 2, '111. A patient with a urinary catheter is at risk for:', NULL, 'A. Urinary tract infection', 'B. Hypertension', 'C. Asthma', 'D. Diabetes', NULL, 'A', 1.00, 103, 1, '2026-09-09 11:30:23'),
(359, 2, '112. The best way to prevent catheter-associated infection is:', NULL, 'A. Maintain sterile technique', 'B. Disconnect tubing frequently', 'C. Keep bag on bed', 'D. Irrigate routinely without order', NULL, 'A', 1.00, 104, 1, '2026-09-09 11:30:23'),
(360, 2, '113. Which symptom suggests internal bleeding?', NULL, 'A. Increased heart rate', 'B. Pink skin', 'C. Increased appetite', 'D. High energy level', NULL, 'A', 1.00, 105, 1, '2026-09-09 11:30:23'),
(361, 2, '114. A normal platelet count helps with:', NULL, 'A. Blood clotting', 'B. Digestion', 'C. Breathing', 'D. Vision', NULL, 'A', 1.00, 106, 1, '2026-09-09 11:30:23'),
(362, 2, '115. Which patient requires immediate attention?', NULL, 'A. Patient with SpO₂ 88%', 'B. Patient requesting a blanket', 'C. Patient watching television', 'D. Patient waiting for discharge papers', NULL, 'A', 1.00, 107, 1, '2026-09-09 11:30:23'),
(363, 2, '116. The purpose of sterile gloves is to:', NULL, 'A. Prevent contamination', 'B. Keep hands warm', 'C. Improve speed', 'D. Replace handwashing', NULL, 'A', 1.00, 108, 1, '2026-09-09 11:30:23'),
(364, 2, '117. Which electrolyte imbalance can cause cardiac arrhythmias?', NULL, 'A. Potassium imbalance', 'B. Mild chloride change', 'C. Water intake', 'D. Protein level change', NULL, 'A', 1.00, 109, 1, '2026-09-09 11:30:23'),
(365, 2, '118. The normal adult respiratory rate is:', NULL, 'A. 4–8/min', 'B. 12–20/min', 'C. 25–35/min', 'D. 40–50/min', NULL, 'B', 1.00, 110, 1, '2026-09-09 11:30:23'),
(366, 2, '119. A patient receiving oxygen should be monitored using:', NULL, 'A. Pulse oximeter', 'B. Thermometer only', 'C. Scale only', 'D. Glucose meter only', NULL, 'A', 1.00, 111, 1, '2026-09-09 11:30:23'),
(367, 2, '120. The first step after discovering a medication error is:', NULL, 'A. Assess patient safety', 'B. Hide the error', 'C. Blame another nurse', 'D. Alter documentation', NULL, 'A', 1.00, 112, 1, '2026-09-09 11:30:23'),
(368, 2, '121. Which condition requires droplet precautions?', NULL, 'A. Influenza', 'B. Tuberculosis', 'C. MRSA wound', 'D. C. difficile', NULL, 'A', 1.00, 113, 1, '2026-09-09 11:30:23'),
(369, 2, '122. The normal adult urine output is approximately:', NULL, 'A. 5 mL/hr', 'B. 30 mL/hr or more', 'C. 100 mL/day', 'D. 10 mL/day', NULL, 'B', 1.00, 114, 1, '2026-09-09 11:30:23'),
(370, 2, '123. Which position is best after feeding a patient to prevent aspiration?', NULL, 'A. Upright position', 'B. Flat position', 'C. Trendelenburg', 'D. Prone', NULL, 'A', 1.00, 115, 1, '2026-09-09 11:30:23'),
(371, 2, '124. Which action helps prevent constipation?', NULL, 'A. Adequate fluids and fiber', 'B. Bed rest only', 'C. Fluid restriction', 'D. Avoiding activity', NULL, 'A', 1.00, 116, 1, '2026-09-09 11:30:23'),
(372, 2, '125. A patient with anemia may experience:', NULL, 'A. Fatigue', 'B. Increased energy', 'C. High oxygen level', 'D. Weight gain only', NULL, 'A', 1.00, 117, 1, '2026-09-09 11:30:23'),
(373, 2, '126. The nurse should use the ABC priority system. ABC means:', NULL, 'A. Airway, Breathing, Circulation', 'B. Alert, Blood, Care', 'C. Assessment, Bed, Comfort', 'D. Air, Body, Control', NULL, 'A', 1.00, 118, 1, '2026-09-09 11:30:23'),
(374, 2, '127. Which finding indicates infection at a wound site?', NULL, 'A. Redness and warmth', 'B. Clean dry skin', 'C. No pain', 'D. Normal healing', NULL, 'A', 1.00, 119, 1, '2026-09-09 11:30:23'),
(375, 2, '128. The purpose of range-of-motion exercises is to:', NULL, 'A. Maintain joint mobility', 'B. Increase stiffness', 'C. Prevent circulation', 'D. Cause fatigue', NULL, 'A', 1.00, 120, 1, '2026-09-09 11:30:23'),
(376, 2, '129. Which medication requires checking apical pulse before administration?', NULL, 'A. Digoxin', 'B. Paracetamol', 'C. Antacid', 'D. Antibiotic cream', NULL, 'A', 1.00, 121, 1, '2026-09-09 11:30:23'),
(377, 2, '130. The normal adult pulse rate is:', NULL, 'A. 20–40 bpm', 'B. 60–100 bpm', 'C. 120–150 bpm', 'D. 160–200 bpm', NULL, 'B', 1.00, 122, 1, '2026-09-09 11:30:23'),
(378, 2, '131. Which symptom is common in asthma?', NULL, 'A. Wheezing', 'B. Constipation', 'C. Hearing loss', 'D. Skin bruising', NULL, 'A', 1.00, 123, 1, '2026-09-09 11:30:23'),
(379, 2, '132. A patient with vomiting should be monitored for:', NULL, 'A. Dehydration', 'B. Fluid overload', 'C. Hypertension', 'D. Increased urine output', NULL, 'A', 1.00, 124, 1, '2026-09-09 11:30:23'),
(380, 2, '133. Which is a sign of oxygen toxicity?', NULL, 'A. Respiratory irritation', 'B. Improved breathing', 'C. Normal oxygen level', 'D. Increased appetite', NULL, 'A', 1.00, 125, 1, '2026-09-09 11:30:23'),
(381, 2, '134. What is the purpose of suctioning?', NULL, 'A. Remove airway secretions', 'B. Increase mucus', 'C. Stop breathing', 'D. Increase infection', NULL, 'A', 1.00, 126, 1, '2026-09-09 11:30:23'),
(382, 2, '135. Which patient has the highest risk for pressure ulcers?', NULL, 'A. Immobile patient', 'B. Walking patient', 'C. Healthy adult', 'D. Active child', NULL, 'A', 1.00, 127, 1, '2026-09-09 11:30:23'),
(383, 2, '136. The nurse should reposition a bedridden patient at least every:', NULL, 'A. 2 hours', 'B. 12 hours', 'C. 24 hours', 'D. 48 hours', NULL, 'A', 1.00, 128, 1, '2026-09-09 11:30:23'),
(384, 2, '137. Which action prevents falls?', NULL, 'A. Keep call bell within reach', 'B. Leave bed high', 'C. Turn off lights', 'D. Remove footwear', NULL, 'A', 1.00, 129, 1, '2026-09-09 11:30:23'),
(385, 2, '138. The purpose of hand hygiene is to:', NULL, 'A. Reduce infection spread', 'B. Dry skin', 'C. Replace gloves', 'D. Improve circulation', NULL, 'A', 1.00, 130, 1, '2026-09-09 11:30:23'),
(386, 2, '139. Which condition requires airborne isolation?', NULL, 'A. Tuberculosis', 'B. Diarrhea', 'C. Wound infection', 'D. UTI', NULL, 'A', 1.00, 131, 1, '2026-09-09 11:30:23'),
(387, 2, '140. A patient with shock may have:', NULL, 'A. Low blood pressure', 'B. Increased appetite', 'C. Warm dry skin always', 'D. Normal circulation', NULL, 'A', 1.00, 132, 1, '2026-09-09 11:30:23'),
(388, 2, '141. The nurse should document:', NULL, 'A. Care provided and patient response', 'B. Personal opinions', 'C. Unconfirmed information', 'D. Family arguments', NULL, 'A', 1.00, 133, 1, '2026-09-09 11:30:23'),
(389, 2, '142. The best indicator of pain is:', NULL, 'A. Patient report', 'B. Facial expression only', 'C. Heart rate only', 'D. Blood pressure only', NULL, 'A', 1.00, 134, 1, '2026-09-09 11:30:23'),
(390, 2, '143. Which vitamin deficiency causes poor blood clotting?', NULL, 'A. Vitamin K', 'B. Vitamin C', 'C. Vitamin D', 'D. Vitamin A', NULL, 'A', 1.00, 135, 1, '2026-09-09 11:30:23'),
(391, 2, '144. The purpose of an incentive spirometer is to:', NULL, 'A. Prevent atelectasis', 'B. Increase fever', 'C. Reduce circulation', 'D. Cause coughing only', NULL, 'A', 1.00, 136, 1, '2026-09-09 11:30:23'),
(392, 2, '145. Which organ filters blood?', NULL, 'A. Kidney', 'B. Heart', 'C. Lung', 'D. Pancreas', NULL, 'A', 1.00, 137, 1, '2026-09-09 11:30:23'),
(393, 2, '146. The nurse should assess a patient after medication administration for:', NULL, 'A. Effect and adverse reactions', 'B. Room temperature only', 'C. Visitor number', 'D. Bed position only', NULL, 'A', 1.00, 138, 1, '2026-09-09 11:30:23'),
(394, 2, '147. A patient with a high fever is at risk for:', NULL, 'A. Dehydration', 'B. Fluid overload', 'C. Bradycardia always', 'D. Hypothermia', NULL, 'A', 1.00, 139, 1, '2026-09-09 11:30:23'),
(395, 2, '148. The abbreviation \"BID\" means:', NULL, 'A. Twice daily', 'B. Once daily', 'C. Every hour', 'D. At bedtime only', NULL, 'A', 1.00, 140, 1, '2026-09-09 11:30:23'),
(396, 2, '149. Which nutrient is important for tissue repair?', NULL, 'A. Protein', 'B. Alcohol', 'C. Caffeine', 'D. Sodium only', NULL, 'A', 1.00, 141, 1, '2026-09-09 11:30:23'),
(397, 2, '150. The final step in the nursing process is:', NULL, 'A. Evaluation', 'B. Assessment', 'C. Planning', 'D. Diagnosis', NULL, 'A', 1.00, 142, 1, '2026-09-09 11:30:23'),
(398, 2, '151. A nurse caring for a patient with a tracheostomy should always have:', NULL, 'A. Oxygen and suction equipment available', 'B. Food at bedside', 'C. Heating pad', 'D. Restraints', NULL, 'A', 1.00, 143, 1, '2026-09-09 11:30:23'),
(399, 2, '152. Which sign indicates impaired circulation?', NULL, 'A. Warm skin', 'B. Capillary refill greater than 3 seconds', 'C. Pink nail beds', 'D. Strong pulse', NULL, 'B', 1.00, 144, 1, '2026-09-09 11:30:23'),
(400, 2, '153. The normal adult fasting blood glucose level is:', NULL, 'A. 40–60 mg/dL', 'B. 70–99 mg/dL', 'C. 150–200 mg/dL', 'D. 250–300 mg/dL', NULL, 'B', 1.00, 145, 1, '2026-09-09 11:30:23'),
(401, 2, '154. Which nursing intervention helps prevent deep vein thrombosis (DVT)?', NULL, 'A. Early ambulation', 'B. Bed rest for long periods', 'C. Restrict fluids', 'D. Massage painful legs', NULL, 'A', 1.00, 146, 1, '2026-09-09 11:30:23'),
(402, 2, '155. A patient receiving anticoagulants should avoid:', NULL, 'A. Activities that increase bleeding risk', 'B. Monitoring', 'C. Fluids', 'D. Nutrition', NULL, 'A', 1.00, 147, 1, '2026-09-09 11:30:23'),
(403, 2, '156. Which finding suggests increased intracranial pressure?', NULL, 'A. Decreased level of consciousness', 'B. Increased appetite', 'C. Normal speech', 'D. Improved alertness', NULL, 'A', 1.00, 148, 1, '2026-09-09 11:30:23'),
(404, 2, '157. Which blood cell fights infection?', NULL, 'A. Red blood cell', 'B. White blood cell', 'C. Platelet', 'D. Plasma only', NULL, 'B', 1.00, 149, 1, '2026-09-09 11:30:23'),
(405, 2, '158. The safest way to move a patient from bed to chair is to:', NULL, 'A. Use proper body mechanics', 'B. Pull the patient by arms', 'C. Move quickly alone', 'D. Twist the back', NULL, 'A', 1.00, 150, 1, '2026-09-09 11:30:23'),
(406, 2, '159. A patient with pneumonia may have:', NULL, 'A. Cough and fever', 'B. Increased hearing', 'C. Hair loss', 'D. Bradycardia only', NULL, 'A', 1.00, 151, 1, '2026-09-09 11:30:23'),
(407, 2, '160. Which organ produces bile?', NULL, 'A. Liver', 'B. Kidney', 'C. Heart', 'D. Lung', NULL, 'A', 1.00, 152, 1, '2026-09-09 11:30:23'),
(408, 2, '161. The purpose of coughing exercises is to:', NULL, 'A. Clear respiratory secretions', 'B. Reduce oxygen', 'C. Increase infection', 'D. Cause fatigue', NULL, 'A', 1.00, 153, 1, '2026-09-09 11:30:23'),
(409, 2, '162. Which patient requires immediate intervention?', NULL, 'A. Patient with airway obstruction', 'B. Patient asking for a blanket', 'C. Patient waiting for discharge', 'D. Patient reading', NULL, 'A', 1.00, 154, 1, '2026-09-09 11:30:23'),
(410, 2, '163. A sterile dressing change requires:', NULL, 'A. Aseptic technique', 'B. Clean hands only', 'C. No gloves', 'D. No assessment', NULL, 'A', 1.00, 155, 1, '2026-09-09 11:30:23'),
(411, 2, '164. Normal serum sodium level is:', NULL, 'A. 50–70 mEq/L', 'B. 135–145 mEq/L', 'C. 160–180 mEq/L', 'D. 200 mEq/L', NULL, 'B', 1.00, 156, 1, '2026-09-09 11:30:23'),
(412, 2, '165. A patient with hypernatremia may experience:', NULL, 'A. Confusion', 'B. Increased relaxation', 'C. Low thirst', 'D. Improved concentration', NULL, 'A', 1.00, 157, 1, '2026-09-09 11:30:23'),
(413, 2, '166. The main purpose of an IV infusion is to:', NULL, 'A. Deliver fluids and medications', 'B. Replace oral hygiene', 'C. Improve hearing', 'D. Reduce movement', NULL, 'A', 1.00, 158, 1, '2026-09-09 11:30:23'),
(414, 2, '167. Which symptom is common with hypoxia?', NULL, 'A. Restlessness', 'B. Increased appetite', 'C. Slow speech only', 'D. Weight gain', NULL, 'A', 1.00, 159, 1, '2026-09-09 11:30:23'),
(415, 2, '168. The best position for inserting a nasogastric tube is:', NULL, 'A. High Fowler\'s', 'B. Flat supine', 'C. Prone', 'D. Trendelenburg', NULL, 'A', 1.00, 160, 1, '2026-09-09 11:30:23'),
(416, 2, '169. Which action prevents aspiration during feeding?', NULL, 'A. Keep patient upright', 'B. Lay patient flat', 'C. Feed quickly', 'D. Ignore swallowing problems', NULL, 'A', 1.00, 161, 1, '2026-09-09 11:30:23'),
(417, 2, '170. A patient with COPD often has:', NULL, 'A. Difficulty exhaling', 'B. Increased lung function', 'C. No breathing problems', 'D. High oxygen without treatment', NULL, 'A', 1.00, 162, 1, '2026-09-09 11:30:23'),
(418, 2, '171. Which assessment is included in a neurological check?', NULL, 'A. Level of consciousness', 'B. Hair condition', 'C. Nail length', 'D. Appetite only', NULL, 'A', 1.00, 163, 1, '2026-09-09 11:30:23'),
(419, 2, '172. Which medication is a bronchodilator?', NULL, 'A. Salbutamol', 'B. Insulin', 'C. Heparin', 'D. Warfarin', NULL, 'A', 1.00, 164, 1, '2026-09-09 11:30:23'),
(420, 2, '173. A patient with hypokalemia may have:', NULL, 'A. Muscle weakness', 'B. Increased reflexes only', 'C. High energy', 'D. Fever', NULL, 'A', 1.00, 165, 1, '2026-09-09 11:30:23');
INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `question_image`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_answer`, `marks`, `question_order`, `status`, `created_at`) VALUES
(421, 2, '174. The purpose of a bed bath is to:', NULL, 'A. Maintain hygiene and comfort', 'B. Replace medication', 'C. Diagnose disease', 'D. Increase infection', NULL, 'A', 1.00, 166, 1, '2026-09-09 11:30:23'),
(422, 2, '175. Which item is used for unconscious oral care?', NULL, 'A. Oral swab', 'B. Large amount of water', 'C. Hard toothbrush only', 'D. Feeding tube', NULL, 'A', 1.00, 167, 1, '2026-09-09 11:30:23'),
(423, 2, '176. A patient with severe allergic reaction may develop:', NULL, 'A. Difficulty breathing', 'B. Increased appetite', 'C. Improved circulation', 'D. Fever only', NULL, 'A', 1.00, 168, 1, '2026-09-09 11:30:23'),
(424, 2, '177. The normal adult heart rhythm is:', NULL, 'A. Regular sinus rhythm', 'B. Always irregular', 'C. No pulse', 'D. Random rhythm', NULL, 'A', 1.00, 169, 1, '2026-09-09 11:30:23'),
(425, 2, '178. Which action is part of safe medication administration?', NULL, 'A. Check medication label three times', 'B. Guess the dose', 'C. Use room number only', 'D. Skip documentation', NULL, 'A', 1.00, 170, 1, '2026-09-09 11:30:23'),
(426, 2, '179. The abbreviation \"NPO\" means:', NULL, 'A. Nothing by mouth', 'B. Normal patient order', 'C. No pain observed', 'D. New procedure only', NULL, 'A', 1.00, 171, 1, '2026-09-09 11:30:23'),
(427, 2, '180. A patient with diarrhea is at risk for:', NULL, 'A. Fluid loss', 'B. Fluid overload', 'C. Increased blood volume', 'D. Hypertension only', NULL, 'A', 1.00, 172, 1, '2026-09-09 11:30:23'),
(428, 2, '181. Which action reduces pressure injury risk?', NULL, 'A. Turning and repositioning', 'B. Keeping skin moist', 'C. Avoiding assessment', 'D. Reducing nutrition', NULL, 'A', 1.00, 173, 1, '2026-09-09 11:30:23'),
(429, 2, '182. The purpose of gloves is to:', NULL, 'A. Reduce exposure to microorganisms', 'B. Replace hand hygiene', 'C. Protect only the patient', 'D. Improve speed', NULL, 'A', 1.00, 174, 1, '2026-09-09 11:30:23'),
(430, 2, '183. A patient with anemia has decreased:', NULL, 'A. Oxygen-carrying capacity', 'B. Blood pressure only', 'C. Body temperature', 'D. Digestion', NULL, 'A', 1.00, 175, 1, '2026-09-09 11:30:23'),
(431, 2, '184. Which symptom indicates possible stroke?', NULL, 'A. Sudden weakness on one side', 'B. Increased appetite', 'C. Fever only', 'D. Slow nail growth', NULL, 'A', 1.00, 176, 1, '2026-09-09 11:30:23'),
(432, 2, '185. Which emergency medication reverses opioid overdose?', NULL, 'A. Naloxone', 'B. Insulin', 'C. Digoxin', 'D. Epinephrine', NULL, 'A', 1.00, 177, 1, '2026-09-09 11:30:23'),
(433, 2, '186. A patient with burns requires monitoring for:', NULL, 'A. Fluid loss', 'B. Increased blood volume', 'C. Decreased pain always', 'D. Infection only after healing', NULL, 'A', 1.00, 178, 1, '2026-09-09 11:30:23'),
(434, 2, '187. The purpose of handwashing before patient care is:', NULL, 'A. Prevent infection transmission', 'B. Improve circulation', 'C. Reduce fatigue', 'D. Replace PPE', NULL, 'A', 1.00, 179, 1, '2026-09-09 11:30:23'),
(435, 2, '188. A patient with low hemoglobin may need assessment for:', NULL, 'A. Fatigue and weakness', 'B. Increased energy', 'C. Hypertension', 'D. Fever only', NULL, 'A', 1.00, 180, 1, '2026-09-09 11:30:23'),
(436, 2, '189. Which finding requires reporting in a postoperative patient?', NULL, 'A. Excessive bleeding', 'B. Mild discomfort', 'C. Hunger', 'D. Sleepiness', NULL, 'A', 1.00, 181, 1, '2026-09-09 11:30:23'),
(437, 2, '190. Which organ controls blood sugar regulation?', NULL, 'A. Pancreas', 'B. Heart', 'C. Lung', 'D. Skin', NULL, 'A', 1.00, 182, 1, '2026-09-09 11:30:23'),
(438, 2, '191. The best method to prevent falls is:', NULL, 'A. Safety precautions and supervision', 'B. Removing call bell', 'C. Keeping bed high', 'D. Poor lighting', NULL, 'A', 1.00, 183, 1, '2026-09-09 11:30:23'),
(439, 2, '192. A patient receiving oxygen should avoid:', NULL, 'A. Open flames', 'B. Drinking fluids', 'C. Talking', 'D. Sleeping', NULL, 'A', 1.00, 184, 1, '2026-09-09 11:30:23'),
(440, 2, '193. Which electrolyte is important for nerve and muscle function?', NULL, 'A. Potassium', 'B. Chloride only', 'C. Iron', 'D. Glucose', NULL, 'A', 1.00, 185, 1, '2026-09-09 11:30:23'),
(441, 2, '194. A patient with asthma should avoid:', NULL, 'A. Known triggers', 'B. Prescribed medications', 'C. Monitoring', 'D. Follow-up care', NULL, 'A', 1.00, 186, 1, '2026-09-09 11:30:23'),
(442, 2, '195. Which assessment indicates dehydration?', NULL, 'A. Dry mucous membranes', 'B. Moist skin', 'C. Edema', 'D. Weight gain', NULL, 'A', 1.00, 187, 1, '2026-09-09 11:30:23'),
(443, 2, '196. Which nursing action improves comfort?', NULL, 'A. Positioning properly', 'B. Ignoring pain', 'C. Reducing communication', 'D. Avoiding hygiene', NULL, 'A', 1.00, 188, 1, '2026-09-09 11:30:23'),
(444, 2, '197. The purpose of suctioning is to:', NULL, 'A. Maintain airway clearance', 'B. Increase secretions', 'C. Reduce oxygen', 'D. Cause coughing only', NULL, 'A', 1.00, 189, 1, '2026-09-09 11:30:23'),
(445, 2, '198. A patient with chest pain should be:', NULL, 'A. Assessed immediately', 'B. Ignored', 'C. Given food first', 'D. Allowed to walk', NULL, 'A', 1.00, 190, 1, '2026-09-09 11:30:23'),
(446, 2, '199. Which sign indicates improved breathing?', NULL, 'A. Reduced respiratory distress', 'B. Increased cyanosis', 'C. Confusion', 'D. Lower oxygen saturation', NULL, 'A', 1.00, 191, 1, '2026-09-09 11:30:23'),
(447, 2, '200. The nurse\'s role includes:', NULL, 'A. Providing safe patient care', 'B. Ignoring patient concerns', 'C. Avoiding assessment', 'D. Giving medication without order', NULL, 'A', 1.00, 192, 1, '2026-09-09 11:30:23'),
(448, 2, '201. The priority assessment for a newly admitted patient is:', NULL, 'A. Airway and breathing', 'B. Favorite food', 'C. Sleep pattern', 'D. Hair condition', NULL, 'A', 1.00, 193, 1, '2026-09-09 11:30:23'),
(449, 2, '202. Which finding indicates hypoxia?', NULL, 'A. Restlessness and confusion', 'B. Pink lips', 'C. Normal breathing', 'D. Warm skin only', NULL, 'A', 1.00, 194, 1, '2026-09-09 11:30:23'),
(450, 2, '203. A patient with diabetes should inspect their feet daily to prevent:', NULL, 'A. Foot ulcers', 'B. Hearing loss', 'C. Hypertension', 'D. Fever', NULL, 'A', 1.00, 195, 1, '2026-09-09 11:30:23'),
(451, 2, '204. The normal adult body temperature is approximately:', NULL, 'A. 34°C', 'B. 37°C', 'C. 40°C', 'D. 42°C', NULL, 'B', 1.00, 196, 1, '2026-09-09 11:30:23'),
(452, 2, '205. Which position is used to improve breathing?', NULL, 'A. High Fowler\'s', 'B. Flat supine', 'C. Trendelenburg', 'D. Prone only', NULL, 'A', 1.00, 197, 1, '2026-09-09 11:30:23'),
(453, 2, '206. A patient receiving a blood transfusion should be monitored for:', NULL, 'A. Transfusion reaction', 'B. Improved appetite only', 'C. Weight gain only', 'D. Hair loss', NULL, 'A', 1.00, 198, 1, '2026-09-09 11:30:23'),
(454, 2, '207. Which sign suggests an allergic reaction?', NULL, 'A. Difficulty breathing and rash', 'B. Increased appetite', 'C. Normal skin', 'D. Improved breathing', NULL, 'A', 1.00, 199, 1, '2026-09-09 11:30:23'),
(455, 2, '208. The purpose of isolation precautions is to:', NULL, 'A. Prevent infection transmission', 'B. Increase patient separation', 'C. Reduce nursing care', 'D. Stop communication', NULL, 'A', 1.00, 200, 1, '2026-09-09 11:30:23'),
(456, 2, '209. Which patient should be seen first?', NULL, 'A. Patient with severe shortness of breath', 'B. Patient requesting water', 'C. Patient waiting for discharge', 'D. Patient asking for a blanket', NULL, 'A', 1.00, 201, 1, '2026-09-09 11:30:23'),
(457, 2, '210. The normal adult respiratory rate is:', NULL, 'A. 5–10/min', 'B. 12–20/min', 'C. 25–40/min', 'D. 50/min', NULL, 'B', 1.00, 202, 1, '2026-09-09 11:30:23'),
(458, 2, '211. Which nutrient promotes wound healing?', NULL, 'A. Protein', 'B. Alcohol', 'C. Caffeine', 'D. Sodium only', NULL, 'A', 1.00, 203, 1, '2026-09-09 11:30:23'),
(459, 2, '212. A patient with a urinary catheter should have:', NULL, 'A. Closed drainage system maintained', 'B. Bag placed on bed', 'C. Tubing disconnected often', 'D. No hygiene care', NULL, 'A', 1.00, 204, 1, '2026-09-09 11:30:23'),
(460, 2, '213. The normal oxygen saturation level is:', NULL, 'A. 50–60%', 'B. 70–80%', 'C. 95–100%', 'D. 40–50%', NULL, 'C', 1.00, 205, 1, '2026-09-09 11:30:23'),
(461, 2, '214. Which sign indicates infection?', NULL, 'A. Elevated white blood cell count', 'B. Low temperature only', 'C. Normal healing', 'D. Clear skin', NULL, 'A', 1.00, 206, 1, '2026-09-09 11:30:23'),
(462, 2, '215. A patient with asthma may need:', NULL, 'A. Bronchodilator therapy', 'B. Insulin', 'C. Anticoagulant only', 'D. Diuretics only', NULL, 'A', 1.00, 207, 1, '2026-09-09 11:30:23'),
(463, 2, '216. Which action prevents aspiration during tube feeding?', NULL, 'A. Elevate the head of bed', 'B. Lay patient flat', 'C. Increase feeding speed', 'D. Stop monitoring', NULL, 'A', 1.00, 208, 1, '2026-09-09 11:30:23'),
(464, 2, '217. The best method to reduce medication errors is:', NULL, 'A. Follow medication rights', 'B. Skip identification', 'C. Guess the dose', 'D. Use room number', NULL, 'A', 1.00, 209, 1, '2026-09-09 11:30:23'),
(465, 2, '218. Which medication lowers blood glucose?', NULL, 'A. Insulin', 'B. Heparin', 'C. Morphine', 'D. Antibiotic', NULL, 'A', 1.00, 210, 1, '2026-09-09 11:30:23'),
(466, 2, '219. A patient with shock may have:', NULL, 'A. Low blood pressure', 'B. Increased urine output', 'C. Warm dry skin always', 'D. Normal circulation', NULL, 'A', 1.00, 211, 1, '2026-09-09 11:30:23'),
(467, 2, '220. Which action maintains patient dignity?', NULL, 'A. Providing privacy', 'B. Exposing the patient unnecessarily', 'C. Ignoring preferences', 'D. Discussing private information publicly', NULL, 'A', 1.00, 212, 1, '2026-09-09 11:30:23'),
(468, 2, '221. The purpose of suctioning a tracheostomy is to:', NULL, 'A. Remove secretions', 'B. Increase mucus', 'C. Stop breathing', 'D. Reduce oxygen', NULL, 'A', 1.00, 213, 1, '2026-09-09 11:30:23'),
(469, 2, '222. Which symptom is common in dehydration?', NULL, 'A. Thirst', 'B. Edema', 'C. Weight gain', 'D. Increased urine output', NULL, 'A', 1.00, 214, 1, '2026-09-09 11:30:23'),
(470, 2, '223. The nurse should document care:', NULL, 'A. Immediately after providing care', 'B. At the end of the month', 'C. Only when asked', 'D. Never', NULL, 'A', 1.00, 215, 1, '2026-09-09 11:30:23'),
(471, 2, '224. Which laboratory value is important before giving anticoagulants?', NULL, 'A. Coagulation studies', 'B. Blood type only', 'C. Height', 'D. Temperature', NULL, 'A', 1.00, 216, 1, '2026-09-09 11:30:23'),
(472, 2, '225. A patient with COPD should avoid:', NULL, 'A. Smoking', 'B. Prescribed inhalers', 'C. Follow-up care', 'D. Breathing exercises', NULL, 'A', 1.00, 217, 1, '2026-09-09 11:30:23'),
(473, 2, '226. Which action prevents pressure injuries?', NULL, 'A. Repositioning frequently', 'B. Keeping skin wet', 'C. Avoiding movement', 'D. Reducing nutrition', NULL, 'A', 1.00, 218, 1, '2026-09-09 11:30:23'),
(474, 2, '227. The normal adult pulse rate is:', NULL, 'A. 30–50 bpm', 'B. 60–100 bpm', 'C. 120–160 bpm', 'D. 180 bpm', NULL, 'B', 1.00, 219, 1, '2026-09-09 11:30:23'),
(475, 2, '228. Which organ removes waste from blood?', NULL, 'A. Kidney', 'B. Lung', 'C. Heart', 'D. Stomach', NULL, 'A', 1.00, 220, 1, '2026-09-09 11:30:23'),
(476, 2, '229. A patient with fever should be assessed for:', NULL, 'A. Infection', 'B. Hair loss', 'C. Vision changes only', 'D. Weight gain', NULL, 'A', 1.00, 221, 1, '2026-09-09 11:30:23'),
(477, 2, '230. The purpose of oxygen therapy is to:', NULL, 'A. Improve oxygen delivery to tissues', 'B. Reduce circulation', 'C. Increase infection', 'D. Stop breathing', NULL, 'A', 1.00, 222, 1, '2026-09-09 11:30:23'),
(478, 2, '231. Which finding indicates improved circulation?', NULL, 'A. Warm extremities and good pulse', 'B. Cold skin', 'C. Delayed capillary refill', 'D. Weak pulse', NULL, 'A', 1.00, 223, 1, '2026-09-09 11:30:23'),
(479, 2, '232. A patient with low potassium may develop:', NULL, 'A. Muscle weakness', 'B. Increased strength', 'C. Fever', 'D. Improved reflexes', NULL, 'A', 1.00, 224, 1, '2026-09-09 11:30:23'),
(480, 2, '233. Which patient needs droplet precautions?', NULL, 'A. Influenza patient', 'B. Tuberculosis patient', 'C. MRSA patient', 'D. C. difficile patient', NULL, 'A', 1.00, 225, 1, '2026-09-09 11:30:23'),
(481, 2, '234. The first step before any nursing procedure is:', NULL, 'A. Hand hygiene', 'B. Documentation', 'C. Medication', 'D. Feeding', NULL, 'A', 1.00, 226, 1, '2026-09-09 11:30:23'),
(482, 2, '235. A patient receiving morphine should be monitored for:', NULL, 'A. Respiratory depression', 'B. Increased breathing rate', 'C. Hypertension only', 'D. Fever', NULL, 'A', 1.00, 227, 1, '2026-09-09 11:30:23'),
(483, 2, '236. Which sign indicates poor oxygenation?', NULL, 'A. Cyanosis', 'B. Pink skin', 'C. Normal breathing', 'D. Normal SpO₂', NULL, 'A', 1.00, 228, 1, '2026-09-09 11:30:23'),
(484, 2, '237. Which vitamin is needed for calcium absorption?', NULL, 'A. Vitamin D', 'B. Vitamin C', 'C. Vitamin K', 'D. Vitamin B', NULL, 'A', 1.00, 229, 1, '2026-09-09 11:30:23'),
(485, 2, '238. The safest way to identify a patient is:', NULL, 'A. Use two identifiers', 'B. Ask room number', 'C. Check diagnosis only', 'D. Ask another patient', NULL, 'A', 1.00, 230, 1, '2026-09-09 11:30:23'),
(486, 2, '239. A patient with severe bleeding is at risk for:', NULL, 'A. Shock', 'B. Hypertension', 'C. Fluid overload', 'D. Increased oxygen', NULL, 'A', 1.00, 231, 1, '2026-09-09 11:30:23'),
(487, 2, '240. The sound produced by the movement of gas and fluid mixture in the intestine is called', NULL, 'Borborygmus', 'Boborygmus', 'Borborigmus', 'Bororygmus', NULL, 'A', 1.00, 232, 1, '2026-09-09 11:30:23'),
(488, 2, '241. Homeostatic control mechanism have three interdependent components which includes the following EXCEPT', NULL, 'The receptor', 'The conductor', 'The control centre', 'The effector', NULL, 'D', 1.00, 233, 1, '2026-09-09 11:30:23'),
(489, 2, '242. The dominant subtype of HIV-1 is', NULL, 'M', 'N', 'O', 'P', NULL, 'A', 1.00, 234, 1, '2026-09-09 11:30:23'),
(490, 2, '243. Social health can be defined as', NULL, 'A mind that grows, reasons adjust to life\'s situation', 'A sense of responsibility for the health and welfare of others', 'Feeling and action that brings ones satisfaction', 'Inner peace and security in one\'s spiritual health', NULL, 'B', 1.00, 235, 1, '2026-09-09 11:30:23'),
(491, 2, '244. Extra pulmonary tuberculosis affecting bones and joints is called', NULL, 'Tuberculous meningis', 'Scrofula', 'Tuberculous pleurisy', 'Pott\'s disease', NULL, 'D', 1.00, 236, 1, '2026-09-09 11:30:23'),
(492, 2, '245. A potentially more serious widespread form of tuberculosis is called disseminated tuberculosis or', NULL, 'Pott\'s disease', 'TB spine', 'Milliary tuberculosis', 'Pulmonary tuberculosis', NULL, 'C', 1.00, 237, 1, '2026-09-09 11:30:23'),
(493, 2, '246. Notable extra pulmonary infection sites includes the following EXCEPT', NULL, 'Pleura', 'Lungs', 'Central nervous system', 'Bones and joints', NULL, 'B', 1.00, 238, 1, '2026-09-09 11:30:23'),
(494, 2, '247. Window period of HIV is also called', NULL, 'Seroconversion stage', 'Prodromal stage', 'Latent stage', 'Resistant stage', NULL, 'A', 1.00, 239, 1, '2026-09-09 11:30:23'),
(495, 2, '248. Regulation of blood pressure as a homeostatic process is an example of', NULL, 'Positive feedback mechanism', 'Negative feedback mechanism', 'Both positive and negative feedback mechanism', 'None of the above', NULL, 'A', 1.00, 240, 1, '2026-09-09 11:30:23'),
(496, 2, '249. The following represent homeostasis situations EXCEPT', NULL, 'Sweating and panting at high temperatures', 'Blood Ph regulation at 7.365', 'Regulation of blood glucose concentration', 'Salivating', NULL, 'D', 1.00, 241, 1, '2026-09-09 11:30:23'),
(497, 2, '250. Ghons and Simon focus is mostly associated with', NULL, 'Pneumonia', 'Lung cancer', 'Bronchiectasis', 'Tuberculosis', NULL, 'D', 1.00, 242, 1, '2026-09-09 11:30:23'),
(498, 2, 'Intrinsic bronchial asthma is also called', NULL, 'Child-onset asthma', 'Adult-onset asthma', 'All-ages asthma', 'Gerontology asthma', NULL, 'B', 1.00, 243, 1, '2026-09-09 11:30:23'),
(499, 2, 'Extrinsic bronchial asthma is ………. In nature', NULL, 'Allergic', 'Non-allergic', 'Both allergic and non-allergic', 'None of the option', NULL, 'A', 1.00, 244, 1, '2026-09-09 11:30:23'),
(500, 2, 'Combine treatment/management for small cell lung carcinoma (SCLC) involves', NULL, 'Surgery and chemotherapy', 'Surgery and radiography', 'Chemotherapy and radiography', 'None of the above', NULL, 'C', 1.00, 245, 1, '2026-09-09 11:30:23'),
(501, 2, 'Which of the following is not a high-priority nursing diagnosis', NULL, 'Impaired gas exchange', 'Ineffective airway clearance', 'Acute pain', 'Pain after minor surgery', NULL, 'D', 1.00, 246, 1, '2026-09-09 11:30:23'),
(502, 2, 'Nursing diagnoses mostly differ from medical diagnoses in that they are', NULL, 'Dependent upon medical diagnoses for the direction of appropriate intervention', 'Primarily concern with human response while medical diagnoses are primarily concern with pathology', 'Primarily concern with caring while medical diagnoses primarily concern with curing', 'Primarily concern with psychosocial parameters while medical diagnoses are primarily concern with physiological parameters', NULL, 'B', 1.00, 247, 1, '2026-09-09 11:30:23'),
(503, 2, 'Which physiological response is often associated with surgery-related stress', NULL, 'Bronchial constriction', 'Peripheral vasoconstriction', 'Decrease cortisol levels', 'Sodium and water retention', NULL, 'D', 1.00, 248, 1, '2026-09-09 11:30:23'),
(504, 2, 'What factors will you consider before advising a patient for amputation of a limb?', NULL, 'The part affected weight and age', 'Age of the patients, severity of injury and time', 'Source heights and weights', 'Sex, height, weight of patient', NULL, 'B', 1.00, 249, 1, '2026-09-09 11:30:23'),
(505, 2, 'The posterior 5/6th of the outer coat of the eye ball is called …', NULL, 'Sclera', 'Retina', 'Conica', 'Iris', NULL, 'A', 1.00, 250, 1, '2026-09-09 11:30:23'),
(506, 2, 'Which of the following bile component is responsible for effective digestion of fat in the small intestine?', NULL, 'Bile salt', 'Cholesterol', 'Lecithin', 'Bilirubin', NULL, 'A', 1.00, 251, 1, '2026-09-09 11:30:23'),
(507, 2, 'A child with wasted muscle hair change and oedema may be suffering from …', NULL, 'marasmus', 'kwashiorkor', 'pellagra', 'vitamin A deficiency', NULL, 'B', 1.00, 252, 1, '2026-09-09 11:30:23'),
(508, 2, 'The food item which contains the lowest potassium is …', NULL, 'spinach', 'apple', 'carrot', 'avocado', NULL, 'B', 1.00, 253, 1, '2026-09-09 11:30:23');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
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

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `exam_id`, `student_id`, `attempt_id`, `total_questions`, `answered`, `correct`, `wrong`, `total_questions_score`, `score`, `total_marks`, `percentage`, `grade`, `remark`, `published`, `published_at`, `published_by`) VALUES
(1, 2, 3, 8, 20, 1, 0, 1, 0.00, 0.00, 100.00, 0.00, 'F', 'Fail', 0, NULL, NULL),
(2, 2, 17, 2, 20, 20, 13, 7, 13.00, 65.00, 100.00, 65.00, 'B', 'Very Good', 0, NULL, NULL),
(3, 2, 5, 4, 20, 1, 0, 1, 0.00, 0.00, 100.00, 0.00, 'F', 'Fail', 0, NULL, NULL),
(4, 2, 23, 21, 20, 0, 0, 0, 0.00, 0.00, 100.00, 0.00, 'F', 'Fail', 0, NULL, NULL),
(5, 1, 23, 18, 200, 5, 4, 1, 4.00, 80.00, 100.00, 80.00, 'A', 'Excellent', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) UNSIGNED NOT NULL,
  `semester_name` varchar(20) NOT NULL,
  `semester_order` int(1) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `semester_name`, `semester_order`, `status`) VALUES
(1, 'First Semester', 1, 1),
(2, 'Second Semester', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) UNSIGNED NOT NULL,
  `session_name` varchar(20) NOT NULL,
  `session_start` date DEFAULT NULL,
  `session_end` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `session_name`, `session_start`, `session_end`, `is_current`, `status`, `created_at`) VALUES
(1, '2024/2025', '2024-09-01', '2025-08-31', 1, 1, '2026-09-06 17:28:47'),
(2, '2025/2026', '2025-09-01', '2026-08-31', 0, 1, '2026-09-06 17:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES
(1, 'institution_name', 'TARABA STATE UNIVERSITY', 'general'),
(2, 'timezone', 'Africa/Lagos', 'general'),
(3, 'default_duration', '60', 'general'),
(4, 'default_pass_mark', '40', 'general'),
(5, 'default_question_limit', '50', 'general'),
(6, 'session_timeout', '30', 'general'),
(7, 'biometric_threshold', '90', 'general'),
(8, 'maintenance_mode', '0', 'general'),
(9, 'biometric_enabled', '0', 'general');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
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

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `matric_number`, `password`, `first_name`, `last_name`, `other_name`, `email`, `phone`, `gender`, `date_of_birth`, `department_id`, `level_id`, `semester_id`, `session_id`, `photo`, `fingerprint_enrolled`, `biometric_verified`, `login_status`, `last_login`, `login_ip`, `current_exam_id`, `status`, `created_at`, `updated_at`, `last_promotion_date`, `promotion_status`) VALUES
(1, 'TSU/FAH/NS/22/1001', '$2y$12$1NAGwnQSscNlcl0FLsyhzuiFMYjXzrlEmcjJWVMRbuiD3PbZfsgLO', 'Odeh', 'Jennifer', 'Ochanya', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1001_1788953567.jpg', 1, 1, 0, '2026-09-10 19:01:18', '10.34.30.1', NULL, 1, '2026-09-09 11:32:47', '2026-09-11 04:21:47', NULL, 'active'),
(2, 'TSU/FAH/NS/22/1002', '$2y$12$PVBcf2.XxkBTEoHnqJ2kmeQTQkrHX6dJBt.1T9J2GuCtCZI8u/RZu', 'James', 'Ejeh', 'Anthony', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1002_1788953586.jpg', 1, 1, 1, '2026-09-09 12:47:52', '10.34.30.77', NULL, 1, '2026-09-09 11:33:07', '2026-09-09 13:11:56', NULL, 'active'),
(3, 'TSU/FAH/NS/22/1003', '$2y$12$EoWp2.6cXv/hg28pchZeFOYqeDnc5V1afFJSlMNAaSlsEOFLZtYdC', 'AGIDA', 'AGBENU', 'RUTH', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1003_1788953600.jpg', 1, 1, 0, '2026-09-18 16:14:15', '::1', NULL, 1, '2026-09-09 11:33:20', '2026-09-18 15:35:31', NULL, 'active'),
(4, 'TSU/FAH/NS/22/1004', '$2y$12$TYPYGxpICFxP11JPprjaJeFz.o6cfA0LGPFg8MflzGQ.XtEKwelxm', 'KONLA', 'SINDI', 'RANGBII', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 1, '2026-09-09 12:46:17', '10.34.30.22', NULL, 1, '2026-09-09 11:33:41', '2026-09-09 12:58:28', NULL, 'active'),
(5, 'TSU/FAH/NS/22/1005', '$2y$12$JNHnw3Jf05ebIBu1Mkv./OrX2OuSiG9Ndqy8pIFxxRnumw19UFXP2', 'Ibrahim', 'Hope', '', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 0, '2026-09-18 15:02:23', '::1', NULL, 1, '2026-09-09 11:33:54', '2026-09-18 14:44:30', NULL, 'active'),
(6, 'TSU/FAH/NS/22/1006', '$2y$12$VWq4a9VKC1diuKK4.mKuLO0sSwAXgTv0ESdRdIJbfojToZRB2hNra', 'James', 'Voviyere', 'Tony', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 1, '2026-09-09 12:51:27', '10.34.30.98', NULL, 1, '2026-09-09 11:34:09', '2026-09-09 13:00:55', NULL, 'active'),
(7, 'TSU/FAH/NS/22/1007', '$2y$12$.tk1ohFDyF6/2hK555Oo8.iUblw.WZfnFzUpidanXuDO9ldIAWcRS', 'Christopher', 'Owoicho', 'Michael', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1007_1788953660.jpg', 1, 1, 1, '2026-09-09 12:46:19', '10.34.30.15', NULL, 1, '2026-09-09 11:34:20', '2026-09-09 13:13:53', NULL, 'active'),
(8, 'TSU/FAH/NS/22/1008', '$2y$12$gl4d/9X74Ds6z1LzcRy7ou4yostONoC/f/yGrD0HkOhrrZtggYaR.', 'Denis', 'Tafon', 'Precious', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1008_1788953672.jpg', 1, 1, 1, '2026-09-09 12:59:19', '10.34.30.122', NULL, 1, '2026-09-09 11:34:32', '2026-09-09 13:14:38', NULL, 'active'),
(9, 'TSU/FAH/NS/22/1009', '$2y$12$c17jaOh.Zleoi72hIqugP.8rmKLmVpnhO2QHS2TZ/Epmh8MPE4Mhy', 'Fidelis', 'Goodness', 'Ifeoma', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1009_1788953685.jpg', 1, 1, 1, '2026-09-09 12:52:17', '10.34.30.91', NULL, 1, '2026-09-09 11:34:45', '2026-09-09 13:12:21', NULL, 'active'),
(10, 'TSU/FAH/NS/22/1010', '$2y$12$F3fCEF66Utk.s.vEmWgQf.5WWNYEOkQOl00uRBHi/OXJSxaw9q3pG', 'Bala', 'Boniface', 'Laayanjong', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 0, '2026-09-18 15:44:57', '::1', NULL, 1, '2026-09-09 11:34:57', '2026-09-18 15:04:20', NULL, 'active'),
(11, 'TSU/FAH/NS/22/1011', '$2y$12$KiODf7LC9s9z9QHxtqY45eV0RvOQMteaA3J11Y5wdDWs.LGCcRGhe', 'Augustine', 'Austine', '', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1011_1788953709.jpg', 1, 1, 1, '2026-09-09 12:46:32', '10.34.30.54', NULL, 1, '2026-09-09 11:35:09', '2026-09-09 13:15:12', NULL, 'active'),
(12, 'TSU/FAH/NS/22/1012', '$2y$12$ciW2P7MbmvHB/dHrv28tmuWQO23ngXXfhdDg1p2merC/eOABqrUOO', 'Zira', 'Paul', 'Mamiya', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1012_1788953722.jpg', 1, 1, 0, '2026-09-11 07:42:26', '10.34.30.1', NULL, 1, '2026-09-09 11:35:22', '2026-09-11 09:47:43', NULL, 'active'),
(13, 'TSU/FAH/NS/22/1013', '$2y$12$Fj38l/NFAVm8ZOgT.aaLauwMDou4Z2QLS6nxK83iwH/glawD.AWyS', 'Fred', 'Nyanganji', 'Light', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_22_1013_1788953731.jpg', 1, 1, 0, '2026-09-12 05:48:54', '::1', NULL, 1, '2026-09-09 11:35:31', '2026-09-12 04:49:58', NULL, 'active'),
(14, 'TSU/FAH/NS/22/1014', '$2y$12$1RBgVujUompljnppDX6qfOp9LhRfc17h7RJoYvkW7wcHHx9kBnmu.', 'Tersugh', 'Ternongo', '', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 0, '2026-09-12 06:15:16', '::1', NULL, 1, '2026-09-09 11:35:42', '2026-09-12 05:19:55', NULL, 'active'),
(15, 'TSU/FAH/NS/22/1016', '$2y$12$9eXOj4R.Q5TiVa/XvNr4ruKDuOqyHzUIesqI709870cRsb0Ias75W', 'Lawan', 'Iro', 'Hudallah', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 1, '2026-09-09 12:46:46', '10.34.30.255', NULL, 1, '2026-09-09 11:35:57', '2026-09-09 13:15:26', NULL, 'active'),
(16, 'TSU/FAH/NS/22/1017', '$2y$12$w9nDYui21yl6FInwaLOr/eTdEZSdF6jk4ajbmEVMTuvTAgSADLTf.', 'Sani', 'Babanlare', 'Najaatu', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 1, '2026-09-09 12:46:50', '10.34.30.36', NULL, 1, '2026-09-09 11:36:12', '2026-09-09 13:02:02', NULL, 'active'),
(17, 'TSU/FAH/NS/22/2001', '$2y$12$qvs9pWFQ4Ze3DKSadESU0OXi7wP.E7Ab3glvTZfbgiELFG4B/Gk7y', 'APURU', 'JONATHAN', 'COMFORT', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 0, '2026-09-12 06:25:04', '::1', NULL, 1, '2026-09-09 11:36:26', '2026-09-12 05:30:11', NULL, 'active'),
(18, 'TSU/FAH/NS/22/2002', '$2y$12$l.6eyWkWKdO2tdf.a5eu8uO3HMhr2dvLm.qBnzI2ZKFQVZOmRFuze', 'LEBUIN', 'VICTORINE', '', '', NULL, NULL, NULL, 6, 5, 1, 1, 'cms_TSU_FAH_NS_22_2002_1788953799.jpg', 1, 1, 0, '2026-09-11 07:41:08', '10.34.30.1', NULL, 1, '2026-09-09 11:36:39', '2026-09-11 06:41:45', NULL, 'active'),
(19, 'TSU/FAH/NS/22/2003', '$2y$12$PkyFvkMuY0Lll6BITPZP8OoiMb27vb/2XzorvrTViShZNmjXX4z7i', 'UKOSON', 'ALICE', 'OMARI', '', NULL, NULL, NULL, 6, 5, 1, 1, 'cms_TSU_FAH_NS_22_2003_1788953810.jpg', 1, 1, 0, '2026-09-18 15:01:52', '::1', NULL, 1, '2026-09-09 11:36:50', '2026-09-18 14:01:58', NULL, 'active'),
(20, 'TSU/FAH/NS/22/2004', '$2y$12$ap26te7WJ9ShqYytkgzbNuvo6zXigwe0IOJ6ZXEpB0NyL/3bhSJ2i', 'EPHRAIM', 'SOLOMON', '', '', NULL, NULL, NULL, 6, 5, 1, 1, 'cms_TSU_FAH_NS_22_2004_1788953821.jpg', 1, 1, 0, NULL, NULL, NULL, 1, '2026-09-09 11:37:02', '2026-09-09 11:40:58', NULL, 'active'),
(21, 'TSU/FAH/NS/22/2005', '$2y$12$YYFqb4VVqcZkIBVE23AYA.geWSiJsA1esBk5xfsiL14iDzUa5qPda', 'BALA', 'NGWEZU', 'MERICA', '', NULL, NULL, NULL, 6, 5, 1, 1, 'cms_TSU_FAH_NS_22_2005_1788953833.jpg', 1, 1, 1, '2026-09-09 12:48:41', '10.34.30.70', NULL, 1, '2026-09-09 11:37:13', '2026-09-09 13:19:39', NULL, 'active'),
(22, 'TSU/FAH/NS/22/2006', '$2y$12$mTu1BIuh66UgfrKWoHMmMusF9tfL7TNpAg/Vv6PFBbILNiJkYzv9S', 'NAPHTALI', 'JUANBOH', 'JULIUS', '', NULL, NULL, NULL, 6, 5, 1, 1, NULL, 1, 1, 0, '2026-09-18 16:04:35', '::1', NULL, 1, '2026-09-09 11:37:26', '2026-09-18 15:04:51', NULL, 'active'),
(23, 'TSU/FAH/NS/23/2003', '$2y$12$BRI8VelFvjMo3U4piQuPj.nTei.1nK2n1KfgYhmBE5lZ7vGK976H.', 'AUDU', 'FAITH', '', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 1, '2026-09-18 17:25:18', '::1', 1, 1, '2026-09-09 11:37:49', '2026-09-18 20:10:40', NULL, 'active'),
(24, 'TSU/FAH/NS/23/2004', '$2y$12$daVfY8ts41CSv6VMuLiB6Oer5SqiJg.r4x3nBs.g1pJBpz.YPwUGe', 'GENESIS', 'BERRINYUY', 'PEACE', '', NULL, NULL, NULL, 6, 4, 1, 1, NULL, 1, 1, 0, '2026-09-12 05:52:55', '::1', NULL, 1, '2026-09-09 11:38:04', '2026-09-12 04:53:41', NULL, 'active'),
(25, 'TSU/FAH/NS/23/2005', '$2y$12$o5kYtiSYScJLuwShAafVyerQpvWcj4sEMKwM/ApLjNwbRYBIlwj0q', 'GENESIS', 'MALLAH', 'MERCY', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_23_2005_1788953896.jpg', 1, 1, 0, '2026-09-18 16:04:54', '::1', NULL, 1, '2026-09-09 11:38:16', '2026-09-18 15:11:15', NULL, 'active'),
(26, 'TSU/FAH/NS/23/2006', '$2y$12$DQUhFOeNEn.BaZ7cDsrfAOQB5RsEpYJF7Qzu62pkakbFEPPn2FnV2', 'ONAH', 'PEACE', 'ADAORA', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_23_2006_1788953909.jpg', 1, 1, 0, '2026-09-12 06:14:28', '::1', NULL, 1, '2026-09-09 11:38:30', '2026-09-12 05:15:13', NULL, 'active'),
(27, 'TSU/FAH/NS/23/3001', '$2y$12$iZUzkjnltSpOjuX1msBxVuaFh5d86TdcWL5rjvaRlnXzDzvxLatkG', 'STEPHEN', 'HARUNA', 'JOANNA', '', NULL, NULL, NULL, 6, 4, 1, 1, 'cms_TSU_FAH_NS_23_3001_1788953925.jpg', 1, 1, 0, '2026-09-12 05:50:53', '::1', NULL, 1, '2026-09-09 11:38:45', '2026-09-12 04:51:04', NULL, 'active'),
(28, 'TSU/FAH/NS/22/2010', '$2y$12$frsiyUez/rzQvnVaeszZT.QuPSyToUst7/Up.uZXMr5Xoa3LJztM.', 'YAKUBANI', 'GANI', 'KUBUZA', '', NULL, NULL, NULL, 6, 5, 1, 1, 'cms_TSU_FAH_NS_22_2010_1788953938.jpg', 1, 1, 0, '2026-09-12 05:50:38', '::1', NULL, 1, '2026-09-09 11:38:58', '2026-09-12 04:50:50', NULL, 'active'),
(29, 'TSU/FAH/NS/22/2016', '$2y$12$czdPYOhlFfnI7KntFg.nbubjvnL9Q2kdSccB1NxtnlGNetdX7GUoG', 'MAMUDA', 'ELINA', '', '', NULL, NULL, NULL, 6, 5, 1, 1, 'cms_TSU_FAH_NS_22_2016_1788953950.jpg', 1, 1, 0, '2026-09-12 05:50:24', '::1', NULL, 1, '2026-09-09 11:39:10', '2026-09-12 04:50:36', NULL, 'active'),
(30, 'TSU/FAH/NS/22/2008', '$2y$12$vRCDGjvoFot1sIXdf6/W4uIik2iPqsTvO.BlOYZQpm4ygGgeWc.kS', 'EMMANUEL', 'PEACE', '', '', NULL, NULL, NULL, 6, 5, 1, 1, 'cms_TSU_FAH_NS_22_2008_1788953963.jpg', 1, 1, 0, '2026-09-12 06:14:11', '::1', NULL, 1, '2026-09-09 11:39:23', '2026-09-12 05:14:25', NULL, 'active'),
(31, 'TSU/FAH/NS/22/2019', '$2y$12$AReOeVD0axal0LtlcBoEReN4ncwSR.A05QznQk1PWVPKqn1UYTiq2', 'ONYEBUEKE', 'ROSELINE', 'CHIDIMMA', '', NULL, NULL, NULL, 6, 5, 1, 1, 'cms_TSU_FAH_NS_22_2019_1788953974.jpg', 1, 1, 0, '2026-09-12 06:13:54', '::1', NULL, 1, '2026-09-09 11:39:35', '2026-09-12 05:14:08', NULL, 'active');

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_exam_access_view`
-- (See below for the actual view)
--
CREATE TABLE `student_exam_access_view` (
`student_id` int(11) unsigned
,`matric_number` varchar(30)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`fingerprint_enrolled` tinyint(1)
,`exam_id` int(11) unsigned
,`exam_code` varchar(30)
,`exam_title` varchar(200)
,`duration` int(4)
,`exam_fingerprint_required` tinyint(1)
,`shuffle_questions` tinyint(1)
,`shuffle_options` tinyint(1)
,`exam_status` enum('draft','published','running','ended','archived')
,`batch_id` int(11) unsigned
,`batch_code` varchar(30)
,`batch_name` varchar(100)
,`batch_date` date
,`batch_start_time` time
,`batch_end_time` time
,`batch_duration` int(4)
,`batch_biometric_required` tinyint(1)
,`assignment_status` enum('assigned','started','submitted','reset')
,`assigned_at` timestamp
,`is_batch_time_active` int(2)
,`can_access_exam` int(2)
);

-- --------------------------------------------------------

--
-- Table structure for table `time_extensions`
--

CREATE TABLE `time_extensions` (
  `id` int(11) UNSIGNED NOT NULL,
  `exam_id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `attempt_id` int(11) UNSIGNED DEFAULT NULL,
  `extra_minutes` int(4) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `granted_by` int(11) UNSIGNED DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `student_exam_access_view`
--
DROP TABLE IF EXISTS `student_exam_access_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_exam_access_view`  AS SELECT `s`.`id` AS `student_id`, `s`.`matric_number` AS `matric_number`, `s`.`first_name` AS `first_name`, `s`.`last_name` AS `last_name`, `s`.`fingerprint_enrolled` AS `fingerprint_enrolled`, `e`.`id` AS `exam_id`, `e`.`exam_code` AS `exam_code`, `e`.`exam_title` AS `exam_title`, `e`.`duration` AS `duration`, `e`.`fingerprint_required` AS `exam_fingerprint_required`, `e`.`shuffle_questions` AS `shuffle_questions`, `e`.`shuffle_options` AS `shuffle_options`, `e`.`status` AS `exam_status`, `eb`.`id` AS `batch_id`, `eb`.`batch_code` AS `batch_code`, `eb`.`batch_name` AS `batch_name`, `eb`.`batch_date` AS `batch_date`, `eb`.`start_time` AS `batch_start_time`, `eb`.`end_time` AS `batch_end_time`, `eb`.`duration` AS `batch_duration`, `eb`.`biometric_required` AS `batch_biometric_required`, `es`.`status` AS `assignment_status`, `es`.`assigned_at` AS `assigned_at`, CASE WHEN `eb`.`id` is not null THEN current_timestamp() between concat(`eb`.`batch_date`,' ',`eb`.`start_time`) and concat(`eb`.`batch_date`,' ',`eb`.`end_time`) ELSE 1 END AS `is_batch_time_active`, CASE WHEN `eb`.`id` is not null AND `eb`.`biometric_required` = 1 AND `s`.`fingerprint_enrolled` = 0 THEN 0 WHEN `e`.`fingerprint_required` = 1 AND `s`.`fingerprint_enrolled` = 0 THEN 0 ELSE 1 END AS `can_access_exam` FROM (((`exam_students` `es` join `students` `s` on(`s`.`id` = `es`.`student_id`)) join `exams` `e` on(`e`.`id` = `es`.`exam_id`)) left join `exam_batches` `eb` on(`eb`.`id` = `es`.`batch_id`)) WHERE `es`.`status` in ('assigned','started') AND `s`.`status` = 1 AND `e`.`status` in ('published','running') ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_username` (`username`),
  ADD UNIQUE KEY `uk_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_attempt_question` (`attempt_id`,`question_id`);

--
-- Indexes for table `composite_exams`
--
ALTER TABLE `composite_exams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_composite_exam_code` (`exam_code`),
  ADD KEY `idx_composite_status` (`status`);

--
-- Indexes for table `composite_exam_answers`
--
ALTER TABLE `composite_exam_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_attempt_sub_question` (`attempt_id`,`composite_sub_question_id`);

--
-- Indexes for table `composite_exam_attempts`
--
ALTER TABLE `composite_exam_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_composite_exam_student_attempt` (`composite_exam_id`,`student_id`);

--
-- Indexes for table `composite_exam_results`
--
ALTER TABLE `composite_exam_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_composite_exam_student_result` (`composite_exam_id`,`student_id`);

--
-- Indexes for table `composite_exam_students`
--
ALTER TABLE `composite_exam_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_composite_exam_student` (`composite_exam_id`,`student_id`);

--
-- Indexes for table `composite_questions`
--
ALTER TABLE `composite_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_composite_exam` (`composite_exam_id`),
  ADD KEY `idx_composite_order` (`question_order`);

--
-- Indexes for table `composite_sub_options`
--
ALTER TABLE `composite_sub_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sub_question` (`composite_sub_question_id`),
  ADD KEY `idx_option_order` (`option_order`);

--
-- Indexes for table `composite_sub_questions`
--
ALTER TABLE `composite_sub_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_composite_question` (`composite_question_id`),
  ADD KEY `idx_sub_order` (`sub_order`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_dept_code` (`dept_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_code` (`exam_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_student_attempt` (`exam_id`,`student_id`);

--
-- Indexes for table `exam_batches`
--
ALTER TABLE `exam_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_students`
--
ALTER TABLE `exam_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_student` (`exam_id`,`student_id`);

--
-- Indexes for table `fingerprint_templates`
--
ALTER TABLE `fingerprint_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_level_name` (`level_name`),
  ADD KEY `idx_level_order` (`level_order`);

--
-- Indexes for table `promotion_history`
--
ALTER TABLE `promotion_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_promoted_by` (`promoted_by`),
  ADD KEY `idx_promotion_type` (`promotion_type`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam` (`exam_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_student_result` (`exam_id`,`student_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_semester_name` (`semester_name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_session_name` (`session_name`),
  ADD KEY `idx_is_current` (`is_current`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_key` (`setting_key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_matric` (`matric_number`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_level` (`level_id`),
  ADD KEY `idx_semester` (`semester_id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Indexes for table `time_extensions`
--
ALTER TABLE `time_extensions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=176;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19325;

--
-- AUTO_INCREMENT for table `composite_exams`
--
ALTER TABLE `composite_exams`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `composite_exam_answers`
--
ALTER TABLE `composite_exam_answers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `composite_exam_attempts`
--
ALTER TABLE `composite_exam_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `composite_exam_results`
--
ALTER TABLE `composite_exam_results`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `composite_exam_students`
--
ALTER TABLE `composite_exam_students`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `composite_questions`
--
ALTER TABLE `composite_questions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `composite_sub_options`
--
ALTER TABLE `composite_sub_options`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `composite_sub_questions`
--
ALTER TABLE `composite_sub_questions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `exam_batches`
--
ALTER TABLE `exam_batches`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_students`
--
ALTER TABLE `exam_students`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `fingerprint_templates`
--
ALTER TABLE `fingerprint_templates`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `promotion_history`
--
ALTER TABLE `promotion_history`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=509;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `time_extensions`
--
ALTER TABLE `time_extensions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
