-- FULL DATABASE BACKUP: mess_system
-- Combined from all tables
-- Generated backup for safety restore

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Database: `mess_system`
-- --------------------------------------------------------

-- --------------------------------------------------------
-- Table structure for table `admin`
-- --------------------------------------------------------

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$Z16Rtc78nee4gJYo.78WXeMiDQk3r0eBc5SICg3ClMvw5Do9G09O6');

ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------
-- Table structure for table `student`
-- --------------------------------------------------------

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

INSERT INTO `student` (`id`, `name`, `class`, `department`, `university_roll`, `hostel_roll`, `phone`, `email`, `room_number`, `address`, `password`, `photo`, `status`, `block_reason`) VALUES
(1, 'Alibha Dehury', 'UG 4th year', 'Math', '24DSM012', '2', '9437665658', 'albhadehury@gmail.com', '324', 'Chanankya nagar ,new delhi', '$2y$10$tfkP6AN9wRBnan6aGf9rRu9BGRN/EJc9lv9uYxp6Zww4wJWnWn2oC', 'uploads/img_69e114dbc81524.34384331.jpg', 'active', NULL),
(2, 'sita sahu', 'UG 2nd  year', 'math', '24DSM007', '1', '9868969977', 'sitasahu@gmail.com', '4', 'BJB nagar ,Bhubaneswar,Odisha', '$2y$10$bdqZc5OYLkYQS2t.2U2Q6.iN205VyBvIFrsUCM4As9ltWX0BwzdsS', 'uploads/img_69e37ce6f3d2b7.00223939.jpg', 'active', NULL);

ALTER TABLE `student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hostel_roll` (`hostel_roll`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_student_roll` (`hostel_roll`);

ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------
-- Table structure for table `menu`
-- --------------------------------------------------------

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
  `is_active` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `menu` VALUES
(1, 'Monday', 'Bara, Tarkari', 15, 'Rice, Dalma, Aloo choka, Ambula khata', '', 0, 33, 33, 'Puri, Matar Tarkari, Kheer', '', 0, 33, 33, 0, 0, NULL, 40, 50, 40, 50, 1),
(2, 'Tuesday', 'Upma, Tarkari', 15, 'Rice, Dal, Soyabean kasa, Tomato ambila', 'Rice, Dal, Egg Curry, Tomato ambila', 1, 33, 33, 'Roti/Rice, Dal, Aloo jeera, Achar', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1),
(3, 'Wednesday', 'Bara, Tarkari', 15, 'Jeera rice, Dal fry, Navaratna korma, Papad', 'Jeera rice, Dal fry, Fish curry, Papad', 1, 33, 33, 'Roti/Rice, Dal, Mushroom chilly', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1),
(4, 'Thursday', 'Idli, Tarkari', 15, 'Rice, Dal, Ghanta, Dahi baigan', NULL, 0, 33, 33, 'Roti/Rice, Dal, Chole, Sweet', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1),
(5, 'Friday', 'Chowmin', 15, 'Rice, Dal, Pakodi curry, Veggie fry', 'Rice, Dal, Fish curry, Veggie fry', 1, 33, 33, 'Roti/Rice, Dal, Chilly paneer', 'Roti/Rice, Dal, Chilly chicken', 1, 33, 43, 1, 0, NULL, 40, 50, 40, 50, 1),
(6, 'Saturday', 'Dahibara', 15, 'Rice, Dal, Drumstick aloo bari curry, Papad', 'Rice, Dal, Egg curry, Papad', 1, 33, 33, 'Roti/Rice, Dal, Tadka, Aloo bhaja', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1),
(7, 'Sunday', 'Sandwich', 15, 'Rice, Dal, Paneer curry, Salad', 'Rice, Dal, Chicken curry, Salad', 1, 33, 33, 'Veg Biryani, Raita', 'Egg Biryani, Raita', 1, 33, 33, 0, 0, NULL, 40, 50, 40, 50, 1);

ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `day` (`day`);

ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- --------------------------------------------------------
-- Table structure for table `meals`
-- --------------------------------------------------------

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
  `base` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `meals` VALUES
(1, '2', 'Saturday', '2026-04-17', 1, 0, NULL, 1, 'veg', 'rice');

ALTER TABLE `meals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_meal` (`hostel_roll`,`date`),
  ADD KEY `idx_roll` (`hostel_roll`),
  ADD KEY `idx_date` (`date`);

ALTER TABLE `meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `meals`
  ADD CONSTRAINT `fk_student_meal`
  FOREIGN KEY (`hostel_roll`)
  REFERENCES `student` (`hostel_roll`)
  ON DELETE CASCADE;

-- --------------------------------------------------------
-- Table structure for table `bills`
-- --------------------------------------------------------

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `hostel_roll` varchar(50) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `total_amount` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bills` VALUES
(1, '2', 4, 2026, 1320, '2026-04-16 17:35:59'),
(2, '1', 4, 2026, 1320, '2026-04-18 12:52:40');

ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bill` (`hostel_roll`,`month`,`year`);

ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1`
  FOREIGN KEY (`hostel_roll`)
  REFERENCES `student` (`hostel_roll`)
  ON DELETE CASCADE;

-- --------------------------------------------------------
-- Table structure for table `announcements`
-- --------------------------------------------------------

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `announce_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;