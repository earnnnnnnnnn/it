-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2026 at 06:19 AM
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
-- Database: `it_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL,
  `serial_id` int(11) NOT NULL,
  `borrower_id` int(11) NOT NULL,
  `asset_number` varchar(100) DEFAULT NULL,
  `building` varchar(255) DEFAULT NULL,
  `floor` varchar(100) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `approver_name` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `borrowed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `returned_at` timestamp NULL DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `serial_id`, `borrower_id`, `asset_number`, `building`, `floor`, `department`, `approver_name`, `reason`, `notes`, `borrowed_at`, `returned_at`, `image`) VALUES
(70, 79, 11, '7440-001-0001-62-0008', 'อาคารชิญวัทนานนท์ (114 เตียง)', 'ชั้น 3', '075 - สูติสามัญ', 'กชกร เมืองโพธิ์', 'ของเก่าชำรุด', NULL, '2026-05-22 08:58:08', NULL, 'borrows/borrow_1779440288_2983.jpeg'),
(71, 78, 11, '7440-001-0001-62-0008', 'อาคารชิญวัทนานนท์ (114 เตียง)', 'ชั้น 3', '075 - สูติสามัญ', 'กชกร เมืองโพธิ์', 'ของเก่าชำรุด', NULL, '2026-05-22 08:58:08', NULL, 'borrows/borrow_1779440288_2983.jpeg'),
(72, 80, 12, 'ไม่มีเลขครุภัณฑ์', 'อาคารสำราญสำรวจกิจ (75 ปี)', 'ชั้น 5', '061 - อายุรกรรมชาย 1', 'กชกร เมืองโพธิ์', 'ของเก่าชำรุด', NULL, '2026-05-28 03:05:55', NULL, 'borrows/borrow_1779937555_9846.jpeg'),
(73, 81, 25, '7440-001-0001-64-0062', 'ตึกเจ้าพระยาอภัยภูเบศร', 'ชั้น 1', '188 - ศูนย์การเรียนรู้ด้านการแพทย์แผนไทย (ร้านยาไทยโพธิ์เงิน)', 'กชกร เมืองโพธิ์', 'ติดตั้งคอมใหม่', 'ห้องมงคล', '2026-06-02 07:42:08', NULL, NULL),
(74, 82, 25, '7440-001-0001-64-0062', 'ตึกเจ้าพระยาอภัยภูเบศร', 'ชั้น 1', '188 - ศูนย์การเรียนรู้ด้านการแพทย์แผนไทย (ร้านยาไทยโพธิ์เงิน)', 'กชกร เมืองโพธิ์', 'ติดตั้งคอมใหม่', 'ห้องมงคล', '2026-06-02 07:42:08', NULL, NULL),
(75, 83, 22, '7440-001-0001-62-0015', 'ตึกเจ้าพระยาอภัยภูเบศร', 'ชั้น 1', '055 - OPD ชั้น 1', 'กชกร เมืองโพธิ์', 'ของเก่าชำรุด', 'เคาร์เตอร์ห้องบัตร(ห้องโถงใหญ่)', '2026-06-04 02:35:23', NULL, 'borrows/borrow_1780540523_4825.jpeg'),
(76, 99, 23, '7440-001-0001-66-0077', 'อาคารอาชีวเวชกรรม', 'ชั้น 2', '186 - เทคโนโลยีสารสนเทศ', 'กชกร เมืองโพธิ์', 'ติดตั้งคอมใหม่', '', '2026-06-05 01:37:06', NULL, NULL),
(77, 100, 24, '7440-001-0001-58-0042', 'อาคารเฉลิมพระเกียรติฯ', 'ชั้น 5', '007 - พ.ร.ส.', 'กชกร เมืองโพธิ์', 'ของเก่าชำรุด', '', '2026-06-05 02:13:42', NULL, 'borrows/borrow_1780625622_8395.jpeg'),
(78, 97, 25, '7440-001-0001-64-0062', 'ตึกเจ้าพระยาอภัยภูเบศร', 'ชั้น 1', '188 - ศูนย์การเรียนรู้ด้านการแพทย์แผนไทย (ร้านยาไทยโพธิ์เงิน)', 'กชกร เมืองโพธิ์', 'ติดตั้งคอมใหม่', 'ห้องมงคล', '2026-06-05 03:39:30', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `name`, `sort_order`, `created_at`) VALUES
(1, 'ตึกเจ้าพระยาอภัยภูเบศร', 1, '2026-05-18 08:10:15'),
(2, 'อาคาร 58 ปี', 2, '2026-05-18 08:10:15'),
(3, 'อาคารคลังน้ำเกลือ', 3, '2026-05-18 08:10:15'),
(4, 'อาคารคลังพัสดุ', 4, '2026-05-18 08:10:15'),
(5, 'อาคารจิตตารมย์', 5, '2026-05-18 08:10:15'),
(6, 'อาคารชวนโปรยทิพย์', 6, '2026-05-18 08:10:15'),
(7, 'อาคารชิญวัทนานนท์ (114 เตียง)', 7, '2026-05-18 08:10:15'),
(8, 'อาคารชีวรักษ์', 8, '2026-05-18 08:10:15'),
(9, 'อาคารซักฟอก', 9, '2026-05-18 08:10:15'),
(10, 'อาคารธรรมรักษ์', 10, '2026-05-18 08:10:15'),
(11, 'อาคารนิติเวช', 11, '2026-05-18 08:10:15'),
(12, 'อาคารปิติพร', 12, '2026-05-18 08:10:15'),
(13, 'อาคารศูนย์ผ่าตัดวันเดียวกลับ', 13, '2026-05-18 08:10:15'),
(14, 'อาคารศูนย์หลักฐานเชิงประจักษ์', 14, '2026-05-18 08:10:15'),
(15, 'อาคารศูนย์แพทยศาสตร์ศึกษาชั้นคลินิก', 15, '2026-05-18 08:10:15'),
(16, 'อาคารสำราญสำรวจกิจ (75 ปี)', 16, '2026-05-18 08:10:15'),
(17, 'อาคารสุวัทนา', 17, '2026-05-18 08:10:15'),
(18, 'อาคารสูตินรีเวช (เก่า)', 18, '2026-05-18 08:10:15'),
(19, 'อาคารอภัยภูเบศรเดย์สปา', 19, '2026-05-18 08:10:15'),
(20, 'อาคารอาชีวเวชกรรม', 20, '2026-05-18 08:10:15'),
(21, 'อาคารอุบัติเหตุและฉุกเฉิน', 21, '2026-05-18 08:10:15'),
(22, 'อาคารเครื่องมือแพทย์', 22, '2026-05-18 08:10:15'),
(23, 'อาคารเฉลิมพระเกียรติฯ', 23, '2026-05-18 08:10:15'),
(24, 'อาคารเพชรรัตน์', 24, '2026-05-18 08:10:15'),
(25, 'อาคารโรงครัว', 25, '2026-05-18 08:10:15'),
(26, 'อาคารไฟฟ้า', 26, '2026-05-18 08:10:15');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `sort_order`) VALUES
(1, 'IT Gadget', 1),
(2, 'Office Supplies', 2),
(3, 'Network Equipment', 3),
(4, 'IT', 4),
(5, 'Office', 5),
(6, 'Network', 6);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `sort_order`, `created_at`) VALUES
(1, '001 - บริหาร', 162, '2026-05-18 08:13:05'),
(2, '002 - สนาม', 163, '2026-05-18 08:13:05'),
(3, '003 - งานยานพาหนะ', 164, '2026-05-18 08:13:05'),
(4, '004 - ยามรักษาการ', 165, '2026-05-18 08:13:05'),
(5, '005 - นิติเวช', 166, '2026-05-18 08:13:05'),
(6, '006 - ประชาสัมพันธ์', 167, '2026-05-18 08:13:05'),
(7, '007 - พ.ร.ส.', 168, '2026-05-18 08:13:05'),
(8, '008 - เวชนิทัศน์', 169, '2026-05-18 08:13:05'),
(9, '009 - เวชระเบียน และ ข้อมูลทางการแพทย์', 170, '2026-05-18 08:13:05'),
(10, '010 - ศูนย์พัฒนาคุณภาพ', 171, '2026-05-18 08:13:05'),
(11, '014 - สำนักงานการเงิน', 172, '2026-05-18 08:13:05'),
(12, '015 - งานประกันสุขภาพ', 173, '2026-05-18 08:13:05'),
(13, '016 - สังคมสงเคราะห์', 174, '2026-05-18 08:13:05'),
(14, '017 - จ่ายกลางเวชภัณฑ์ปราศจากเชื้อ', 175, '2026-05-18 08:13:05'),
(15, '018 - การพยาบาล', 176, '2026-05-18 08:13:05'),
(16, '019 - ซักฟอก', 177, '2026-05-18 08:13:05'),
(17, '021 - ศูนย์เปล', 178, '2026-05-18 08:13:05'),
(18, '022 - โภชนาการ', 179, '2026-05-18 08:13:05'),
(19, '023 - ฝ่ายพัสดุและบำรุงรักษา', 180, '2026-05-18 08:13:05'),
(20, '024 - โรงซ่อมครุภัณฑ์', 181, '2026-05-18 08:13:05'),
(21, '025 - ไฟฟ้า(งานซ่อมบำรุง)', 182, '2026-05-18 08:13:05'),
(22, '026 - ประปา', 183, '2026-05-18 08:13:05'),
(23, '027 - บำบัดน้ำเสีย', 184, '2026-05-18 08:13:05'),
(24, '028 - จัดซื้อและบำรุงรักษาเครื่องมือแพทย์', 185, '2026-05-18 08:13:05'),
(25, '029 - คลังยา', 186, '2026-05-18 08:13:05'),
(26, '030 - น้ำเกลือ(อาคารเภสัช)', 187, '2026-05-18 08:13:05'),
(27, '178 - OPD LAB', 188, '2026-05-18 08:13:05'),
(28, '032 - ธนาคารเลือด', 189, '2026-05-18 08:13:05'),
(29, '033 - เครื่องมือพิเศษ', 190, '2026-05-18 08:13:05'),
(30, '034 - ไตเทียม', 191, '2026-05-18 08:13:05'),
(31, '035 - พยาธิกายวิภาค', 192, '2026-05-18 08:13:05'),
(32, '036 - จ่ายยาผู้ป่วยใน', 193, '2026-05-18 08:13:05'),
(33, '037 - จ่ายยาผู้ป่วยนอก', 194, '2026-05-18 08:13:05'),
(34, '038 - จ่ายยาผู้ป่วยในสาขา', 195, '2026-05-18 08:13:05'),
(35, '039 - รังสีวิทยา', 196, '2026-05-18 08:13:05'),
(36, '040 - กายอุปกรณ์', 197, '2026-05-18 08:13:05'),
(37, '041 - กายภาพบำบัด', 198, '2026-05-18 08:13:05'),
(57, '042 - ห้องผ่าตัด', 199, '2026-05-22 03:25:56'),
(58, '043 - วิสัญญี', 200, '2026-05-22 03:25:56'),
(59, '044 - อุบัติเหตุฉุกเฉิน', 201, '2026-05-22 03:25:56'),
(60, '045 - ฉีดยาทำแผล', 202, '2026-05-22 03:25:56'),
(61, '046 - OR เล็ก', 203, '2026-05-22 03:25:56'),
(62, '047 - OBSERVE', 204, '2026-05-22 03:25:56'),
(63, '048 - ห้องเฝือก', 205, '2026-05-22 03:25:56'),
(64, '049 - ทันตกรรม', 206, '2026-05-22 03:25:56'),
(65, '050 - OPD ตา', 207, '2026-05-22 03:25:56'),
(66, '051 - OPD กลุ่มงานจิตเวช', 208, '2026-05-22 03:25:56'),
(67, '052 - PCU', 209, '2026-05-22 03:25:56'),
(68, '053 - OPD แพทย์ทางเลือก', 210, '2026-05-22 03:25:56'),
(69, '054 - OPD นรีเวช', 211, '2026-05-22 03:25:56'),
(70, '055 - OPD ชั้น 1', 212, '2026-05-22 03:25:56'),
(71, '056 - คลินิกพิเศษ OPD 2', 213, '2026-05-22 03:25:56'),
(72, '057 - การแพทย์แผนไทย', 214, '2026-05-22 03:25:56'),
(73, '073 - CCU', 215, '2026-05-22 03:25:56'),
(74, '059 - ตึกตา หู คอ จมูก', 216, '2026-05-22 03:25:56'),
(75, '060 - อายุรกรรมหญิง1', 217, '2026-05-22 07:58:44'),
(76, '061 - อายุรกรรมชาย 1', 218, '2026-05-22 07:58:44'),
(77, '062 - สงฆ์อาพาธ บน', 219, '2026-05-22 07:58:44'),
(78, '063 - สงฆ์อาพาธ ล่าง (พิเศษรวม 1)', 220, '2026-05-22 07:58:44'),
(79, '064 - ตึกประกันสังคม บน', 221, '2026-05-22 07:58:44'),
(80, '065 - ตึกประกันสังคม ล่าง', 222, '2026-05-22 07:58:44'),
(81, '066 - แพทย์ทางเลือก (อบ.3)', 1, '2026-05-22 07:58:44'),
(82, '067 - ศัลยกรรมชาย', 2, '2026-05-22 07:58:44'),
(83, '068 - ศัลยกรรมหญิง', 3, '2026-05-22 07:58:44'),
(84, '069 - พิเศษศัลยกรรม', 4, '2026-05-22 07:58:44'),
(85, '070 - ICU ศัลย์', 5, '2026-05-22 07:58:44'),
(86, '071 - ICU ศัลยกรรมประสาท', 6, '2026-05-22 07:58:44'),
(87, '072 - พิเศษรวมอายุรกรรม (อบ.3)', 7, '2026-05-22 07:58:44'),
(88, '058 - ICU อายุรกรรม', 8, '2026-05-22 07:58:44'),
(89, '074 - ธเนศวร', 9, '2026-05-22 07:58:44'),
(90, '075 - สูติสามัญ', 10, '2026-05-22 07:58:44'),
(91, '077 - สูติกรรมพิเศษ 1', 11, '2026-05-22 07:58:44'),
(92, '078 - ห้องคลอด', 12, '2026-05-22 07:58:44'),
(93, '079 - ศัลยกรรมกระดูก', 13, '2026-05-22 07:58:44'),
(94, '080 - เด็กสามัญ', 14, '2026-05-22 07:58:44'),
(95, '081 - เด็กพิเศษ', 15, '2026-05-22 07:58:44'),
(96, '082 - สูติกรรมพิเศษ 2', 16, '2026-05-22 07:58:44'),
(97, '083 - ICU เด็ก', 17, '2026-05-22 07:58:44'),
(98, '087 - เวชกรรมสังคม', 18, '2026-05-22 07:58:44'),
(99, '088 - งานประกันสังคม', 19, '2026-05-22 07:58:44'),
(100, '090 - พยาบาลชุมชน', 20, '2026-05-22 07:58:44'),
(101, '091 - คลินิคให้คำปรึกษา', 21, '2026-05-22 07:58:44'),
(102, '093 - สุขศึกษา', 22, '2026-05-22 07:58:44'),
(103, '094 - พิเศษสุวัทนา', 23, '2026-05-22 07:58:44'),
(104, '095 - บ้านเปรมสุข 1', 24, '2026-05-22 07:58:44'),
(105, '096 - งานอาชีวเวชกรรม', 25, '2026-05-22 07:58:44'),
(106, '097 - ชันสูตรโรค', 26, '2026-05-22 07:58:44'),
(107, '102 - ฝ่ายการเจ้าหน้าที่', 27, '2026-05-22 07:58:44'),
(108, '104 - ตึกประกันสังคม', 28, '2026-05-22 07:58:44'),
(109, '106 - งานสนับสนุนบริการสุขภาพ', 29, '2026-05-22 07:58:44'),
(110, '108 - งานบริหารเวชภัณฑ์ (เวชภัณฑ์ที่มิใช่ยา)', 30, '2026-05-22 07:58:44'),
(111, '109 - สอ.โคกไม้ลาย', 31, '2026-05-22 07:58:44'),
(112, '110 - สอ.วัดโบสถ์', 32, '2026-05-22 07:58:44'),
(113, '111 - สอ.บางบริบูรณ์', 33, '2026-05-22 07:58:44'),
(114, '112 - สอ.ห้วยเกษียรใหญ่', 34, '2026-05-22 07:58:44'),
(115, '113 - สอ.ไม้เค็ด', 35, '2026-05-22 07:58:44'),
(116, '114 - สอ.รอบเมือง', 36, '2026-05-22 07:58:44'),
(117, '115 - สอ.ท่างาม', 37, '2026-05-22 07:58:44'),
(118, '116 - สอ.เนินหอม', 38, '2026-05-22 07:58:44'),
(119, '117 - สอ.สนทรีย์', 39, '2026-05-22 07:58:44'),
(120, '118 - งานการพยาบาลชุมชน', 40, '2026-05-22 07:58:44'),
(121, '119 - วิทยาลัยแพทย์แผนไทย', 41, '2026-05-22 07:58:44'),
(122, '165 - สอ.เมือง', 42, '2026-05-22 07:58:44'),
(123, '137 - สำนักงานสาธารณสุขอำเภอเมืองปราจีนบุรี', 43, '2026-05-22 07:58:44'),
(124, '133 - คลินิกประกันสังคม 304', 44, '2026-05-22 07:58:44'),
(125, '135 - ศูนย์สุขภาพชุมชน', 45, '2026-05-22 07:58:44'),
(126, '136 - เตาเผาขยะ', 46, '2026-05-22 07:58:44'),
(127, '140 - กลุ่มงานเทคนิคการแพทย์', 47, '2026-05-22 07:58:44'),
(128, '138 - OPD ENT', 48, '2026-05-22 07:58:44'),
(129, '139 - ห้องพักแพทย์', 49, '2026-05-22 07:58:44'),
(130, '141 - งานเลขานุการ', 50, '2026-05-22 07:58:44'),
(131, '142 - ตึกเจ้าพระยาอภัยภูเบศร', 51, '2026-05-22 07:58:44'),
(132, '144 - กิจกรรมบำบัด', 52, '2026-05-22 07:58:44'),
(133, '120 - สอ.ดงขี้เหล็ก', 53, '2026-05-22 07:58:44'),
(134, '123 - สอ.ดงพระราม', 54, '2026-05-22 07:58:44'),
(135, '145 - ศูนย์เทศบาลเมือง', 55, '2026-05-22 07:58:44'),
(136, '146 - สอ.ดงกระทงยาม', 56, '2026-05-22 07:58:44'),
(137, '147 - จ่ายยาผู้ป่วยนอก(ห้องเฝือก)', 57, '2026-05-22 07:58:44'),
(138, '148 - จ่ายยาผู้ป่วยใน (กายภาพ)', 58, '2026-05-22 07:58:44'),
(139, '149 - โรงงาน ฮิตาชิ', 59, '2026-05-22 07:58:44'),
(140, '152 - สอ.ศาลานเรศวร', 60, '2026-05-22 07:58:44'),
(141, '159 - รพ.ประจันตคาม', 61, '2026-05-22 07:58:44'),
(142, '153 - สอ.บ้านพระ', 62, '2026-05-22 07:58:44'),
(143, '154 - สอ.บางกุ้ง', 63, '2026-05-22 07:58:44'),
(144, '155 - สอ.ทุ่งตะลุมพุก', 64, '2026-05-22 07:58:44'),
(145, '156 - สอ.หาดยาง', 65, '2026-05-22 07:58:44'),
(146, '157 - สอ.โนนห้อม', 66, '2026-05-22 07:58:44'),
(147, '158 - สอ.ห้วยเกษียรน้อย', 67, '2026-05-22 07:58:44'),
(148, '160 - รพ.ศรีมโหสถ', 68, '2026-05-22 07:58:44'),
(149, '161 - สอ.บางเดชะ', 69, '2026-05-22 07:58:44'),
(150, '162 - รพ.บ้านสร้าง', 70, '2026-05-22 07:58:44'),
(151, '163 - รพ.ศรีมหาโพธิ์', 71, '2026-05-22 07:58:44'),
(152, '164 - ห้องรองบริหาร', 72, '2026-05-22 07:58:44'),
(153, '130 - สอ.เทศบาล', 73, '2026-05-22 07:58:44'),
(154, '166 - เภสัชสนเทศ', 74, '2026-05-22 07:58:44'),
(155, '167 - คลังเวชภัณฑ์มิใช่ยา', 75, '2026-05-22 07:58:44'),
(156, '168 - รพ.นาดี', 76, '2026-05-22 07:58:44'),
(157, '169 - เซลล์วิทยา', 77, '2026-05-22 07:58:44'),
(158, '170 - กลุ่มงานพยาธิกายวิภาค', 78, '2026-05-22 07:58:44'),
(159, '171 - เรือนจำจังหวัดปราจีนบุรี', 79, '2026-05-22 07:58:44'),
(160, '172 - โลหิตวิทยา', 80, '2026-05-22 07:58:44'),
(161, '173 - ภูมิคุ้มกันวิทยา', 81, '2026-05-22 07:58:44'),
(162, '174 - อณูชีววิทยา', 82, '2026-05-22 07:58:44'),
(163, '175 - แบคทีเรีย', 83, '2026-05-22 07:58:44'),
(164, '176 - จุลทรรศน์ศาสตร์', 84, '2026-05-22 07:58:44'),
(165, '177 - ห้องล้างเครื่องมือ LAB', 85, '2026-05-22 07:58:44'),
(166, '031 - พยาธิคลินิก', 86, '2026-05-22 07:58:44'),
(167, '179 - งานเคมีคลินิก', 87, '2026-05-22 07:58:44'),
(168, '180 - ศูนย์สุขภาพชุมชนศาลาไทย', 88, '2026-05-22 07:58:44'),
(169, '181 - ศูนย์สุขภาพชุมชนเจ้าพระยาฯ', 89, '2026-05-22 07:58:44'),
(170, '182 - ฝ่ายแผนงานและสารสนเทศ', 90, '2026-05-22 07:58:44'),
(171, '183 - สถานพินิจและคุ้มครองเด็กและเยาวชนจังหวัดปราจีนบุรี', 91, '2026-05-22 07:58:44'),
(172, '184 - งานบัญชี', 92, '2026-05-22 07:58:44'),
(173, '185 - ศูนย์แพทยศาสตร์', 93, '2026-05-22 07:58:44'),
(174, '187 - อภัยภูเบศรเดย์สปา', 94, '2026-05-22 07:58:44'),
(175, '186 - เทคโนโลยีสารสนเทศ', 95, '2026-05-22 07:58:44'),
(176, '188 - ศูนย์การเรียนรู้ด้านการแพทย์แผนไทย (ร้านยาไทยโพธิ์เงิน)', 96, '2026-05-22 07:58:44'),
(177, '189 - ประสาทวิทยา', 97, '2026-05-22 07:58:44'),
(178, '190 - ศูนย์จัดเก็บรายได้', 98, '2026-05-22 07:58:44'),
(179, '191 - OPD หู คอ จมูก', 99, '2026-05-22 07:58:44'),
(180, '192 - สูติกรรมแผนไทย', 100, '2026-05-22 07:58:44'),
(181, '194 - อายุรกรรมหญิง2', 101, '2026-05-22 07:58:44'),
(182, '195 - รพ.กบินทร์บุรี', 102, '2026-05-22 07:58:44'),
(183, '197 - ห้องปฎิบัติบัติการตรวจสวนหัวใจ', 103, '2026-05-22 07:58:44'),
(184, '198 - คลินิกพัฒนาการและพฤติกรรมเด็ก', 104, '2026-05-22 07:58:44'),
(185, '199 - หอผู้ป่วยพิเศษ (อายุรกรรมหญิง)', 105, '2026-05-22 07:58:44'),
(186, '200 - อายุรกรรมชาย 2', 106, '2026-05-22 07:58:44'),
(187, '201 - พิเศษอายุรกรรมหญิง', 107, '2026-05-22 07:58:44'),
(188, '202 - วันเดย์ยูนิต', 108, '2026-05-22 07:58:44'),
(189, '1120 - บริษัท เมกกะฟิล จำกัด', 109, '2026-05-22 07:58:44'),
(190, '203 - ANC', 110, '2026-05-22 07:58:44'),
(191, '204 - หอผู้ป่วยหัวใจและหลอดเลือด (ICCU)', 111, '2026-05-22 07:58:44'),
(192, '205 - OPD ศัลยกรรมทั่วไป', 112, '2026-05-22 07:58:44'),
(193, '206 - OPD ศัลยกรรมกระดูก', 113, '2026-05-22 07:58:44'),
(194, '207 - ศูนย์การเรียนรู้สมุนไพรและภูมิปัญญาสุขภาพ บางเดชะ (ภูมิภูเบศร)', 114, '2026-05-22 07:58:44'),
(195, '208 - หอผู้ป่วยวิกฤตทางเดินหายใจ (RICU)', 115, '2026-05-22 07:58:44'),
(196, '211 - แม่บ้านชั้น 5 อาคารเฉลิมพระเกียรติ', 116, '2026-05-22 07:58:44'),
(197, '209 - จุลชีววิทยาคลินิก', 117, '2026-05-22 07:58:44'),
(198, '210 - ศูนย์เครื่องมือแพทย์', 118, '2026-05-22 07:58:44'),
(199, '212 - หน่วยไตเทียม2', 119, '2026-05-22 07:58:44'),
(200, '213 - สำนักงานสาธารณสุขจังหวัดปราจีนบุรี', 120, '2026-05-22 07:58:44'),
(201, '214 - smart Isoaltion ward (SIW)', 121, '2026-05-22 07:58:44'),
(202, '215 - เคมีบำบัด ชั้น 3', 122, '2026-05-22 07:58:44'),
(203, '217 - โรงงาน GMP', 123, '2026-05-22 07:58:44'),
(204, '218 - ตำรวจภูธรจังหวัดปราจีนบุรี', 124, '2026-05-22 07:58:44'),
(205, '219 - PICU & SNB', 125, '2026-05-22 07:58:44'),
(206, '220 - ส่องกล้องทางเดินอาหารศัลยกรรม', 126, '2026-05-22 07:58:44'),
(207, '497 - ศูนย์ผ่าตัดวันเดียวกลับ (ODS)', 127, '2026-05-22 07:58:44'),
(208, '498 - ห้องส่องกล้องทางเดินอาหารศัลยกรรม', 128, '2026-05-22 07:58:44'),
(209, '221 - wrjm', 129, '2026-05-22 07:58:44'),
(210, '222 - หอผู้ป่วยแยกโรคตึกธรรมรักษ์', 130, '2026-05-22 07:58:44'),
(211, '223 - สำนักงานสาธารณสุขอำเภอเมืองปราจีนบุรี', 131, '2026-05-22 07:58:44'),
(212, '499 - ศูนย์ขนส่งกลาง (Logistic center)', 132, '2026-05-22 07:58:44'),
(213, '225 - (รพ. สนาม กองร้อยต่อสู้รถถัง 2) ปราจีนบุรีประชารักษ์', 133, '2026-05-22 07:58:44'),
(214, '226 - (รพ. สนาม 3 ) ประจันตคามประชารักษ์', 134, '2026-05-22 07:58:44'),
(215, '227 - ร.พ. อภัยภูเบศรประชารักษ์', 135, '2026-05-22 07:58:44'),
(216, '228 - สนาม เทศบาลประชารักษ์', 136, '2026-05-22 07:58:44'),
(217, '229 - หอผู้ป่วยกึ่งวิกฤตแยกโรค ( Cohort Sub ICU )', 137, '2026-05-22 07:58:44'),
(218, '999 - ไม่ระบุ', 138, '2026-05-22 07:58:44'),
(219, '230 - วัตถุดิบสมุนไพร', 139, '2026-05-22 07:58:44'),
(220, '500 - คลังวัคซีน', 140, '2026-05-22 07:58:44'),
(221, '231 - Covid สูตินรีเวช', 141, '2026-05-22 07:58:44'),
(222, '232 - Modular ICU', 142, '2026-05-22 07:58:44'),
(223, '233 - หอผู้ป่วย เคมีบำบัด ชั้น 5', 143, '2026-05-22 07:58:44'),
(224, '083 - ไอซียูเด็ก(NICU)', 144, '2026-05-22 07:58:44'),
(225, '234 - LAB ER', 145, '2026-05-22 07:58:44'),
(226, '501 - SMC ศัลยกรรม (Gen Sx Uro,Breast)', 146, '2026-05-22 07:58:44'),
(227, '326 - ศูนย์หลักฐานเชิงประจักษ์', 147, '2026-05-22 07:58:44'),
(228, '502 - หอผู้ป่วยจิตตารมณ์', 148, '2026-05-22 07:58:44'),
(229, '072 - พิเศษรวมอายุกรรม', 149, '2026-05-22 07:58:44'),
(230, '503 - Wound care and Colostomy care', 150, '2026-05-22 07:58:44'),
(231, '216 - งานควบคุมและป้องกันการติดเชื้อ', 151, '2026-05-22 07:58:44'),
(232, '504 - ทัณฑสถานเปิดบ้านเนินสูง', 152, '2026-05-22 07:58:44'),
(233, '505 - สุขภาพดิจิทัล', 153, '2026-05-22 07:58:44'),
(234, '506 - การวิจัยและพัฒนาการพยาบาล', 154, '2026-05-22 07:58:44'),
(235, '507 - งานกฎหมาย', 155, '2026-05-22 07:58:44'),
(236, '508 - กึ่งวิกฤตศัลยกรรม', 156, '2026-05-22 07:58:44'),
(237, '1365 - การเงิน ยาใน', 157, '2026-05-22 07:58:44'),
(238, '1366 - การเงิน ยานอก', 158, '2026-05-22 07:58:44'),
(239, '1367 - การเงิน ER', 159, '2026-05-22 07:58:44'),
(240, '1368 - การเงิน ชั้น 2', 160, '2026-05-22 07:58:44'),
(241, '1369 - การเงิน อายุรกรรม 14', 161, '2026-05-22 07:58:44');

