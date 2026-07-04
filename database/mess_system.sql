-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 04, 2026 at 03:55 PM
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
(4, 'Summer vacation', 'Due to heat wave when university give notice for summer vacation our hostel mess also remain close from 10th may. please cooperate...', '2026-05-02', '2026-04-21 18:59:30', 'urgent', '2026-05-07', '1777743522_24DCS042 readmission Sonali.pdf', 0),
(6, 'Mess bill', 'kindly pays your mess bill on time', '2026-07-03', '2026-07-03 11:40:00', 'urgent', '2026-07-10', '1783078800_scenery.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `hostel_roll` varchar(50) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `hostel_roll`, `month`, `year`, `total_amount`, `generated_at`, `status`, `paid_at`) VALUES
(1, '2', 4, 2026, 1320.00, '2026-04-16 17:35:59', 'paid', '2026-07-03 21:07:08'),
(2, '1', 4, 2026, 1320.00, '2026-04-18 12:52:40', 'pending', NULL),
(3, '3', 4, 2026, 1320.00, '2026-04-21 19:51:51', 'paid', '2026-05-02 21:38:34'),
(4, '4', 4, 2026, 1320.00, '2026-04-21 19:51:51', 'pending', NULL),
(5, '1', 5, 2026, 1320.00, '2026-07-03 15:44:56', 'pending', NULL),
(6, '1', 7, 2026, 1320.00, '2026-07-03 17:22:55', 'pending', NULL);

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
  `lunch_type` varchar(10) NOT NULL DEFAULT 'none',
  `dinner` tinyint(1) DEFAULT 0,
  `dinner_type` varchar(10) NOT NULL DEFAULT 'none',
  `base` varchar(10) NOT NULL DEFAULT 'none',
  `is_special` tinyint(1) DEFAULT 0,
  `locked` tinyint(1) DEFAULT 0,
  `breakfast_served` tinyint(1) DEFAULT 0,
  `lunch_served` tinyint(1) DEFAULT 0,
  `dinner_served` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meals`
--

INSERT INTO `meals` (`id`, `hostel_roll`, `day`, `date`, `breakfast`, `lunch`, `lunch_type`, `dinner`, `dinner_type`, `base`, `is_special`, `locked`, `breakfast_served`, `lunch_served`, `dinner_served`) VALUES
(1, '2', 'Saturday', '2026-04-17', 1, 0, 'none', 1, 'veg', 'rice', 0, 0, 0, 0, 0),
(2, '1', 'Wednesday', '2026-04-22', 1, 1, 'veg', 1, 'veg', 'none', 0, 0, 0, 0, 0),
(3, '2', 'Wednesday', '2026-04-22', 1, 1, 'nonveg', 1, 'veg', 'rice', 0, 0, 0, 0, 0),
(4, '3', 'Wednesday', '2026-04-22', 1, 1, 'veg', 1, 'veg', 'rice', 0, 0, 0, 0, 0),
(5, '4', 'Thursday', '2026-04-23', 1, 1, 'veg', 1, 'nonveg', 'roti', 0, 0, 0, 0, 0),
(6, '3', 'Thursday', '2026-04-23', 1, 1, 'veg', 1, 'nonveg', 'roti', 0, 0, 0, 0, 0),
(7, '6', 'Thursday', '2026-04-23', 1, 1, 'veg', 1, 'veg', 'rice', 0, 0, 0, 0, 0),
(8, '5', 'Sunday', '2026-05-03', 1, 1, 'veg', 1, 'veg', '', 0, 0, 0, 0, 0),
(9, '1', 'Sunday', '2026-05-03', 1, 1, 'veg', 1, 'veg', '', 0, 0, 0, 0, 0),
(10, '562', 'Sunday', '2026-05-03', 1, 1, 'veg', 1, 'nonveg', '', 0, 0, 0, 0, 0),
(11, '562', 'Thursday', '2026-05-07', 1, 1, 'veg', 1, 'veg', 'rice', 0, 0, 0, 0, 0),
(12, '562', 'Wednesday', '2026-06-03', 1, 1, 'nonveg', 1, 'veg', 'roti', 0, 0, 0, 0, 0),
(13, '562', 'Thursday', '2026-07-02', 1, 1, 'veg', 1, 'veg', 'rice', 0, 0, 0, 0, 0),
(14, '562', 'Saturday', '2026-07-04', 1, 1, 'veg', 1, 'veg', 'roti', 0, 0, 0, 0, 0),
(15, '11', 'Saturday', '2026-07-04', 1, 1, 'veg', 1, 'veg', 'roti', 0, 1, 0, 0, 0),
(16, '562', 'Sunday', '2026-07-05', 0, 1, 'veg', 1, 'nonveg', 'none', 0, 0, 0, 0, 0),
(17, '10', 'Sunday', '2026-07-05', 1, 1, 'veg', 1, 'veg', 'none', 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `meal_reviews`
--

CREATE TABLE `meal_reviews` (
  `id` int(11) NOT NULL,
  `hostel_roll` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `meal_type` enum('breakfast','lunch','dinner') NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_reviews`
--

INSERT INTO `meal_reviews` (`id`, `hostel_roll`, `date`, `meal_type`, `rating`, `comment`, `submitted_at`) VALUES
(1, '11', '2026-07-04', 'dinner', 5, 'fantastic', '2026-07-04 10:17:49'),
(3, '11', '2026-07-04', 'breakfast', 1, '', '2026-07-04 10:18:52');

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
  `has_special_dinner` tinyint(1) DEFAULT 0,
  `has_lunch` tinyint(1) DEFAULT 0,
  `has_dinner` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `day`, `breakfast`, `breakfast_price`, `lunch_veg`, `lunch_nonveg`, `has_lunch_nonveg`, `lunch_veg_price`, `lunch_nonveg_price`, `dinner_veg`, `dinner_nonveg`, `has_dinner_nonveg`, `dinner_veg_price`, `dinner_nonveg_price`, `has_base_option`, `is_special`, `special_date`, `special_lunch_veg_price`, `special_lunch_nonveg_price`, `special_dinner_veg_price`, `special_dinner_nonveg_price`, `is_active`, `has_special_lunch`, `has_special_dinner`, `has_lunch`, `has_dinner`) VALUES
