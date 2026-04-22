-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2026 at 01:12 PM
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
-- Database: `mess_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$Z16Rtc78nee4gJYo.78WXeMiDQk3r0eBc5SICg3ClMvw5Do9G09O6');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `announce_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('normal','urgent') DEFAULT 'normal',
  `expiry_date` date DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `show_popup` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `announce_date`, `created_at`, `priority`, `expiry_date`, `attachment`, `show_popup`) VALUES
(4, 'Summer vacation', 'Due to heat wave when university give notice for summer vacation our hostel mess also remain close from 1st may. please cooperate...', '2026-04-22', '2026-04-21 18:59:30', 'urgent', '2026-05-01', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `hostel_roll` varchar(50) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `total_amount` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `hostel_roll`, `month`, `year`, `total_amount`, `generated_at`) VALUES
(1, '2', 4, 2026, 1320, '2026-04-16 17:35:59'),
(2, '1', 4, 2026, 1320, '2026-04-18 12:52:40'),
(3, '3', 4, 2026, 1320, '2026-04-21 19:51:51'),
(4, '4', 4, 2026, 1320, '2026-04-21 19:51:51');

-- --------------------------------------------------------

--
-- Table structure for table `meals`
--

CREATE TABLE `meals` (
  `id` int(11) NOT NULL,
  `hostel_roll` varchar(50) DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `breakfast` tinyint(1) DEFAULT 0,
  `lunch` tinyint(1) DEFAULT 0,
  `lunch_type` varchar(10) DEFAULT NULL,
  `dinner` tinyint(1) DEFAULT 0,
  `dinner_type` varchar(10) DEFAULT NULL,
  `base` varchar(10) DEFAULT NULL,
  `is_special` tinyint(1) DEFAULT 0,
  `locked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meals`
--

INSERT INTO `meals` (`id`, `hostel_roll`, `day`, `date`, `breakfast`, `lunch`, `lunch_type`, `dinner`, `dinner_type`, `base`, `is_special`, `locked`) VALUES
(1, '2', 'Saturday', '2026-04-17', 1, 0, NULL, 1, 'veg', 'rice', 0, 0),
(2, '1', 'Wednesday', '2026-04-22', 1, 1, 'veg', 1, 'veg', '0', 0, 0),
(3, '2', 'Wednesday', '2026-04-22', 1, 1, 'nonveg', 1, 'veg', 'rice', 0, 0),
(4, '3', 'Wednesday', '2026-04-22', 1, 1, 'veg', 1, 'veg', 'rice', 0, 0),
(5, '4', 'Thursday', '2026-04-23', 1, 1, 'veg', 1, 'nonveg', 'roti', 0, 0),
(6, '3', 'Thursday', '2026-04-23', 1, 1, 'veg', 1, 'nonveg', 'roti', 0, 0),
(7, '6', 'Thursday', '2026-04-23', 1, 1, 'veg', 1, 'nonveg', 'roti', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `day` varchar(20) DEFAULT NULL,
  `breakfast` varchar(255) DEFAULT NULL,
  `breakfast_price` int(11) DEFAULT 15,
  `lunch_veg` varchar(255) DEFAULT NULL,
  `lunch_nonveg` varchar(255) DEFAULT NULL,
  `has_lunch_nonveg` tinyint(1) DEFAULT 0,
  `lunch_veg_price` int(11) DEFAULT 33,
  `lunch_nonveg_price` int(11) DEFAULT 33,
  `dinner_veg` varchar(255) DEFAULT NULL,
  `dinner_nonveg` varchar(255) DEFAULT NULL,
  `has_dinner_nonveg` tinyint(1) DEFAULT 0,
  `dinner_veg_price` int(11) DEFAULT 33,
  `dinner_nonveg_price` int(11) DEFAULT 33,
  `has_base_option` tinyint(1) DEFAULT 0,
  `is_special` tinyint(1) DEFAULT 0,
  `special_date` date DEFAULT NULL,
  `special_lunch_veg_price` int(11) DEFAULT 40,
  `special_lunch_nonveg_price` int(11) DEFAULT 50,
  `special_dinner_veg_price` int(11) DEFAULT 40,
  `special_dinner_nonveg_price` int(11) DEFAULT 50,
  `is_active` tinyint(4) DEFAULT 1,
  `has_special_lunch` tinyint(1) DEFAULT 0,
  `has_special_dinner` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `day`, `breakfast`, `breakfast_price`, `lunch_veg`, `lunch_nonveg`, `has_lunch_nonveg`, `lunch_veg_price`, `lunch_nonveg_price`, `dinner_veg`, `dinner_nonveg`, `has_dinner_nonveg`, `dinner_veg_price`, `dinner_nonveg_price`, `has_base_option`, `is_special`, `special_date`, `special_lunch_veg_price`, `special_lunch_nonveg_price`, `special_dinner_veg_price`, `special_dinner_nonveg_price`, `is_active`, `has_special_lunch`, `has_special_dinner`) VALUES
(1, 'Monday', 'Bara, Tarkari', 15, 'Rice, Dalma, Aloo choka, Ambula khata', '', 0, 33, 33, 'Puri, Matar Tarkari, Kheer', '', 0, 33, 33, 0, 0, NULL, 40, 50, 40, 50, 1, 0, 0),
(2, 'Tuesday', 'Upma, Tarkari', 15, 'Rice, Dal, Soyabean kasa, Tomato ambila', 'Rice, Dal, Egg Curry, Tomato ambila', 1, 33, 33, 'Roti/Rice, Dal, Aloo jeera, Achar', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0),
(3, 'Wednesday', 'Bara, Tarkari', 15, 'Jeera rice, Dal fry, Navaratna korma, Papad', 'Jeera rice, Dal fry, Fish curry, Papad', 1, 33, 33, 'Roti/Rice, Dal, Mushroom chilly', 'Roti,Mutton', 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0),
(4, 'Thursday', 'Idli, Tarkari', 15, 'Rice, Dal, Ghanta, Dahi baigan', NULL, 0, 33, 33, 'Roti/Rice, Dal, Chole, Sweet', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0),
(5, 'Friday', 'Chowmin', 15, 'Rice, Dal, Pakodi curry, Veggie fry', 'Rice, Dal, Fish curry, Veggie fry', 1, 33, 33, 'Roti/Rice, Dal, Chilly paneer', 'Roti/Rice, Dal, Chilly chicken', 1, 33, 43, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0),
(6, 'Saturday', 'Dahibara', 15, 'Rice, Dal, Drumstick aloo bari curry, Papad', 'Rice, Dal, Egg curry, Papad', 1, 33, 33, 'Roti/Rice, Dal, Tadka, Aloo bhaja', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0),
(7, 'Sunday', 'Sandwich', 15, 'Rice, Dal, Paneer curry, Salad', 'Rice, Dal, Chicken curry, Salad', 1, 33, 33, 'Veg Biryani, Raita', 'Egg Biryani, Raita', 1, 33, 33, 0, 0, NULL, 40, 50, 40, 50, 1, 0, 0),
(9, 'Thursday', NULL, 15, '', NULL, 0, 33, 33, 'Roti/Rice, Dal, Mushroom chilly', 'Roti/Rice ,dal ,Mutton', 1, 33, 33, 1, 1, '2026-04-23', 0, 0, 43, 53, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `class` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `university_roll` varchar(50) DEFAULT NULL,
  `hostel_roll` varchar(50) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `block_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `name`, `class`, `department`, `university_roll`, `hostel_roll`, `phone`, `email`, `room_number`, `address`, `password`, `photo`, `status`, `block_reason`) VALUES
(1, 'Alibha Dehury', 'UG 4th year', 'Math', '24DSM012', '2', '9437665658', 'albhadehury@gmail.com', '324', 'Chanankya nagar ,new delhi', '$2y$10$tfkP6AN9wRBnan6aGf9rRu9BGRN/EJc9lv9uYxp6Zww4wJWnWn2oC', 'uploads/img_69e114dbc81524.34384331.jpg', 'active', NULL),
(2, 'sita sahu', 'UG 2nd  year', 'math', '24DSM007', '1', '9868969977', 'sitasahu@gmail.com', '4', 'BJB nagar ,Bhubaneswar,Odisha', '$2y$10$bdqZc5OYLkYQS2t.2U2Q6.iN205VyBvIFrsUCM4As9ltWX0BwzdsS', 'uploads/img_69e37ce6f3d2b7.00223939.jpg', 'active', NULL),
(3, 'Monalisa Dehury', 'UG 1st year', 'Economics', '25DEC001', '3', '9040585533', 'dehurymonalisa345@gmail.com', '320', 'Chanankya nagar ,new delhi', '$2y$10$NL3BAJM.CYg8qvgXvSsXNOQMFKXjd2py8uIW0atDXNcHt56j04R3.', 'uploads/student_1776776306.jpg', 'active', NULL),
(4, 'Ratna Manjari Tripathy', 'UG 2nd year', 'Chemistry', '24DCH022', '4', '9178524897', 'ratanamanjaritripathy@gmail.com', '324', 'OUAT campus,Bhubaneswar', '$2y$10$aN1tmHFDC01nKTAmUg96aukxbglGD4ybdbfil2UG7YiScCrhiMtQ.', 'uploads/student_1776792964.jpg', 'active', NULL),
(5, 'Salini Amatya', 'UG 2nd year', 'Chemistry', '24DCH023', '5', '9178524896', 'saliniamatya@gmail.com', '328', 'Patia,Bhubaneswar', '$2y$10$PtvgT5hFV5Tspbmi5B0qleFDTOlf4RbBKYlOM0BeIB2cR3zAWngJe', 'uploads/student_1776830746.jpg', 'active', NULL),
(6, 'Lipi Jhankar', 'UG 3rd year', 'Physics', '24DPH007', '6', '9874563210', 'lipijhankar@gmaail.com', '24', 'Burla,Sambalpur', '$2y$10$XNkfCdtz1zmClRYxZBtB1uA87dHSmglT9VPH8o5trkuha6GIAkddK', 'uploads/img_69e894fbdb1ed0.94316460.jpg', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bill` (`hostel_roll`,`month`,`year`),
  ADD KEY `idx_bill_month` (`month`,`year`);

--
-- Indexes for table `meals`
--
ALTER TABLE `meals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_meal` (`hostel_roll`,`date`),
  ADD KEY `idx_roll` (`hostel_roll`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_meal_date` (`date`),
  ADD KEY `idx_meal_roll_date` (`hostel_roll`,`date`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_special_date` (`special_date`),
  ADD KEY `idx_day` (`day`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hostel_roll` (`hostel_roll`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_student_roll` (`hostel_roll`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `meals`
--
ALTER TABLE `meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`hostel_roll`) REFERENCES `student` (`hostel_roll`) ON DELETE CASCADE;

--
-- Constraints for table `meals`
--
ALTER TABLE `meals`
  ADD CONSTRAINT `fk_student_meal` FOREIGN KEY (`hostel_roll`) REFERENCES `student` (`hostel_roll`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