-- --------------------------------------------------------

--
-- Table structure for table `floors`
--

CREATE TABLE `floors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `floors`
--

INSERT INTO `floors` (`id`, `name`, `sort_order`, `created_at`) VALUES
(1, 'ชั้น 1', 1, '2026-05-18 08:13:05'),
(2, 'ชั้น 2', 2, '2026-05-18 08:13:05'),
(3, 'ชั้น 3', 3, '2026-05-18 08:13:05'),
(4, 'ชั้น 4', 4, '2026-05-18 08:13:05'),
(5, 'ชั้น 5', 5, '2026-05-18 08:13:05'),
(6, 'ชั้น 6', 6, '2026-05-18 08:13:05'),
(7, 'ชั้น 7', 7, '2026-05-18 08:13:05');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `spec` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'เธเธดเนเธ',
  `min_alert` int(11) DEFAULT 5,
  `image` varchar(255) DEFAULT 'default_product.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `category`, `brand`, `model`, `spec`, `price`, `unit`, `min_alert`, `image`, `created_at`) VALUES
(29, '097855067128', 'Keyboard', 'IT', 'Logitech', 'k120 corded', '', 300.00, 'ชิ้น', 5, 'products/prod_1779418665.png', '2026-05-22 02:57:45'),
(30, '097855180582', 'Mouse', 'IT', 'Logitech', 'M100r', '', 200.00, 'ชิ้น', 5, 'products/prod_1779418732.png', '2026-05-22 02:58:52'),
(31, '013803233841', 'Pinter', 'IT', 'Canon', 'imageCLASS LBP6030', '', 4000.00, 'เครื่อง', 5, 'products/prod_1780560962.png', '2026-06-04 08:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_serials`
--