(1, 'Monday', 'Bara, Tarkari', 15, 'Rice, Dalma, Aloo choka, Ambula khata', '', 0, 33, 33, 'Puri, Matar Tarkari, Kheer', '', 0, 33, 33, 0, 0, NULL, 40, 50, 40, 50, 1, 0, 0, 0, 0),
(2, 'Tuesday', 'Upma, Tarkari', 15, 'Rice, Dal, Soyabean kasa, Tomato ambila', 'Rice, Dal, Egg Curry, Tomato ambila', 1, 33, 33, 'Roti/Rice, Dal, Aloo jeera, Achar', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0, 0, 0),
(3, 'Wednesday', 'Bara, Tarkari', 15, 'Jeera rice, Dal fry, Navaratna korma, Papad', 'Jeera rice, Dal fry, Fish curry, Papad', 1, 33, 33, 'Roti/Rice, Dal, Mushroom chilly', '', 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0, 0, 0),
(4, 'Thursday', 'Idli, Tarkari', 15, 'Rice, Dal, Ghanta, Dahi baigan', NULL, 0, 33, 33, 'Roti/Rice, Dal, Chole, Sweet', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0, 0, 0),
(5, 'Friday', 'Chowmin', 15, 'Rice, Dal, Pakodi curry, Veggie fry', 'Rice, Dal, Fish curry, Veggie fry', 1, 33, 33, 'Roti/Rice, Dal, Chilly paneer', 'Roti/Rice, Dal, Chilly chicken', 1, 33, 43, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0, 0, 0),
(6, 'Saturday', 'Dahibara', 15, 'Rice, Dal, Drumstick aloo bari curry, Papad', 'Rice, Dal, Egg curry, Papad', 1, 33, 33, 'Roti/Rice, Dal, Tadka, Aloo bhaja', NULL, 0, 33, 33, 1, 0, NULL, 40, 50, 40, 50, 1, 0, 0, 0, 0),
(7, 'Sunday', 'Sandwich', 15, 'Rice, Dal, Paneer curry, Salad', 'Rice, Dal, Chicken curry, Salad', 1, 33, 33, 'Veg Biryani, Raita', 'Egg Biryani, Raita', 1, 33, 33, 0, 0, NULL, 40, 50, 40, 50, 1, 0, 0, 0, 0),
(9, 'Thursday', NULL, 15, '', NULL, 0, 33, 33, 'Roti/Rice, Dal, Mushroom chilly', 'Roti/Rice ,dal ,Mutton', 1, 33, 33, 1, 1, '2026-04-23', 0, 0, 43, 53, 0, 0, 0, 0, 0),
(10, 'Monday', NULL, 15, 'paneer biriyani,raita,chilli mushroom', 'chicken biriyani,raita,chilli chicken', 1, 33, 33, 'Naan,gobi manchriyaan,paneer gravy', 'Naan,chicken manchuriyan,chicken 65', 1, 33, 33, 0, 1, '2026-05-04', 50, 60, 45, 55, 0, 0, 0, 1, 1),
(11, 'Friday', NULL, 15, '', '', 0, 33, 33, 'veg biriyani,chili paneer,sweet', 'dum biriyani,chili chicken,sweet', 1, 33, 33, 1, 1, '2026-07-10', 0, 0, 40, 50, 1, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `mess_leaves`
--

CREATE TABLE `mess_leaves` (
  `id` int(11) NOT NULL,
  `hostel_roll` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mess_leaves`
--

INSERT INTO `mess_leaves` (`id`, `hostel_roll`, `start_date`, `end_date`, `reason`, `status`, `created_at`) VALUES
(1, '562', '2026-07-04', '2026-07-05', 'to meet my family', 'approved', '2026-07-04 10:01:16'),
(2, '11', '2026-07-06', '2026-07-09', 'for semester holiday', 'rejected', '2026-07-04 10:08:50'),
(3, '11', '2026-07-06', '2026-07-09', 'for semester holiday', 'pending', '2026-07-04 10:09:19');

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
  `block_reason` text DEFAULT NULL,
  `reset_otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `name`, `class`, `department`, `university_roll`, `hostel_roll`, `phone`, `email`, `room_number`, `address`, `password`, `photo`, `status`, `block_reason`, `reset_otp`, `otp_expiry`) VALUES
(1, 'Alibha Dehury', 'UG 4th year', 'Math', '24DSM012', '2', '9437665658', 'albhadehury@gmail.com', '324', 'Chanankya nagar ,new delhi', '$2y$10$tfkP6AN9wRBnan6aGf9rRu9BGRN/EJc9lv9uYxp6Zww4wJWnWn2oC', 'uploads/img_69e114dbc81524.34384331.jpg', 'active', NULL, NULL, NULL),
(2, 'sita sahu', 'UG 2nd  year', 'math', '24DSM007', '1', '9868969977', 'sitasahu@gmail.com', '4', 'BJB nagar ,Bhubaneswar,Odisha', '$2y$10$bdqZc5OYLkYQS2t.2U2Q6.iN205VyBvIFrsUCM4As9ltWX0BwzdsS', 'uploads/img_69e37ce6f3d2b7.00223939.jpg', 'active', NULL, NULL, NULL),
(3, 'Monalisa Dehury', 'UG 1st year', 'Economics', '25DEC001', '3', '9040585533', 'dehurymonalisa345@gmail.com', '320', 'Chanankya nagar ,new delhi', '$2y$10$NL3BAJM.CYg8qvgXvSsXNOQMFKXjd2py8uIW0atDXNcHt56j04R3.', 'uploads/student_1776776306.jpg', 'active', NULL, NULL, NULL),
(4, 'Ratna Manjari Tripathy', 'UG 2nd year', 'Chemistry', '24DCH022', '4', '9178524897', 'ratanamanjaritripathy@gmail.com', '324', 'OUAT campus,Bhubaneswar', '$2y$10$aN1tmHFDC01nKTAmUg96aukxbglGD4ybdbfil2UG7YiScCrhiMtQ.', 'uploads/student_1776792964.jpg', 'active', NULL, NULL, NULL),
(5, 'Salini Amatya', 'UG 2nd year', 'Chemistry', '24DCH023', '5', '9178524896', 'saliniamatya@gmail.com', '328', 'Patia,Bhubaneswar', '$2y$10$PtvgT5hFV5Tspbmi5B0qleFDTOlf4RbBKYlOM0BeIB2cR3zAWngJe', 'uploads/student_1776830746.jpg', 'active', NULL, NULL, NULL),
(6, 'Lipi Jhankar', 'UG 3rd year', 'Physics', '24DPH007', '6', '9874563210', 'lipijhankar@gmaail.com', '24', 'Burla,Sambalpur', '$2y$10$XNkfCdtz1zmClRYxZBtB1uA87dHSmglT9VPH8o5trkuha6GIAkddK', 'uploads/img_69e894fbdb1ed0.94316460.jpg', 'active', NULL, NULL, NULL),
(7, 'Sonali Dehury', 'UG 2nd year', 'Computer Science', '24DCS042', '562', '9178422033', 'sonalidehury77@gmail.com', '324', 'Timur,Reamal,Deogarh', '$2y$10$z5CovmYHMYcolKE4gAuBtuOqHNMTE34hD4M4b2jGT7Jr88yhC6MXq', 'uploads/student_1776856827.jpeg', 'active', NULL, NULL, NULL),
(8, 'Saswoti Sahoo', 'UG 1st year', 'Physics', '25DPH045', '7', '9854765567', 'saswotisahoo@gmail.com', '8', 'Bhubaneswar,Odisha', '$2y$10$XlfNMpGV8MPLoJZbdF6D7.8JCyXJ6ILgIFM1/QdKJNd/FuP7XLdT.', 'uploads/student_1777735354.jpg', 'active', NULL, NULL, NULL),
(9, 'Maithili Bhoi', 'UG 3rd year', 'EDUCATION', '24DED026', '10', '9178422033', 'maithilibhaoi@gmail.com', '15', 'Jagarnath Road,deogarh', '$argon2id$v=19$m=65536,t=4,p=1$amRDYjVhQTRmSXpZY3p6MA$/gCv11FeXXNhM/rp2qD+YM/1wMtOCuMg7Qtd60VQbkA', 'uploads/students/da412ff013252e85fcdc67e4af9aa6aa.jpg', 'active', NULL, NULL, NULL),
(10, 'Hiteswari Sahu', 'UG 3rd year', 'ZOOLOGY', '24DZO001', '11', '9178422033', 'hiteswarisahu@gmail.com', '24', 'Jagarnath Road,deogarh', '$argon2id$v=19$m=65536,t=4,p=1$UFVXQjRaYllaVHpOdkYyRA$8aULL7xAWUV1QtxBS/y35qQN3slFx7bXGBobEa5kK7s', 'uploads/students/a5763e8037c599147479cac34d008157.jpg', 'active', NULL, NULL, NULL),
(11, 'Swayam Prangya Das', 'UG 1st year', 'ECONOMICS', '24DEC009', '200', '9517538524', 'swayamdas@gmail.com', '18', 'bargarh', '$argon2id$v=19$m=65536,t=4,p=1$UDJibGlMV2RoLllXaHhSTA$vyLSso53i/VoGJSkNjc3W5jbMvIF4XCejJQQyCi1+bQ', 'uploads/students/7b84980199322f6495a4c15b01a03023.jpg', 'active', NULL, NULL, NULL);

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
-- Indexes for table `meal_reviews`
--
ALTER TABLE `meal_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_meal_rating` (`hostel_roll`,`date`,`meal_type`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_special_date` (`special_date`),
  ADD KEY `idx_day` (`day`);

--
-- Indexes for table `mess_leaves`
--
ALTER TABLE `mess_leaves`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `meals`
--
ALTER TABLE `meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `meal_reviews`
--
ALTER TABLE `meal_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `mess_leaves`
--
ALTER TABLE `mess_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
