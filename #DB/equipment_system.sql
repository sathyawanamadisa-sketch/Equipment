-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2026 at 08:01 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `equipment_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL DEFAULT '',
  `asset_number` varchar(50) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `brand_model` varchar(100) NOT NULL,
  `status` enum('Available','Issued','Workshop') NOT NULL DEFAULT 'Available',
  `section_division` varchar(100) NOT NULL,
  `date_added` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `item_name`, `asset_number`, `item_type`, `brand_model`, `status`, `section_division`, `date_added`, `created_at`) VALUES
(7, 'Dell Vostro 15 3000 10 GEN Laptop &  Back bag', '7COXH33', 'Laptop', 'DEll', 'Issued', 'webcell', '2026-09-24', '2026-09-24 10:15:28'),
(8, 'Dell Power Adapter', 'OMGJN9-LOCOO-046-314A-A09', 'Laptop Charger', 'DEll', 'Issued', 'webcell', '2026-09-24', '2026-09-24 10:34:07'),
(9, 'DELL MOUSE', 'ONMJ83-L0300-0C2-04NE', 'Mouse', 'DEll', 'Issued', 'webcell', '2026-09-24', '2026-09-24 10:34:42'),
(10, 'Acer Aspire 3 Core i5 Laptop', 'NXA0TAA0050241134E3400', 'Laptop', 'Acer', 'Issued', 'webcell', '2026-09-24', '2026-09-24 10:36:14'),
(11, 'ACER POWER ADEPTER', 'F258351731042665', 'Laptop Charger', 'Acer', 'Issued', 'webcell', '2026-09-24', '2026-09-24 10:36:45'),
(12, 'LOGITECH HEADSET', '1915ALA08F98', 'Headset', 'LOGITECH', 'Workshop', 'webcell', '2026-09-24', '2026-09-24 10:37:19'),
(13, 'DELL MOUSE', 'LO300-OC2-04YZ', 'Mouse', 'DEll', 'Issued', 'webcell', '2026-09-24', '2026-09-24 10:37:46'),
(14, 'Acer Back Bag', 'no', 'Bag', 'Acer', 'Available', 'webcell', '2026-09-24', '2026-09-24 10:38:13');

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `officer_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Issued','Returned') NOT NULL DEFAULT 'Issued',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `equipment_id`, `officer_id`, `issue_date`, `return_date`, `status`, `notes`, `created_at`) VALUES
(13, 7, 1, '2026-09-24', NULL, 'Issued', '', '2026-09-24 10:39:05'),
(14, 8, 1, '2026-09-24', NULL, 'Issued', '', '2026-09-24 10:39:05'),
(15, 9, 1, '2026-09-24', NULL, 'Issued', '', '2026-09-24 10:39:05'),
(16, 10, 2, '2026-09-24', NULL, 'Issued', '', '2026-09-24 10:39:20'),
(17, 11, 2, '2026-09-24', NULL, 'Issued', '', '2026-09-24 10:39:20'),
(18, 13, 2, '2026-09-24', NULL, 'Issued', '', '2026-09-24 10:39:20');

-- --------------------------------------------------------

--
-- Table structure for table `officers`
--

CREATE TABLE `officers` (
  `id` int(11) NOT NULL,
  `service_number` varchar(50) NOT NULL,
  `rank` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `section_division` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `seniority_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officers`
--

INSERT INTO `officers` (`id`, `service_number`, `rank`, `name`, `phone_number`, `section_division`, `created_at`, `seniority_order`) VALUES
(1, 'S/284216', 'S/Sgt', 'Sathyawan MMA', '715318452', 'webcell', '2026-09-23 06:29:05', 1),
(2, 'S/285767', 'Sgt', 'Nisayuru RAS', '6565146', 'webcell', '2026-09-23 08:04:15', 3),
(3, 'S/284239', 'S/Sgt', 'Rajapacsha KKK', '27767387', 'webcell', '2026-09-24 05:17:33', 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$UrWX1SDVorShY7BgAtFc0u5/tB1ccWRTJ0Jv4uQlPb02FbFnxwTb6', 'Administrator', '2026-09-22 10:44:04');

-- --------------------------------------------------------

--
-- Table structure for table `workshop`
--

CREATE TABLE `workshop` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `admit_date` date NOT NULL,
  `workshop_job_number` varchar(50) NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('In Workshop','Repaired') NOT NULL DEFAULT 'In Workshop',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workshop`
--

INSERT INTO `workshop` (`id`, `equipment_id`, `admit_date`, `workshop_job_number`, `return_date`, `status`, `notes`, `created_at`) VALUES
(5, 12, '2026-09-24', 'png112', NULL, 'In Workshop', 'not work', '2026-09-24 10:39:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_number` (`asset_number`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `officer_id` (`officer_id`);

--
-- Indexes for table `officers`
--
ALTER TABLE `officers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_number` (`service_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `workshop`
--
ALTER TABLE `workshop`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `officers`
--
ALTER TABLE `officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `workshop`
--
ALTER TABLE `workshop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issues_ibfk_2` FOREIGN KEY (`officer_id`) REFERENCES `officers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workshop`
--
ALTER TABLE `workshop`
  ADD CONSTRAINT `workshop_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