CREATE TABLE `product_serials` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `serial_code` varchar(100) NOT NULL,
  `status` enum('available','borrowed','repairing','broken','lost') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_serials`
--

INSERT INTO `product_serials` (`id`, `product_id`, `serial_code`, `status`, `created_at`) VALUES
(78, 29, '2512MRK00AK9', 'borrowed', '2026-05-22 02:57:45'),
(79, 30, '2531APU0VKY9', 'borrowed', '2026-05-22 02:58:52'),
(80, 30, '2531APF0VKW9', 'borrowed', '2026-05-25 03:30:42'),
(81, 30, '2531APE0YMF9', 'borrowed', '2026-06-02 07:23:57'),
(82, 29, '2512MRH009A9', 'borrowed', '2026-06-02 07:24:27'),
(83, 30, '2531APW0YME9', 'borrowed', '2026-06-04 02:20:39'),
(84, 31, 'PCLA957801', 'available', '2026-06-04 08:30:46'),
(85, 31, 'PCLA831066', 'available', '2026-06-04 08:30:46'),
(86, 31, 'PCLA821073', 'available', '2026-06-04 08:30:46'),
(87, 31, 'PCLA831104', 'available', '2026-06-04 08:30:46'),
(88, 31, 'PCLA831081', 'available', '2026-06-04 08:30:46'),
(89, 31, 'PCLA831107', 'available', '2026-06-04 08:30:46'),
(90, 31, 'PCLA957809', 'available', '2026-06-04 08:30:46'),
(91, 31, 'PCLA831017', 'available', '2026-06-04 08:30:46'),
(92, 31, 'PCLA955188', 'available', '2026-06-04 08:30:46'),
(93, 31, 'PCLA831110', 'available', '2026-06-04 08:30:46'),
(94, 31, 'PCLA831050', 'available', '2026-06-04 08:30:46'),
(95, 31, 'PCLA831106', 'available', '2026-06-04 08:30:46'),
(96, 31, 'PCLA968198', 'available', '2026-06-04 08:30:46'),
(97, 31, 'PCLA957789', 'borrowed', '2026-06-04 08:30:46'),
(98, 31, 'PCLA955024', 'available', '2026-06-04 08:30:46'),
(99, 31, 'PCLA831095', 'borrowed', '2026-06-05 01:33:37'),
(100, 30, '2524APTC2TD9', 'borrowed', '2026-06-05 01:57:38'),
(101, 30, '2531APM0VL19', 'available', '2026-06-05 02:15:37'),
(102, 30, '2531APL10D49', 'available', '2026-06-05 02:15:37'),
(103, 30, '2531APH0YM89', 'available', '2026-06-05 02:15:37'),
(104, 30, '2531APP0YMM9', 'available', '2026-06-05 02:49:26'),
(105, 30, '2531AP70YMC9', 'available', '2026-06-05 02:49:26'),
(106, 30, '2531AP30YMG9', 'available', '2026-06-05 02:49:26'),
(107, 30, '2531APQ0YMU9', 'available', '2026-06-05 02:49:26'),
(108, 30, '2531APX0VL29', 'available', '2026-06-05 02:49:26'),
(109, 30, '2531APT10CM9', 'available', '2026-06-05 02:49:26'),
(110, 30, '2524APVC2CM9', 'available', '2026-06-05 02:49:26'),
(111, 30, '2524APGC2CG9', 'available', '2026-06-05 02:49:26'),
(112, 30, '2531AP70YML9', 'available', '2026-06-05 02:49:26'),
(113, 30, '2531APG0YMN9', 'available', '2026-06-05 02:49:26'),
(114, 30, '2531APQ0YMA9', 'available', '2026-06-05 02:49:26'),
(115, 30, '2531AP50VKX9', 'available', '2026-06-05 02:49:26'),
(116, 30, '2531APD0YM69', 'available', '2026-06-05 02:49:26'),
(117, 30, '2524AP5C2CL9', 'available', '2026-06-05 02:49:26'),
(118, 30, '2531APA0VLB9', 'available', '2026-06-05 02:49:26'),
(119, 30, '2524APPC2CJ9', 'available', '2026-06-05 02:49:26'),
(120, 30, '2531APC0YMD9', 'available', '2026-06-05 02:49:26');

-- --------------------------------------------------------

--
-- Table structure for table `reasons`
--

CREATE TABLE `reasons` (
  `id` int(11) NOT NULL,
  `type` enum('borrow','import') NOT NULL,
  `label` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reasons`
