-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2026 at 08:50 AM
-- Server version: 8.0.43
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `literary_planner`
--
CREATE DATABASE IF NOT EXISTS `literary_planner` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `literary_planner`;

-- --------------------------------------------------------

--
-- Table structure for table `academic_calendars`
--

CREATE TABLE `academic_calendars` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `semester` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `academic_calendars`
--

INSERT INTO `academic_calendars` (`id`, `user_id`, `course_id`, `semester`, `academic_year`, `name`, `created_at`) VALUES
(1, 2, 1, 5, '2025-2026', 'Sem 5 CE Calendar', '2026-03-21 01:44:37');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `due_date` datetime NOT NULL,
  `status` enum('pending','in_progress','completed','submitted') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `calendar_id` int NOT NULL,
  `subject_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `type` enum('holiday','internal_exam','external_exam','practical_exam','submission','custom') DEFAULT 'custom'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `semester` int NOT NULL,
  `subject_id` int NOT NULL,
  `internal_marks` int DEFAULT '0',
  `external_marks` int DEFAULT '0',
  `practical_marks` int DEFAULT '0',
  `grade` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gtu_courses`
--

CREATE TABLE `gtu_courses` (
  `id` int NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `total_semesters` int DEFAULT '8'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gtu_courses`
--

INSERT INTO `gtu_courses` (`id`, `branch_name`, `code`, `total_semesters`) VALUES
(1, 'Computer Engineering', '07', 8),
(2, 'Information Technology', '16', 8),
(3, 'Mechanical Engineering', '19', 8),
(4, 'Civil Engineering', '06', 8),
(5, 'Electrical Engineering', '09', 8);

-- --------------------------------------------------------

--
-- Table structure for table `gtu_notifications`
--

CREATE TABLE `gtu_notifications` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `link_url` varchar(255) NOT NULL,
  `date_posted` date NOT NULL,
  `is_important` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gtu_notifications`
--

INSERT INTO `gtu_notifications` (`id`, `title`, `link_url`, `date_posted`, `is_important`, `created_at`) VALUES
(1, 'Revised Academic Calendar for Winter 2025', 'https://gtu.ac.in', '2026-03-21', 1, '2026-03-21 01:44:37'),
(2, 'Exam form filling dates extended', 'https://gtu.ac.in', '2026-03-19', 0, '2026-03-21 01:44:37');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int NOT NULL,
  `subject_id` int NOT NULL,
  `uploaded_by` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('note','paper','book','video','other') NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `downloads` int DEFAULT '0',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `study_plans`
--

CREATE TABLE `study_plans` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `subject_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `plan_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('scheduled','completed','missed') DEFAULT 'scheduled',
  `focus_hours` decimal(5,2) DEFAULT '0.00',
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `semester` int NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `credits` int DEFAULT '3'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `course_id`, `semester`, `subject_name`, `subject_code`, `credits`) VALUES
(1, 1, 1, 'Mathematics - 1', '3110014', 4),
(2, 1, 1, 'Basic Electrical Engineering', '3110005', 4),
(3, 1, 1, 'Programming for Problem Solving', '3110003', 4),
(4, 1, 3, 'Data Structures', '3130702', 4),
(5, 1, 3, 'Database Management Systems', '3130703', 4),
(6, 1, 3, 'Digital Fundamentals', '3130704', 4),
(7, 1, 5, 'Design and Analysis of Algorithms', '3150703', 4),
(8, 1, 5, 'Object Oriented Programming using JAVA', '3150704', 4),
(9, 1, 5, 'Computer Networks', '3150710', 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `avatar`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@literaryplanner.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, '2026-03-21 01:44:37', '2026-03-21 01:44:37'),
(2, 'Student Demo', 'student@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', NULL, '2026-03-21 01:44:37', '2026-03-21 01:44:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `theme` enum('light','dark','system') DEFAULT 'light',
  `email_notifications` tinyint(1) DEFAULT '1',
  `browser_notifications` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `theme`, `email_notifications`, `browser_notifications`) VALUES
