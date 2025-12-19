-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 04:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pixelwizard`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `surname` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `surname`, `firstname`, `middle_initial`, `role`) VALUES
(6, 'admin@kld.edu.ph', 'admin123', 'Admin', 'System', NULL, 'administrator');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otps`
--

CREATE TABLE `password_reset_otps` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_otps`
--

INSERT INTO `password_reset_otps` (`id`, `email`, `otp_code`, `created_at`, `expires_at`, `used`) VALUES
(1, 'abfaustino@kld.edu.ph', '963239', '2025-11-18 18:22:54', '2025-11-18 11:32:54', 0);

-- --------------------------------------------------------

--
-- Table structure for table `sanctions`
--

CREATE TABLE `sanctions` (
  `id` int(11) NOT NULL,
  `violation_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sanction_type` varchar(255) DEFAULT NULL,
  `status` enum('pending','in-progress','completed') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completion_proof` varchar(500) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `counselor_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sanctions`
--

INSERT INTO `sanctions` (`id`, `violation_id`, `user_id`, `sanction_type`, `status`, `due_date`, `completion_proof`, `completion_date`, `counselor_notes`, `created_at`) VALUES
(1, 6, NULL, 'verbal_reprimand', 'completed', '0000-00-00', NULL, '2025-11-30', '', '2025-11-27 16:22:28'),
(4, 7, NULL, 'verbal_reprimand', 'completed', '0000-00-00', NULL, '2025-11-30', '', '2025-11-28 14:50:47'),
(5, 8, NULL, 'verbal_reprimand', 'completed', '2025-12-12', NULL, '2025-11-30', '', '2025-11-29 06:04:27'),
(6, 9, NULL, 'suspension_6_days', 'completed', NULL, NULL, '2025-11-30', '', '2025-11-29 07:25:48'),
(7, 11, NULL, 'verbal_reprimand', 'completed', '0000-00-00', NULL, '2025-11-30', '', '2025-11-29 07:35:39'),
(8, 12, NULL, 'verbal_reprimand', 'completed', '0000-00-00', NULL, '2025-11-30', '', '2025-11-29 07:49:46'),
(9, 13, NULL, 'written_warning_1', 'completed', '0000-00-00', NULL, '2025-11-30', '', '2025-11-29 07:51:23'),
(10, 14, NULL, 'written_warning_1', 'completed', '0000-00-00', NULL, '2025-11-30', '', '2025-11-29 09:06:09'),
(11, 16, NULL, 'verbal_reprimand', 'completed', '2025-11-11', NULL, '2025-11-29', '', '2025-11-29 10:26:12'),
(12, 15, NULL, 'written_warning_3', 'completed', '0000-00-00', NULL, '2025-12-30', 'r', '2025-11-29 10:38:54'),
(13, 17, NULL, 'verbal_reprimand', 'completed', '2025-11-11', 'sanction_13_1764413965.gif', '2025-11-29', 'try', '2025-11-29 10:53:49'),
(14, 18, NULL, 'verbal_reprimand', 'completed', '2005-12-12', 'sanction_14_1764421426.gif', '2025-11-29', 'nothing', '2025-11-29 11:00:11'),
(15, 20, NULL, 'verbal_reprimand', 'completed', '0000-00-00', NULL, '2025-11-30', '', '2025-11-29 17:10:26'),
(16, 19, NULL, 'suspension_6_days', 'completed', '2024-12-12', NULL, '2025-11-30', 'try', '2025-11-29 17:41:05'),
(17, 21, NULL, 'written_warning_1', 'completed', NULL, NULL, '2025-11-30', '', '2025-11-30 07:52:52'),
(18, 22, 42, 'verbal_reprimand', 'completed', '2025-12-12', NULL, '2025-12-04', 'lel', '2025-11-30 13:28:32'),
(19, 23, 42, 'verbal_reprimand', 'completed', '2025-12-12', NULL, '2025-11-30', '', '2025-11-30 17:14:02'),
(20, 24, 45, 'verbal_reprimand', 'completed', '2025-12-12', NULL, '2025-11-30', '', '2025-11-30 17:16:40'),
(21, 25, 45, 'verbal_reprimand', 'completed', '2025-12-12', NULL, '2025-11-30', '', '2025-11-30 17:28:23'),
(22, 29, 44, 'verbal_reprimand', 'completed', '2025-02-11', NULL, '2025-11-30', '', '2025-11-30 17:31:38'),
(23, 30, 44, 'verbal_reprimand', 'completed', '2025-12-02', NULL, '2025-12-02', '', '2025-12-01 12:45:12'),
(24, 34, 42, 'verbal_reprimand', 'completed', '2025-12-02', NULL, '2025-12-03', 'f', '2025-12-01 13:16:19'),
(25, 35, 42, 'written_warning_1', 'completed', '2025-12-18', NULL, '2025-12-02', '', '2025-12-01 15:32:34'),
(26, 37, 42, 'verbal_reprimand', 'completed', '2025-12-12', NULL, '2025-12-02', '', '2025-12-02 04:33:16'),
(27, 36, 42, 'verbal_reprimand', 'in-progress', '2025-12-03', NULL, NULL, '', '2025-12-02 05:15:05'),
(28, 38, 44, 'verbal_reprimand', 'completed', '2025-12-02', '/html/try/uploads/sanction_proofs/sanction_28_1764658928.gif', '2025-12-02', '10', '2025-12-02 07:01:44'),
(29, 39, 44, 'verbal_reprimand', 'completed', '2025-12-02', NULL, '2025-12-02', '', '2025-12-02 07:10:33'),
(30, 40, 45, 'verbal_reprimand', 'in-progress', '2025-12-02', NULL, NULL, '', '2025-12-02 07:23:39'),
(31, 44, 45, 'verbal_reprimand', 'in-progress', '2025-12-02', NULL, NULL, '', '2025-12-02 07:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `surname` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `member_since` datetime DEFAULT current_timestamp(),
  `qr_code` text DEFAULT NULL,
  `completed_setup` tinyint(1) DEFAULT 0,
  `has_setup` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `email`, `password`, `fullname`, `surname`, `firstname`, `middle_initial`, `position`, `department`, `phone`, `sex`, `member_since`, `qr_code`, `completed_setup`, `has_setup`) VALUES