--

INSERT INTO `reasons` (`id`, `type`, `label`, `created_at`, `sort_order`) VALUES
(1, 'borrow', 'ของเก่าชำรุด', '2026-05-10 18:43:26', 2),
(2, 'borrow', 'ติดตั้งคอมใหม่', '2026-05-10 18:43:26', 1),
(4, 'borrow', 'สำรองใช้งานชั่วคราว', '2026-05-10 18:43:26', 3),
(5, 'import', 'จัดซื้อใหม่', '2026-05-10 18:43:26', 1),
(6, 'import', 'รับบริจาค', '2026-05-10 18:43:26', 2),
(8, 'import', 'คืนจากโครงการ', '2026-05-10 18:43:26', 3);

-- --------------------------------------------------------

--
-- Table structure for table `stock_imports`
--

CREATE TABLE `stock_imports` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_imports`
--

INSERT INTO `stock_imports` (`id`, `admin_id`, `reason`, `created_at`) VALUES
(1, 1, 'นำเข้าครุภัณฑ์ไอทีรอบแรก ประจำปี 2568', '2025-01-10 09:00:00'),
(2, 1, 'เพิ่มอุปกรณ์รองรับพนักงานใหม่ Q1/2568', '2025-03-01 08:30:00'),
(4, 1, 'จัดซื้อใหม่', '2026-05-15 08:12:56'),
(5, 1, 'จัดซื้อใหม่', '2026-05-18 07:47:28'),
(6, 1, 'จัดซื้อใหม่', '2026-05-21 03:12:59'),
(7, 1, 'จัดซื้อใหม่', '2026-05-21 03:14:56'),
(8, 1, 'จัดซื้อใหม่', '2026-05-21 03:15:15'),
(9, 1, 'จัดซื้อใหม่', '2026-05-21 05:00:09'),
(10, 9, 'จัดซื้อใหม่', '2026-05-21 06:58:00'),
(11, 9, 'จัดซื้อใหม่', '2026-05-21 07:03:39'),
(12, 9, 'จัดซื้อใหม่', '2026-05-21 07:10:57'),
(13, 1, 'จัดซื้อใหม่', '2026-05-21 09:46:57'),
(14, 1, 'จัดซื้อใหม่', '2026-05-22 01:57:29'),
(15, 1, 'จัดซื้อใหม่', '2026-05-22 02:52:09'),
(16, 1, 'จัดซื้อใหม่', '2026-05-22 02:57:45'),
(17, 1, 'จัดซื้อใหม่', '2026-05-22 02:58:52'),
(18, 1, 'จัดซื้อใหม่', '2026-05-25 03:30:42'),
(19, 1, 'จัดซื้อใหม่', '2026-06-02 07:23:57'),
(20, 1, 'จัดซื้อใหม่', '2026-06-02 07:24:27'),
(21, 1, 'จัดซื้อใหม่', '2026-06-04 02:20:39'),
(22, 1, 'จัดซื้อใหม่', '2026-06-04 08:30:46'),
(23, 1, 'จัดซื้อใหม่', '2026-06-05 01:33:37'),
(24, 1, 'จัดซื้อใหม่', '2026-06-05 01:57:38'),
(25, 1, 'จัดซื้อใหม่', '2026-06-05 02:15:37'),
(26, 1, 'จัดซื้อใหม่', '2026-06-05 02:49:26');

-- --------------------------------------------------------

--
-- Table structure for table `stock_import_items`
--

CREATE TABLE `stock_import_items` (
  `id` int(11) NOT NULL,
  `import_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_import_items`
--

INSERT INTO `stock_import_items` (`id`, `import_id`, `product_id`, `qty`) VALUES
(35, 16, 29, 1),
(36, 17, 30, 1),
(37, 18, 30, 1),
(38, 19, 30, 1),
(39, 20, 29, 1),
(40, 21, 30, 1),
(41, 22, 31, 15),
(42, 23, 31, 1),
(43, 24, 30, 1),
(44, 25, 30, 3),
(45, 26, 30, 17);

-- --------------------------------------------------------

--
-- Table structure for table `stock_import_serials`
--

CREATE TABLE `stock_import_serials` (
  `id` int(11) NOT NULL,
  `import_item_id` int(11) NOT NULL,
  `serial_code` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_import_serials`
