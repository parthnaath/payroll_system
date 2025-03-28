-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2024 at 10:07 AM
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
-- Database: `smarthr`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_next_holiday` () RETURNS DATE  BEGIN
    DECLARE next_holiday DATE;
    SELECT date INTO next_holiday FROM holidays WHERE date >= CURDATE() ORDER BY date ASC LIMIT 1;
    RETURN next_holiday;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `get_team_profile` (`emp_id` INT) RETURNS VARCHAR(10) CHARSET utf8mb4 COLLATE utf8mb4_general_ci  BEGIN
    DECLARE team VARCHAR(10);
    SELECT team_id INTO team FROM employee WHERE id = emp_id;
    RETURN team;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `time_ago` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `action`, `icon`, `time_ago`, `created_at`) VALUES
(1, 'New employee added', 'people', '2 hours ago', '2024-10-12 12:54:02'),
(2, 'Salary processed', 'currency-dollar', '5 hours ago', '2024-10-12 12:54:02'),
(3, 'Leave request approved', 'calendar-event', '1 day ago', '2024-10-12 12:54:02'),
(4, 'Performance review scheduled', 'file-text', '2 days ago', '2024-10-12 12:54:02');

-- --------------------------------------------------------

--
-- Table structure for table `add-employees`
--

CREATE TABLE `add-employees` (
  `id` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `Email` varchar(100) NOT NULL,
  `Designation` varchar(100) NOT NULL,
  `Image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add-employees`
--

INSERT INTO `add-employees` (`id`, `FirstName`, `LastName`, `Email`, `Designation`, `Image`) VALUES
(3, 'Partha', 'Nath', 'parthanath@example.com', 'Team Leader', 'WhatsApp Image 2024-02-06 at 19.18.14_32288b05.jpg'),
(4, 'Taofiq Omar', 'Raju', 'taofiqomar@example.com', 'Web Developer', 'WhatsApp Image 2024-02-06 at 19.18.14_1bf73367.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `team_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `name`, `employee_id`, `email`, `role`, `image`, `team_id`) VALUES
(1, 'Partha Nath', 'FT-0001', 'parthanath@example.com', 'Team Leader', 'assets/img/profiles/WhatsApp Image 2024-02-06 at 19.18.14_32288b05.jpg', 'TEAM001'),
(7, 'Taofiq Omar Raju', 'FT-0002', 'taofiqomar@example.com', 'Front-end Developer', 'uploads/WhatsApp Image 2024-02-06 at 19.18.14_1bf73367.jpg', 'TEAM001'),
(8, 'Rajesh das joy', 'FT-0003', 'rajeshjoy@example.com', 'Back-end Developer', 'uploads/WhatsApp Image 2024-02-06 at 19.18.14_92dd0cad.jpg', 'TEAM001'),
(9, 'MD. Sajid', 'FT-0004', 'mdsajid@example.com', 'Mobile Web Developer', 'uploads/WhatsApp Image 2024-02-06 at 19.18.15_48dfb3a1.jpg', 'TEAM002'),
(10, 'Sanjoy Sen Gupta', 'FT-0005', 'sanjoygupta@example.com', 'DevOps Engineer', 'uploads/WhatsApp Image 2024-09-27 at 13.42.54_ca4d8031.jpg', 'TEAM002');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `title`, `date`) VALUES
(1, 'Tuesday', '2024-10-03'),
(2, 'Friday', '2024-10-04'),
(3, 'Christmas', '2024-12-25'),
(4, 'New Year\'s Day', '2025-01-01'),
(5, 'Independence Day', '2024-07-04'),
(6, 'Labor Day', '2024-09-02'),
(7, 'Thanksgiving', '2024-11-28');

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(11) NOT NULL,
  `employee` varchar(255) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `days` int(11) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `employee`, `from_date`, `to_date`, `days`, `reason`, `created_at`) VALUES
(1, 'Taofiq Omar Raju', '2024-10-03', '2024-10-31', 28, 'Leave for bringing mom & dad home', '2024-10-03 05:29:37'),
(2, 'Sanjoy Sen Gupta', '2024-10-18', '2024-10-21', 3, 'Leave for severe illness', '2024-10-03 05:37:42');

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `addition` decimal(10,2) DEFAULT 0.00,
  `deduction` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salaries`
--

INSERT INTO `salaries` (`id`, `employee_id`, `pay_date`, `amount`, `addition`, `deduction`) VALUES
(1, 1, '2024-09-26', 50000.00, 0.00, 0.00),
(2, 1, '2024-09-26', 50000.00, 0.00, 0.00),
(3, 1, '2024-09-26', 50000.00, 0.00, 0.00),
(5, 7, '2024-09-13', 40000.00, 200.00, 100.00),
(6, 8, '2024-09-14', 45000.00, 100.00, 70.00),
(7, 9, '2024-09-15', 55000.00, 500.00, 120.00),
(8, 10, '2024-09-17', 60000.00, 700.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `security`
--

CREATE TABLE `security` (
  `username` varchar(20) NOT NULL,
  `id` int(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(200) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `fullName` varchar(100) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `joinDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security`
--

INSERT INTO `security` (`username`, `id`, `Email`, `Password`, `image`, `fullName`, `department`, `joinDate`) VALUES
('Partha', 1, 'partha@example.com', '$2y$10$voLokervLvlGxr8X8ypMqucQcpeQYk/v/z6uHMAF8lUTinbsr1bS.', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `team_profiles`
--

CREATE TABLE `team_profiles` (
  `id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `reportsTo` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `joinDate` date NOT NULL,
  `location` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `members` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_profiles`
--

INSERT INTO `team_profiles` (`id`, `name`, `role`, `email`, `reportsTo`, `department`, `joinDate`, `location`, `phone`, `members`, `image`) VALUES
('TEAM001', 'Development Team', 'Software Development', 'dev.team@example.com', 'Jane Smith', 'Engineering', '2023-01-01', 'New York, NY', '+1234567890', 5, 'assets/img/teams/dev_team.jpg'),
('TEAM002', 'Design Team', 'UI/UX Design', 'design.team@example.com', 'John Doe', 'Design', '2023-02-01', 'San Francisco, CA', '+1987654321', 3, 'assets/img/teams/design_team.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `add-employees`
--
ALTER TABLE `add-employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `security`
--
ALTER TABLE `security`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `team_profiles`
--
ALTER TABLE `team_profiles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `add-employees`
--
ALTER TABLE `add-employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `salaries`
--
ALTER TABLE `salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `security`
--
ALTER TABLE `security`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `salaries`
--
ALTER TABLE `salaries`
  ADD CONSTRAINT `salaries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