(1, 'staff@kld.edu.ph', 'staff123', 'Test Staff', 'Staff', 'Test', 'T', 'Security Officer', 'Campus Security', '09123456789', 'Male', '2025-11-25 22:42:47', NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `student_number` varchar(30) DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `course` varchar(10) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `year_level` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`id`, `user_id`, `surname`, `firstname`, `middle_initial`, `student_number`, `sex`, `course`, `section`, `qr_code`, `created_at`, `year_level`) VALUES
(20, 42, 'Faustino', 'Angelo Brian', 'R', '2024-3-100', 'Male', 'BSP', '209', 'qrcodes/qr_user_42.png', '2025-11-27 02:41:02', '2'),
(21, 44, 'Ventus', 'Karl Thomas', 'L', '2024-4-600', 'Male', 'BSIS', '209', 'qrcodes/qr_user_44.png', '2025-11-27 12:37:39', '2'),
(22, 45, 'Reyes', 'Pedro', 'G', '2022-3-400', 'Male', 'BSN', '405', 'qrcodes/qr_user_45.png', '2025-11-29 15:11:53', '3');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_setup` tinyint(1) NOT NULL DEFAULT 0,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `profile_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `created_at`, `completed_setup`, `otp_code`, `otp_expires_at`, `profile_completed`) VALUES
(42, '', 'abfaustino@kld.edu.ph', '$2y$10$tE9YksB/78d1bVGapqn3..SHrYwGHv0pPD3NKoMOebbaIVJC4qfjm', '2025-11-27 02:39:33', 1, '693858', '2025-11-30 04:24:08', 1),
(44, '', 'ktventus@kld.edu.ph', '$2y$10$jbPz1ZHdFC2GgVxj5y8LwuMgtqJsdzg82S0kpsKrW.BMPx8MDyzrq', '2025-11-27 12:28:44', 1, NULL, NULL, 1),
(45, 'John Doe', 'john.doe@kld.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-11-29 15:06:08', 1, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `violations`
--

CREATE TABLE `violations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `recorded_by` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','staff') NOT NULL,
  `violation_type` varchar(255) NOT NULL,
  `violation_category` enum('minor','major') NOT NULL DEFAULT 'minor',
  `description` text DEFAULT NULL,
  `proof_filename` varchar(255) DEFAULT NULL,
  `status` enum('pending','under_review','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `violations`
--

INSERT INTO `violations` (`id`, `user_id`, `recorded_by`, `admin_id`, `staff_id`, `user_type`, `violation_type`, `violation_category`, `description`, `proof_filename`, `status`, `created_at`, `resolved_at`, `resolved_by`, `resolution_notes`) VALUES
(6, 42, 1, NULL, NULL, 'admin', 'No ID', 'minor', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(7, 42, 6, NULL, NULL, 'admin', 'No ID', 'minor', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(8, 42, 6, NULL, NULL, 'admin', 'Improper Attire', 'minor', 'Angelo', NULL, 'resolved', '2025-12-01 19:23:00', NULL, NULL, NULL),
(9, 42, 6, NULL, NULL, 'admin', 'No ID', 'minor', '', NULL, 'resolved', '2025-12-02 11:40:00', NULL, NULL, NULL),
(11, 42, 6, NULL, NULL, 'admin', 'No ID', 'minor', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(12, 42, 6, NULL, NULL, 'admin', 'Improper Attire', 'minor', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(13, 42, 6, NULL, NULL, 'admin', 'Improper Attire', 'minor', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(14, 42, 6, NULL, NULL, 'admin', 'Mobile Phone Use', 'major', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(15, 42, 6, NULL, NULL, 'admin', 'Public Display of Affection', 'minor', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(16, 42, 6, NULL, NULL, 'admin', 'No ID', 'major', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(17, 42, 6, NULL, NULL, 'admin', 'Gambling Materials', 'major', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(18, 42, 6, NULL, NULL, 'admin', 'Parking Violation', 'major', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(19, 42, 6, NULL, NULL, 'admin', 'ID or Document Misuse', 'minor', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(20, 42, 1, NULL, NULL, 'staff', 'No ID', 'minor', '', NULL, 'resolved', '0000-00-00 00:00:00', NULL, NULL, NULL),
(21, 44, 6, NULL, NULL, 'admin', 'Gambling Materials', 'minor', '', NULL, 'resolved', '2025-12-03 05:00:00', NULL, NULL, NULL),
(22, 42, 6, NULL, NULL, 'admin', 'Drug Violation', 'major', '', NULL, 'resolved', '2025-11-30 21:14:52', NULL, NULL, NULL),
(23, 42, 6, NULL, NULL, 'admin', 'Classroom Entry', 'minor', '', NULL, 'resolved', '2025-12-01 01:13:41', NULL, NULL, NULL),
(24, 45, 6, NULL, NULL, 'admin', 'Disrespect', 'minor', '', NULL, 'resolved', '2025-12-01 01:16:12', NULL, NULL, NULL),
(25, 45, 6, NULL, NULL, 'admin', 'Late', 'minor', '', NULL, 'resolved', '2025-12-01 01:27:56', NULL, NULL, NULL),
(29, 44, 6, NULL, NULL, 'admin', 'Disrespect', 'minor', '', NULL, 'resolved', '2025-12-01 01:31:20', NULL, NULL, NULL),
(30, 44, 6, NULL, NULL, 'admin', 'Gambling Materials', 'minor', '', NULL, 'resolved', '2025-12-01 12:14:32', NULL, NULL, NULL),
(34, 42, 6, NULL, NULL, 'admin', 'Public Display of Affection', 'minor', '', NULL, 'resolved', '2025-12-01 21:15:53', NULL, NULL, NULL),
(35, 42, 1, NULL, NULL, 'staff', 'Improper Uniform', 'minor', '', NULL, 'resolved', '2025-12-01 23:06:59', NULL, NULL, NULL),
(36, 42, 6, NULL, NULL, 'admin', 'Improper Uniform', 'minor', '', NULL, 'under_review', '2025-12-02 11:18:27', NULL, NULL, NULL),
(37, 42, 6, NULL, NULL, 'admin', 'Parking Violation', 'minor', '', NULL, 'resolved', '2025-12-02 03:22:00', NULL, NULL, NULL),
(38, 44, 6, NULL, NULL, 'admin', 'Mobile Phone Use', 'minor', '', 'violation_proof_1764658904_692e8ed81f306.png', 'resolved', '2025-12-02 14:22:01', NULL, NULL, NULL),
(39, 44, 6, NULL, NULL, 'admin', 'Gambling Materials', 'minor', '', 'violation_proof_1764659433_692e90e9b907c.jpg', 'resolved', '2025-12-02 15:03:55', NULL, NULL, NULL),
(40, 45, 6, NULL, NULL, 'admin', 'Other Minor', 'minor', '', 'violation_proof_1764660218_692e93fae463b.png', 'under_review', '2025-12-02 15:12:27', NULL, NULL, NULL),
(44, 45, 6, NULL, NULL, 'admin', 'Late', 'minor', '', 'uploads/violation_proofs/violation_proof_1764661163_692e97ab7a5f3.jpg', 'under_review', '2025-12-02 15:39:01', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `violation_conversion_tracking`
--

CREATE TABLE `violation_conversion_tracking` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `conversion_cycle` int(11) NOT NULL DEFAULT 1 COMMENT 'Which conversion cycle (1st, 2nd, 3rd...)',
  `converted_violation_ids` text DEFAULT NULL COMMENT 'Comma-separated list of minor violation IDs that were converted in this cycle',
  `resulting_major_id` int(11) DEFAULT NULL COMMENT 'The major violation ID that resulted from this conversion',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `violation_conversion_tracking`
--

INSERT INTO `violation_conversion_tracking` (`id`, `student_id`, `conversion_cycle`, `converted_violation_ids`, `resulting_major_id`, `created_at`) VALUES
(1, 44, 1, '103,101,100', 104, '2025-12-02 11:30:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp_code` (`otp_code`);

--
-- Indexes for table `sanctions`
--
ALTER TABLE `sanctions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `violation_id` (`violation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `violations`
--
ALTER TABLE `violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`recorded_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category` (`violation_category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `admin_id_2` (`admin_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `violation_conversion_tracking`
--
ALTER TABLE `violation_conversion_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sanctions`
--
ALTER TABLE `sanctions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `violation_conversion_tracking`
--
ALTER TABLE `violation_conversion_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sanctions`
--
ALTER TABLE `sanctions`
  ADD CONSTRAINT `sanctions_ibfk_1` FOREIGN KEY (`violation_id`) REFERENCES `violations` (`id`),
  ADD CONSTRAINT `sanctions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `violations`
--
ALTER TABLE `violations`
  ADD CONSTRAINT `violations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `violations_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`),
  ADD CONSTRAINT `violations_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