--

INSERT INTO `stock_import_serials` (`id`, `import_item_id`, `serial_code`) VALUES
(75, 35, '2512MRK00AK9'),
(76, 36, '2531APU0VKY9'),
(77, 37, '2531APF0VKW9'),
(78, 38, '2531APE0YMF9'),
(79, 39, '2512MRH009A9'),
(80, 40, '2531APW0YME9'),
(81, 41, 'PCLA957801'),
(82, 41, 'PCLA831066'),
(83, 41, 'PCLA821073'),
(84, 41, 'PCLA831104'),
(85, 41, 'PCLA831081'),
(86, 41, 'PCLA831107'),
(87, 41, 'PCLA957809'),
(88, 41, 'PCLA831017'),
(89, 41, 'PCLA955188'),
(90, 41, 'PCLA831110'),
(91, 41, 'PCLA831050'),
(92, 41, 'PCLA831106'),
(93, 41, 'PCLA968198'),
(94, 41, 'PCLA957789'),
(95, 41, 'PCLA955024'),
(96, 42, 'PCLA831095'),
(97, 43, '2524APTC2TD9'),
(98, 44, '2531APM0VL19'),
(99, 44, '2531APL10D49'),
(100, 44, '2531APH0YM89'),
(101, 45, '2531APP0YMM9'),
(102, 45, '2531AP70YMC9'),
(103, 45, '2531AP30YMG9'),
(104, 45, '2531APQ0YMU9'),
(105, 45, '2531APX0VL29'),
(106, 45, '2531APT10CM9'),
(107, 45, '2524APVC2CM9'),
(108, 45, '2524APGC2CG9'),
(109, 45, '2531AP70YML9'),
(110, 45, '2531APG0YMN9'),
(111, 45, '2531APQ0YMA9'),
(112, 45, '2531AP50VKX9'),
(113, 45, '2531APD0YM69'),
(114, 45, '2524AP5C2CL9'),
(115, 45, '2531APA0VLB9'),
(116, 45, '2524APPC2CJ9'),
(117, 45, '2531APC0YMD9');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `sort_order`) VALUES
(1, 'ชิ้น', 1),
(2, 'ตัว', 2),
(3, 'เครื่อง', 3),
(4, 'ชุด', 4),
(5, 'กล่อง', 5),
(6, 'อัน', 6),
(7, 'ม้วน', 7);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'USER',
  `image` varchar(255) DEFAULT 'default_user.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `firstname`, `lastname`, `fullname`, `email`, `password`, `role`, `image`, `created_at`) VALUES