(1, 1, 'light', 1, 1),
(2, 2, 'dark', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_calendars`
--
ALTER TABLE `academic_calendars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `calendar_id` (`calendar_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `gtu_courses`
--
ALTER TABLE `gtu_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `gtu_notifications`
--
ALTER TABLE `gtu_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `study_plans`
--
ALTER TABLE `study_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_calendars`
--
ALTER TABLE `academic_calendars`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gtu_courses`
--
ALTER TABLE `gtu_courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gtu_notifications`
--
ALTER TABLE `gtu_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `study_plans`
--
ALTER TABLE `study_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_calendars`
--
ALTER TABLE `academic_calendars`
  ADD CONSTRAINT `academic_calendars_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `academic_calendars_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `gtu_courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`calendar_id`) REFERENCES `academic_calendars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_plans`
--
ALTER TABLE `study_plans`
  ADD CONSTRAINT `study_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `study_plans_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `gtu_courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
--
-- Database: `planner`
--
CREATE DATABASE IF NOT EXISTS `planner` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `planner`;
--
-- Database: `student`
--
CREATE DATABASE IF NOT EXISTS `student` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `student`;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int NOT NULL,
  `course_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`) VALUES
(1, 'Computer Engineering'),
(2, 'Mechanical Engineering'),
(3, 'Civil Engineering'),
(4, 'Electrical Engineering'),
(5, 'Electronics & Communication'),
(6, 'Chemical Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `cust_no` int NOT NULL,
  `cust_name` varchar(50) NOT NULL,
  `item_purchase` varchar(100) DEFAULT NULL,
  `mob_no` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`cust_no`, `cust_name`, `item_purchase`, `mob_no`) VALUES
(1, 'XYZ', 'Laptop', '9876543210'),
(2, 'Ravi Sharma', 'Smartphone', '9123456780'),
(4, 'Priya Mehta', 'Smartwatch', '9090909090');

-- --------------------------------------------------------

--
-- Table structure for table `emp`
--

CREATE TABLE `emp` (
  `emp_no` int NOT NULL,
  `emp_name` varchar(50) NOT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `emp`
--

INSERT INTO `emp` (`emp_no`, `emp_name`, `designation`, `salary`, `department`, `joining_date`, `email`, `phone`) VALUES
(101, 'Aditya Mourya', 'Manager', 75000.00, 'HR', '2020-05-10', 'aditya.mourya@example.com', '9876543210'),
(102, 'Mayank Rathod', 'Developer', 55000.00, 'IT', '2021-02-15', 'mayank.rathod@example.com', '9876543211'),
(103, 'Ankit Chauhan', 'Designer', 45000.00, 'Design', '2019-11-20', 'ankit.chauhan@example.com', '9876543212'),
(104, 'Krrish Patel', 'Tester', 40000.00, 'QA', '2022-06-25', 'krrish.patel@example.com', '9876543213'),
(105, 'Faiz Farooqui', 'Software Engineer', 48000.00, 'IT', '2022-01-10', 'faiz.farooqui@example.com', '9876543217'),
(106, 'Henil Don', 'Business Analyst', 52000.00, 'Analytics', '2021-05-22', 'henil.don@example.com', '9876543218'),
(107, 'Krupal Patel', 'UI/UX Designer', 45000.00, 'Design', '2023-03-15', 'krupal.patel@example.com', '9876543219'),
(108, 'Anish Prajapati', 'Intern', 35000.00, 'IT', '2025-09-29', 'anish.prajapati@example.com', '9876543221');

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `q_id` int NOT NULL,
  `question` varchar(255) NOT NULL,
  `option1` varchar(100) NOT NULL,
  `option2` varchar(100) NOT NULL,
  `option3` varchar(100) NOT NULL,
  `option4` varchar(100) NOT NULL,
  `correct_option` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`q_id`, `question`, `option1`, `option2`, `option3`, `option4`, `correct_option`) VALUES
(1, 'What is the capital of India?', 'Mumbai', 'Delhi', 'Kolkata', 'Chennai', 2),
(2, 'Which language is used for web apps?', 'Python', 'C++', 'PHP', 'Java', 3),
(3, 'What is 5 + 7?', '10', '11', '12', '13', 3),
(4, 'Which planet is known as the Red Planet?', 'Earth', 'Mars', 'Jupiter', 'Venus', 2),
(5, 'Who wrote \"Romeo and Juliet\"?', 'Shakespeare', 'Hemingway', 'Tolkien', 'Dickens', 1),
(6, 'HTML stands for?', 'Hyper Trainer Marking Language', 'Hyper Text Markup Language', 'Hyper Text Making Language', 'High Text Markup Language', 2),
(7, 'Which is the largest ocean on Earth?', 'Atlantic', 'Indian', 'Arctic', 'Pacific', 4),
(8, 'PHP is a?', 'Server-side scripting language', 'Client-side scripting language', 'Database', 'Operating System', 1);

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `result_id` int NOT NULL,
  `user_id` int NOT NULL,
  `score` int NOT NULL,
  `total` int NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `date_taken` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`result_id`, `user_id`, `score`, `total`, `percentage`, `date_taken`) VALUES
(1, 3, 2, 8, 25.00, '2025-09-29 15:47:44'),
(2, 3, 4, 8, 50.00, '2025-09-29 15:49:53'),
(3, 3, 8, 8, 100.00, '2025-09-29 15:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `course` varchar(50) DEFAULT NULL,
  `semester` int DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `course`, `semester`, `registration_date`) VALUES
(1, 'Anish', 'Prajapati', 'anishprajapati19802005@gmail.com', '9099777455', 'Male', 'B.voc', 3, '2025-09-29 10:24:45'),
(2, 'Ankit', 'Chauhan', 'chauankit25@gmail.com', '6352288605', 'Male', 'Computer Engineering', 3, '2025-09-29 10:28:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`) VALUES
(1, 'Anish', 'Anish566'),
(3, 'Anish123', '$2y$10$R7MAzjBL9HPGGoK6pRT2CerPuDIMzpgPwrTcPDYuhZ2nakJppCf3e');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`cust_no`);