(1, 'admin', 'กชกร', 'เมืองโพธิ์', 'กชกร เมืองโพธิ์', 'admin@gmail.com', '$2y$10$a3rbNVhCnHA0tLfWqKMlkucw1Z1j6cgwKyV/7FnbYQiQ3QT3mY87K', 'SUPERADMIN', 'users/user_1779340070_743.jpg', '2025-01-02 08:00:00'),
(9, 'aomaom', 'เกวลิน', 'คอตแลนด์', '', 'baekaommie04@gmail.com', '$2y$10$U9WT.0RE6WDx1w6Hrq0kHeJT/j90kATI2ZcBGWOdwLHe6fIB3Sjg.', 'ADMIN', 'default_user.png', '2026-05-21 06:29:30'),
(11, 'มาริสา695', 'มาริสา', 'ด่านดี', '', 'มาริสา695@it-system.com', '$2y$10$uyRuqa5d9..4c8XmXqcXoOSaAbXL94ro9qVWrFSeYnrUTeo.W6ufa', 'USER', 'default_user.png', '2026-05-22 08:58:08'),
(12, 'นางสาวปิยะรัตน์420', 'นางสาวปิยะรัตน์', 'กิจจะ', '', 'นางสาวปิยะรัตน์420@it-system.com', '$2y$10$qvcU4P3Lk6HzuFfAe9E2oeQErscPf/sc/8LW/p3sFJkVP8Qab2S9e', 'USER', 'default_user.png', '2026-05-28 03:05:55'),
(13, 'test', 'test', 'test', '', 'test@gmail.com', '$2y$10$vjBgAoKgvF9v9pZQ6r9HyukQzIYb.gwL3i9bZvM4d.O1wWD/Knvbq', 'USER', 'default_user.png', '2026-05-28 09:06:08'),
(21, 'นายปฐมพงศ์407', 'นายปฐมพงศ์', 'สุภาพ', '', 'นายปฐมพงศ์407@it-system.com', '$2y$10$9h1/m2yUJtG1E2Tmb0Wv3ebvUL1W0UHNIkk96dYt0TtWkHbJorWy2', 'USER', 'default_user.png', '2026-06-02 07:42:08'),
(22, 'นางวิภาวดี242', 'นางวิภาวดี', 'ดวงจันทร์', '', 'นางวิภาวดี242@it-system.com', '$2y$10$4VrqeIiwSYXX6RWL/rYuQ.XKdBOS/nQ3t2SponPizQWLb.i1qY3/m', 'USER', 'default_user.png', '2026-06-04 02:35:23'),
(23, 'นายวัชพงษ์934', 'นายวัชพงษ์', 'อัณฑสูตร', '', 'นายวัชพงษ์934@it-system.com', '$2y$10$dIhQraHoJPWCSdNqgQpb4uR3.5JEL5P4Vm8MAON96BuDUsioEBSjO', 'USER', 'default_user.png', '2026-06-05 01:37:06'),
(24, 'นายพงศธร614', 'นายพงศธร', 'ฝากกาย', '', 'นายพงศธร614@it-system.com', '$2y$10$iYJ.V/Sn121eLPvBTFUXBukFT0TkYgAzOlwKtocm.UYRaTVxW2hcy', 'USER', 'default_user.png', '2026-06-05 02:13:42'),
(25, 'นางสาวณัชชา728', 'นางสาวณัชชา', 'เต็งเติมวงศ์', '', 'นางสาวณัชชา728@it-system.com', '$2y$10$/XjkqmBfYQvRI4PSgaBO0e11BnsWMMxEHfiIItgSmb0DZNC00BPSC', 'USER', 'default_user.png', '2026-06-05 03:39:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `serial_id` (`serial_id`),
  ADD KEY `borrower_id` (`borrower_id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `floors`
--
ALTER TABLE `floors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `product_serials`
--
ALTER TABLE `product_serials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_code` (`serial_code`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reasons`
--
ALTER TABLE `reasons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_imports`
--
ALTER TABLE `stock_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `stock_import_items`
--
ALTER TABLE `stock_import_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `import_id` (`import_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_import_serials`
--
ALTER TABLE `stock_import_serials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `import_item_id` (`import_item_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `floors`
--
ALTER TABLE `floors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `product_serials`
--
ALTER TABLE `product_serials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `reasons`
--
ALTER TABLE `reasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `stock_imports`
--
ALTER TABLE `stock_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `stock_import_items`
--
ALTER TABLE `stock_import_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `stock_import_serials`
--
ALTER TABLE `stock_import_serials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`serial_id`) REFERENCES `product_serials` (`id`),
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `product_serials`
--
ALTER TABLE `product_serials`
  ADD CONSTRAINT `product_serials_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_imports`
--
ALTER TABLE `stock_imports`
  ADD CONSTRAINT `stock_imports_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_import_items`
--
ALTER TABLE `stock_import_items`
  ADD CONSTRAINT `stock_import_items_ibfk_1` FOREIGN KEY (`import_id`) REFERENCES `stock_imports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_import_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_import_serials`
--
ALTER TABLE `stock_import_serials`
  ADD CONSTRAINT `stock_import_serials_ibfk_1` FOREIGN KEY (`import_item_id`) REFERENCES `stock_import_items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