--
-- Indexes for table `emp`
--
ALTER TABLE `emp`
  ADD PRIMARY KEY (`emp_no`);

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`q_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `q_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `result_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
--
-- Database: `studentsdb`
--
CREATE DATABASE IF NOT EXISTS `studentsdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `studentsdb`;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `enrollment_no` varchar(20) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `semester` int DEFAULT NULL,
  `contact_no` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`enrollment_no`, `first_name`, `last_name`, `semester`, `contact_no`) VALUES
('1', 'Mitesh', 'Matera', 2, '9876543210'),
('2', 'Ankit', 'Nimla', 3, '9123456789'),
('3', 'Prahlad', 'Varli', 4, '9988776655'),
('4', 'Devang', 'Halpti', 5, '9090909090');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`enrollment_no`);
--
-- Database: `timetablegen`
--
CREATE DATABASE IF NOT EXISTS `timetablegen` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `timetablegen`;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int NOT NULL,
  `course_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credits` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `credits`, `type`, `department`, `status`) VALUES
(1, 'CS301', 'Data Structures', 4, 'core', 'CSE', 'active'),
(2, 'CS311', 'Data Structures Lab', 2, 'lab', 'CSE', 'active'),
(3, 'MA201', 'Discrete Math', 3, 'core', 'CSE', 'active'),
(4, 'CS302', 'Computer Networks', 4, 'core', 'CSE', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hod` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Not Assigned',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `est_year` int DEFAULT '2000',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `hod`, `status`, `est_year`, `email`, `phone`, `description`) VALUES
(1, 'CE', 'Computer Engineering', 'Not Assigned', 'active', 2000, '', '', NULL),
(2, 'IT', 'Information Technology', 'Not Assigned', 'active', 2000, '', '', NULL),
(3, 'ME', 'Mechanical Engineering', 'Not Assigned', 'active', 2000, '', '', NULL),
(4, 'EE', 'Electrical Engineering', 'Not Assigned', 'active', 2000, '', '', NULL),
(5, 'CL', 'Civil Engineering', 'Not Assigned', 'active', 2000, '', '', NULL),
(6, 'EC', 'Electronics & Comm.', 'Not Assigned', 'active', 2000, '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faculty_courses`
--

CREATE TABLE `faculty_courses` (
  `id` int NOT NULL,
  `faculty_id` int NOT NULL,
  `course_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int NOT NULL,
  `type` enum('Lecture','Lab') COLLATE utf8mb4_unicode_ci DEFAULT 'Lecture',
  `status` enum('active','maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `status`) VALUES
(1, 'LH101', 60, 'Lecture', 'active'),
(2, 'LH102', 60, 'Lecture', 'active'),
(3, 'LAB201', 30, 'Lab', 'active'),
(4, 'LAB202', 30, 'Lab', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int NOT NULL,
  `department_id` int NOT NULL,
  `program_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` int NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_entries`
--

CREATE TABLE `timetable_entries` (
  `id` int NOT NULL,
  `timetable_id` int NOT NULL,
  `course_id` int NOT NULL,
  `faculty_id` int NOT NULL,
  `room_id` int NOT NULL,
  `time_slot_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int NOT NULL,
  `day_of_week` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `day_of_week`, `start_time`, `end_time`) VALUES
(29, 'Friday', '09:00:00', '10:00:00'),
(30, 'Friday', '10:00:00', '11:00:00'),
(31, 'Friday', '11:00:00', '12:00:00'),
(32, 'Friday', '12:00:00', '13:00:00'),
(33, 'Friday', '13:00:00', '14:00:00'),
(34, 'Friday', '14:00:00', '15:00:00'),
(35, 'Friday', '15:00:00', '17:00:00'),
(1, 'Monday', '09:00:00', '10:00:00'),
(2, 'Monday', '10:00:00', '11:00:00'),
(3, 'Monday', '11:00:00', '12:00:00'),
(4, 'Monday', '12:00:00', '13:00:00'),
(5, 'Monday', '13:00:00', '14:00:00'),
(6, 'Monday', '14:00:00', '15:00:00'),
(7, 'Monday', '15:00:00', '17:00:00'),
(22, 'Thursday', '09:00:00', '10:00:00'),
(23, 'Thursday', '10:00:00', '11:00:00'),
(24, 'Thursday', '11:00:00', '12:00:00'),
(25, 'Thursday', '12:00:00', '13:00:00'),
(26, 'Thursday', '13:00:00', '14:00:00'),
(27, 'Thursday', '14:00:00', '15:00:00'),
(28, 'Thursday', '15:00:00', '17:00:00'),
(8, 'Tuesday', '09:00:00', '10:00:00'),
(9, 'Tuesday', '10:00:00', '11:00:00'),
(10, 'Tuesday', '11:00:00', '12:00:00'),
(11, 'Tuesday', '12:00:00', '13:00:00'),
(12, 'Tuesday', '13:00:00', '14:00:00'),
(13, 'Tuesday', '14:00:00', '15:00:00'),
(14, 'Tuesday', '15:00:00', '17:00:00'),
(15, 'Wednesday', '09:00:00', '10:00:00'),
(16, 'Wednesday', '10:00:00', '11:00:00'),
(17, 'Wednesday', '11:00:00', '12:00:00'),
(18, 'Wednesday', '12:00:00', '13:00:00'),
(19, 'Wednesday', '13:00:00', '14:00:00'),
(20, 'Wednesday', '14:00:00', '15:00:00'),
(21, 'Wednesday', '15:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','faculty','student') COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `employee_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availability` enum('available','busy','leave') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `roll_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_year` int DEFAULT '1',
  `current_semester` int DEFAULT '1',
  `cgpa` decimal(4,2) DEFAULT '0.00',
  `attendance_percent` int DEFAULT '0',
  `student_status` enum('Active','Inactive','Graduated') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `batch_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `phone`, `program_level`, `department`, `created_at`, `employee_id`, `specialization`, `availability`, `roll_number`, `current_year`, `current_semester`, `cgpa`, `attendance_percent`, `student_status`, `batch_year`, `section`) VALUES
(18, 'Rahul Patel', 'rahul@student.edu', '$2y$10$lbG3HJDxgXNGblhfXRGIqOageF4rbg0hQ59I8Xm1UN.qh2Bpz0Yjm', 'student', '', 'Degree', 'CE', '2026-03-21 04:13:17', NULL, NULL, 'available', NULL, 1, 1, 0.00, 0, 'Active', NULL, NULL),
(19, 'Dr. Priya Sharma', 'priya@faculty.edu', '$2y$10$ixhozd3D1T9r6vMR23h0Wu5D8WP0H6fSQyMblNDeQ2NMa8YGHcrf.', 'faculty', '', 'Degree', 'IT', '2026-03-21 04:24:00', NULL, NULL, 'available', NULL, 1, 1, 0.00, 0, 'Active', NULL, NULL),
(20, 'ANish', 'anish@lit.edu', '$2y$10$HjCiPeejXzjwYqjwYb4y2.wdjYkS6V7PQ9aVRa.ed7gP1Mu01QdGy', 'faculty', '', 'Degree', 'CE', '2026-03-21 04:42:59', NULL, NULL, 'available', NULL, 1, 1, 0.00, 0, 'Active', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `faculty_courses`
--
ALTER TABLE `faculty_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faculty_id` (`faculty_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timetable_id` (`timetable_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `time_slot_id` (`time_slot_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `day_of_week` (`day_of_week`,`start_time`,`end_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `faculty_courses`
--
ALTER TABLE `faculty_courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `faculty_courses`
--
ALTER TABLE `faculty_courses`
  ADD CONSTRAINT `faculty_courses_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faculty_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD CONSTRAINT `timetable_entries_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_ibfk_4` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_ibfk_5` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
