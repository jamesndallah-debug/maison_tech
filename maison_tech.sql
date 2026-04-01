-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 06:12 PM
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
-- Database: `maison_tech`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_us`
--

CREATE TABLE `about_us` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT 'Maison Tech',
  `description` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `agency_service_image` varchar(255) DEFAULT NULL,
  `office_image` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_us`
--

INSERT INTO `about_us` (`id`, `company_name`, `description`, `vision`, `mission`, `contact_email`, `contact_phone`, `address`, `agency_service_image`, `office_image`, `updated_at`) VALUES
(1, 'Maison Tech', 'Maison Tech is your premier destination for high-quality technology solutions. Our entire system, from inventory to client orders, is professionally controlled by our Administrative team to ensure the highest standards of service and security.', 'To be the leading tech provider in the region.', 'To make technology feel at home for everyone.', 'jamesndallah@gmail.com', '+255710726602, +2557672027115', 'Rwezaula Singida', 'https://images.unsplash.com/photo-1553413077-190dd305871c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', '2026-03-18 14:13:37');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `log_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `log_date`) VALUES
(1, 1, 'Logged in', '2026-03-17 17:44:33'),
(2, 1, 'Created new employee account: Sylvester Ndallah (role: chairman)', '2026-03-17 17:51:27'),
(3, 2, 'Logged in', '2026-03-17 17:51:43'),
(4, 2, 'Logged in', '2026-03-17 19:28:45'),
(5, 1, 'Logged in', '2026-03-17 19:29:36'),
(6, 1, 'Added new category: Covers', '2026-03-17 19:30:18'),
(7, 1, 'Added new category: Charges', '2026-03-17 19:30:34'),
(8, 1, 'Added new product: Cover', '2026-03-17 19:31:13'),
(9, 1, 'Added new product: Cover', '2026-03-17 19:35:47'),
(10, 1, 'Created new employee account: Irene (role: staff)', '2026-03-17 19:37:14'),
(11, 3, 'Logged in', '2026-03-17 19:37:30'),
(12, 3, 'Completed sale #1 - Total: $5,000.00', '2026-03-17 19:38:02'),
(13, 1, 'Logged in', '2026-03-17 19:39:34'),
(14, 3, 'Logged in', '2026-03-17 19:58:55'),
(15, 3, 'Completed sale #2 - Total: $50,000.00', '2026-03-17 20:00:02'),
(16, 3, 'Completed sale #3 - Total: $20,000.00', '2026-03-17 20:21:42'),
(17, 3, 'Completed sale #4 - Total: $25,000.00', '2026-03-17 20:23:56'),
(18, 2, 'Logged in', '2026-03-17 20:28:21'),
(19, 1, 'Logged in', '2026-03-17 20:37:02'),
(20, 1, 'Added new product: Charges 1', '2026-03-17 20:38:51'),
(21, 3, 'Logged in', '2026-03-17 20:40:15'),
(22, 2, 'Logged in', '2026-03-17 20:46:17'),
(23, 2, 'Logged in', '2026-03-18 05:18:50'),
(24, 1, 'Logged in', '2026-03-18 05:27:54'),
(25, 1, 'Adjusted stock for Cover: -5 (Restock)', '2026-03-18 05:41:32'),
(26, 1, 'Adjusted stock for Cover: +5 (Restock)', '2026-03-18 05:41:57'),
(27, 2, 'Logged in', '2026-03-18 05:55:22'),
(28, 3, 'Logged in', '2026-03-18 05:55:47'),
(29, 1, 'Logged in', '2026-03-18 05:56:28'),
(30, 1, 'Created new employee account: Jackson (role: manager)', '2026-03-18 05:57:06'),
(31, 1, 'Created new employee account: Angel (role: money_agent)', '2026-03-18 05:57:17'),
(32, 4, 'Logged in', '2026-03-18 05:57:43'),
(33, 1, 'Logged in', '2026-03-18 06:47:27'),
(34, 5, 'Logged in', '2026-03-18 07:00:03'),
(35, 1, 'Logged in', '2026-03-18 07:16:35'),
(36, 2, 'Logged in', '2026-03-18 07:28:11'),
(37, 5, 'Logged in', '2026-03-18 07:28:54'),
(38, 1, 'Logged in', '2026-03-18 07:31:58'),
(39, 2, 'Logged in', '2026-03-18 07:37:36'),
(40, 3, 'Logged in', '2026-03-18 07:42:49'),
(41, 4, 'Logged in', '2026-03-18 07:54:23'),
(42, 1, 'Logged in', '2026-03-18 09:40:24'),
(43, 1, 'Logged in', '2026-03-18 09:45:18'),
(44, 2, 'Logged in', '2026-03-18 09:45:40'),
(45, 2, 'Logged in', '2026-03-18 09:45:46'),
(46, 1, 'Logged in', '2026-03-18 10:46:57'),
(47, 1, 'Deleted salary payment record ID: 12', '2026-03-18 12:13:48'),
(48, 1, 'Deleted salary payment record ID: 11', '2026-03-18 12:13:51'),
(49, 1, 'Deleted salary payment record ID: 10', '2026-03-18 12:13:54'),
(50, 1, 'Deleted salary payment record ID: 9', '2026-03-18 12:13:57'),
(51, 1, 'Deleted salary payment record ID: 8', '2026-03-18 12:13:59'),
(52, 1, 'Deleted salary payment record ID: 7', '2026-03-18 12:14:02'),
(53, 1, 'Deleted salary payment record ID: 6', '2026-03-18 12:14:05'),
(54, 1, 'Deleted salary payment record ID: 5', '2026-03-18 12:14:08'),
(55, 1, 'Deleted salary payment record ID: 4', '2026-03-18 12:14:10'),
(56, 1, 'Deleted salary payment record ID: 3', '2026-03-18 12:14:14'),
(57, 2, 'Logged in', '2026-03-18 12:16:22'),
(58, 1, 'Logged in', '2026-03-18 12:21:59'),
(59, 1, 'Deleted product: Cover', '2026-03-18 12:31:35'),
(60, 1, 'Deleted product: Charges 1', '2026-03-18 12:41:52'),
(61, 1, 'Deleted category ID #2', '2026-03-18 12:41:56'),
(62, 1, 'Added new category: Charges', '2026-03-18 12:42:02'),
(63, 1, 'Added new product: Charges 1', '2026-03-18 12:42:24'),
(64, 5, 'Logged in', '2026-03-18 12:53:52'),
(65, 4, 'Logged in', '2026-03-18 12:54:35'),
(66, 3, 'Logged in', '2026-03-18 12:54:51'),
(67, 3, 'Completed sale #5 - Total: $35,000.00', '2026-03-18 12:57:31'),
(68, 1, 'Logged in', '2026-03-18 14:06:12'),
(69, 1, 'Logged in', '2026-03-18 14:40:42'),
(70, 1, 'Logged in', '2026-03-18 14:45:15'),
(71, 1, 'Logged in', '2026-03-18 14:50:57'),
(72, 1, 'Logged in', '2026-03-18 15:30:06'),
(73, 1, 'Logged in', '2026-03-23 09:25:52'),
(74, 3, 'Logged in', '2026-03-23 09:27:40'),
(75, 3, 'Completed sale #6 - Total: $7,000.00', '2026-03-23 09:29:36'),
(76, 1, 'Logged in', '2026-03-23 09:38:05'),
(77, 1, 'Logged in', '2026-03-24 08:46:51'),
(78, 3, 'Logged in', '2026-03-24 08:47:13'),
(79, 3, 'Processed return #1 for Charges 1 (Qty: 1) - Refund: $5,000.00', '2026-03-24 09:05:48'),
(80, 3, 'Processed return #2 for Cover (Qty: 2) - Refund: $10,000.00', '2026-03-24 09:06:44'),
(81, 1, 'Logged in', '2026-03-24 09:29:34'),
(82, 3, 'Logged in', '2026-03-24 09:35:14'),
(83, 1, 'Logged in', '2026-03-24 09:48:30'),
(84, 3, 'Logged in', '2026-03-24 10:02:53'),
(85, 2, 'Logged in', '2026-03-24 10:03:16'),
(86, 4, 'Logged in', '2026-03-24 10:08:48'),
(87, 5, 'Logged in', '2026-03-24 10:09:18'),
(88, 1, 'Logged in', '2026-03-24 10:14:07'),
(89, 5, 'Logged in', '2026-03-24 12:27:56'),
(90, 5, 'Recorded mobile money transaction (cash_in) - TSh 30,000 (mpesa), commission 500', '2026-03-24 14:29:37'),
(91, 5, 'Recorded payment - Kingamuzi (Azam TV): TSh 19,000, customer: James', '2026-03-24 14:30:20'),
(92, 1, 'Logged in', '2026-03-24 14:58:37'),
(93, 2, 'Logged in', '2026-03-24 15:15:17'),
(94, 5, 'Logged in', '2026-03-25 07:00:36'),
(95, 5, 'Recorded payment - Kingamuzi (Azam TV): TSh 28,000, customer: James', '2026-03-25 07:28:14'),
(96, 1, 'Logged in', '2026-03-25 07:30:50'),
(97, 2, 'Logged in', '2026-03-25 07:33:08'),
(98, 1, 'Logged in', '2026-03-25 08:02:53'),
(99, 3, 'Logged in', '2026-03-25 08:19:06'),
(100, 5, 'Logged in', '2026-03-25 08:27:22'),
(101, 5, 'Recorded expense: Food - TSh 2,000', '2026-03-25 08:28:44'),
(102, 4, 'Logged in', '2026-03-25 08:35:45'),
(103, 2, 'Logged in', '2026-03-25 08:36:00'),
(104, 1, 'Logged in', '2026-03-25 08:49:26'),
(105, 5, 'Logged in', '2026-03-25 08:50:10'),
(106, 3, 'Logged in', '2026-03-25 08:52:57'),
(107, 2, 'Logged in', '2026-03-25 08:53:19'),
(108, 1, 'Logged in', '2026-03-25 09:06:19'),
(109, 5, 'Logged in', '2026-03-25 13:12:22'),
(110, 1, 'Logged in', '2026-03-25 14:03:40'),
(111, 1, 'Logged in', '2026-03-27 07:15:53'),
(112, 1, 'Created new expense category: Wi-Fi', '2026-03-27 07:16:50'),
(113, 1, 'Recorded expense: Office Wi-Fi expenses - TSh 55,000', '2026-03-27 07:17:37'),
(114, 5, 'Logged in', '2026-03-27 07:18:05'),
(115, 5, 'Recorded mobile money transaction (cash_out) - TSh 200,000 (bank_agency), commission 2,000', '2026-03-27 07:29:48'),
(116, 1, 'Logged in', '2026-03-27 07:34:01'),
(117, 5, 'Logged in', '2026-03-27 09:09:46'),
(118, 1, 'Logged in', '2026-03-27 09:10:30'),
(119, 1, 'Logged in', '2026-03-27 10:48:27'),
(120, 2, 'Logged in', '2026-03-28 13:44:09'),
(121, 1, 'Logged in', '2026-03-28 13:47:17'),
(122, 5, 'Logged in', '2026-03-28 13:47:59'),
(123, 4, 'Logged in', '2026-03-28 13:50:02'),
(124, 3, 'Logged in', '2026-03-28 13:51:10'),
(125, 4, 'Logged in', '2026-03-30 09:55:30'),
(126, 3, 'Logged in', '2026-03-30 09:57:18'),
(127, 5, 'Logged in', '2026-03-30 09:59:37'),
(128, 4, 'Logged in', '2026-03-30 10:00:42'),
(129, 5, 'Logged in', '2026-03-30 10:03:47'),
(130, 1, 'Logged in', '2026-03-30 10:26:01'),
(131, 1, 'Deleted expense: Office Wi-Fi expenses', '2026-03-30 10:27:49'),
(132, 1, 'Deleted expense category: Wi-Fi', '2026-03-30 10:27:55'),
(133, 1, 'Created new expense category: Wi-Fi', '2026-03-30 10:28:04'),
(134, 5, 'Logged in', '2026-03-30 14:55:25'),
(135, 1, 'Logged in', '2026-03-30 14:55:45'),
(136, 1, 'Updated Money Agent balances permissions for 0 agents', '2026-03-30 15:57:41'),
(137, 1, 'Updated Money Agent balances permissions for 1 agents', '2026-03-30 15:57:56'),
(138, 5, 'Logged in', '2026-03-30 15:58:14'),
(139, 5, 'Updated mobile money opening balances (cash + float per provider)', '2026-03-30 16:02:03'),
(140, 1, 'Logged in', '2026-03-30 16:02:14'),
(141, 1, 'Updated Money Agent balances permissions for 1 agents', '2026-03-30 16:02:29'),
(142, 5, 'Logged in', '2026-03-30 16:02:38'),
(143, 1, 'Logged in', '2026-03-30 16:04:41'),
(144, 1, 'Money Agent Balances - No changes made', '2026-03-30 16:05:09'),
(145, 1, 'Money Agent Balances - No changes made', '2026-03-30 16:05:11'),
(146, 1, 'Money Agent Balances - Revoked access from 1 agent(s)', '2026-03-30 16:05:27'),
(147, 5, 'Logged in', '2026-03-30 16:05:38'),
(148, 4, 'Logged in', '2026-03-30 16:05:55');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `sign_in_time` datetime NOT NULL,
  `sign_out_time` datetime DEFAULT NULL,
  `date` date NOT NULL,
  `ip_address` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `sign_in_time`, `sign_out_time`, `date`, `ip_address`) VALUES
(1, 3, '2026-03-24 12:48:16', NULL, '2026-03-24', '::1'),
(2, 5, '2026-03-24 13:10:21', NULL, '2026-03-24', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `balance_permissions`
--

CREATE TABLE `balance_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_view_balances` tinyint(1) DEFAULT 0,
  `can_manage_balances` tinyint(1) DEFAULT 0,
  `granted_by` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `balance_permissions`
--

INSERT INTO `balance_permissions` (`id`, `user_id`, `can_view_balances`, `can_manage_balances`, `granted_by`, `granted_at`, `expires_at`) VALUES
(1, 1, 1, 1, 1, '2026-03-27 11:27:13', NULL),
(2, 2, 1, 1, 2, '2026-03-27 11:27:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(3, 'Charges'),
(1, 'Covers');

-- --------------------------------------------------------

--
-- Table structure for table `client_orders`
--

CREATE TABLE `client_orders` (
  `id` int(11) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `client_phone` varchar(50) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `product_description` text DEFAULT NULL,
  `region` varchar(100) NOT NULL,
  `order_type` enum('catalog','custom') DEFAULT 'catalog',
  `status` enum('pending','approved','paid','shipped','delivered','cancelled') DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `agency_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_payable` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_orders`
--

INSERT INTO `client_orders` (`id`, `client_name`, `client_email`, `client_phone`, `product_id`, `product_name`, `product_description`, `region`, `order_type`, `status`, `amount`, `agency_fee`, `total_payable`, `created_at`) VALUES
(1, 'John', NULL, '0710726602', 1, 'Cover', '', 'Singida', 'catalog', 'approved', 5000.00, 0.00, 5000.00, '2026-03-18 14:05:54');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','chairman','manager','money_agent') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-03-17 15:06:31'),
(2, 'Sylvester Ndallah', '$2y$10$Nq6alocjOVwPFAu5voVYQeaPdgYJoRKH2NDPNh4n1C3LIFjagxl3G', 'chairman', '2026-03-17 17:51:27'),
(3, 'Irene', '$2y$10$ZbY4kgLcfr6.D/XciXp2POgcWyVjVhhHncvWraafAGM602rrZHSDy', 'staff', '2026-03-17 19:37:14'),
(4, 'Jackson', '$2y$10$CxS8XzjGyQbSP8LF6SwV7eV5BaIvtcrB5vm/Uvy9bNnFyCLXwBgrC', 'manager', '2026-03-18 05:57:06'),
(5, 'Angel', '$2y$10$G30IWOMaOm3PeVQHC0H52.FWcNrQMORgIQZQ.IQttETdvn0ZteIy2', 'money_agent', '2026-03-18 05:57:17');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_category_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `recorded_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_category_id`, `description`, `amount`, `expense_date`, `recorded_by`, `notes`, `created_at`) VALUES
(1, 2, 'Food', 2000.00, '2026-03-25', 5, '', '2026-03-25 08:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Rent', 'Monthly rent payments for office space', '2026-03-25 08:12:06'),
(2, 'Food', 'Food and refreshments for staff and meetings', '2026-03-25 08:12:06'),
(3, 'Transport', 'Transportation fees and travel expenses', '2026-03-25 08:12:06'),
(4, 'Website Management', 'Website hosting, domain, and maintenance costs', '2026-03-25 08:12:06'),
(5, 'Offerings', 'Business offerings and charitable contributions', '2026-03-25 08:12:06'),
(6, 'Staff Expenses', 'Staff-related expenses and allowances', '2026-03-25 08:12:06'),
(7, 'Utilities', 'Electricity, water, and other utility bills', '2026-03-25 08:12:06'),
(8, 'Office Supplies', 'Stationery, equipment, and office supplies', '2026-03-25 08:12:06'),
(9, 'Marketing', 'Advertising and promotional expenses', '2026-03-25 08:12:06'),
(10, 'Other', 'Miscellaneous expenses not covered by other categories', '2026-03-25 08:12:06'),
(12, 'Wi-Fi', 'Office Wi-Fi expenses every month', '2026-03-30 10:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `money_agent_permissions`
--

CREATE TABLE `money_agent_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_view_balances` tinyint(1) DEFAULT 0,
  `can_manage_balances` tinyint(1) DEFAULT 0,
  `granted_by` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `money_agent_permissions`
--

INSERT INTO `money_agent_permissions` (`id`, `user_id`, `can_view_balances`, `can_manage_balances`, `granted_by`, `granted_at`, `updated_at`) VALUES
(1, 5, 0, 0, 1, '2026-03-27 10:59:52', '2026-03-27 10:59:52');

-- --------------------------------------------------------

--
-- Table structure for table `money_cash_opening`
--

CREATE TABLE `money_cash_opening` (
  `id` tinyint(4) NOT NULL,
  `opening_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `money_cash_opening`
--

INSERT INTO `money_cash_opening` (`id`, `opening_cash`, `updated_at`) VALUES
(1, 0.00, '2026-03-17 15:06:31');

-- --------------------------------------------------------

--
-- Table structure for table `money_daily_closing`
--

CREATE TABLE `money_daily_closing` (
  `id` int(11) NOT NULL,
  `closing_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `expected_cash` decimal(12,2) NOT NULL,
  `counted_cash` decimal(12,2) NOT NULL,
  `variance_cash` decimal(12,2) NOT NULL,
  `expected_float_mpesa` decimal(12,2) NOT NULL,
  `counted_float_mpesa` decimal(12,2) NOT NULL,
  `variance_float_mpesa` decimal(12,2) NOT NULL,
  `expected_float_tigopesa` decimal(12,2) NOT NULL,
  `expected_float_mixx_by_yass` decimal(15,2) DEFAULT 0.00,
  `counted_float_tigopesa` decimal(12,2) NOT NULL,
  `counted_float_mixx_by_yass` decimal(15,2) DEFAULT 0.00,
  `variance_float_tigopesa` decimal(12,2) NOT NULL,
  `variance_float_mixx_by_yass` decimal(15,2) DEFAULT 0.00,
  `expected_float_airtelmoney` decimal(12,2) NOT NULL,
  `counted_float_airtelmoney` decimal(12,2) NOT NULL,
  `variance_float_airtelmoney` decimal(12,2) NOT NULL,
  `expected_float_halopesa` decimal(12,2) NOT NULL,
  `counted_float_halopesa` decimal(12,2) NOT NULL,
  `variance_float_halopesa` decimal(12,2) NOT NULL,
  `expected_float_other` decimal(12,2) NOT NULL,
  `counted_float_other` decimal(12,2) NOT NULL,
  `variance_float_other` decimal(12,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expected_float_azam_pesa` decimal(15,2) DEFAULT 0.00,
  `counted_float_azam_pesa` decimal(15,2) DEFAULT 0.00,
  `variance_float_azam_pesa` decimal(15,2) DEFAULT 0.00,
  `expected_float_bank_agency` decimal(15,2) DEFAULT 0.00,
  `counted_float_bank_agency` decimal(15,2) DEFAULT 0.00,
  `variance_float_bank_agency` decimal(15,2) DEFAULT 0.00,
  `expected_float_kingamuzi` decimal(15,2) DEFAULT 0.00,
  `counted_float_kingamuzi` decimal(15,2) DEFAULT 0.00,
  `variance_float_kingamuzi` decimal(15,2) DEFAULT 0.00,
  `expected_float_government` decimal(15,2) DEFAULT 0.00,
  `counted_float_government` decimal(15,2) DEFAULT 0.00,
  `variance_float_government` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `money_float_opening`
--

CREATE TABLE `money_float_opening` (
  `provider` enum('mpesa','tigopesa','airtelmoney','halopesa','other') NOT NULL,
  `opening_float` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `money_float_opening`
--

INSERT INTO `money_float_opening` (`provider`, `opening_float`, `updated_at`) VALUES
('', 0.00, '2026-03-24 12:47:36'),
('mpesa', 0.00, '2026-03-17 15:06:31'),
('tigopesa', 0.00, '2026-03-17 15:06:31'),
('airtelmoney', 0.00, '2026-03-17 15:06:31'),
('halopesa', 0.00, '2026-03-17 15:06:31'),
('other', 0.00, '2026-03-17 15:06:31');

-- --------------------------------------------------------

--
-- Table structure for table `money_transactions`
--

CREATE TABLE `money_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tx_type` enum('cash_in','cash_out') NOT NULL,
  `provider` enum('mpesa','tigopesa','airtelmoney','halopesa','kingamuzi','government','other') NOT NULL DEFAULT 'other',
  `amount` decimal(10,2) NOT NULL,
  `commission` decimal(10,2) NOT NULL DEFAULT 0.00,
  `customer_msisdn` varchar(20) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `payment_service` varchar(50) DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `tx_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `money_transactions`
--

INSERT INTO `money_transactions` (`id`, `user_id`, `tx_type`, `provider`, `amount`, `commission`, `customer_msisdn`, `customer_name`, `payment_service`, `bank_name`, `reference`, `notes`, `tx_time`) VALUES
(1, 5, 'cash_in', 'mpesa', 30000.00, 500.00, '0710726602', NULL, NULL, NULL, '', '', '2026-03-24 14:29:37'),
(2, 5, 'cash_out', 'kingamuzi', 19000.00, 1000.00, '', 'James', 'azam_tv', NULL, '', '', '2026-03-24 14:30:20'),
(3, 5, 'cash_in', 'kingamuzi', 50000.00, 5000.00, '', 'James', 'dstv', NULL, '', '', '2026-03-25 07:17:34'),
(4, 5, 'cash_in', 'kingamuzi', 28000.00, 2000.00, '', 'James', 'azam_tv', NULL, '', '', '2026-03-25 07:28:14'),
(5, 5, 'cash_out', '', 200000.00, 2000.00, '', NULL, NULL, 'Selcom', '', '', '2026-03-27 07:29:48');

-- --------------------------------------------------------

--
-- Table structure for table `official_profiles`
--

CREATE TABLE `official_profiles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `official_profiles`
--

INSERT INTO `official_profiles` (`id`, `name`, `position`, `image_path`, `bio`, `created_at`) VALUES
(3, 'Sylvester Ndallah', 'Chairman', NULL, 'Tech visionary leading the strategic growth of Maison Tech. Phone: +255767207115, Email: sylvesterpius17@gmail.com', '2026-03-18 13:41:56'),
(4, 'James Ndallah', 'CEO', 'uploads/officials/1774437764_cropped_image.jpg', 'Driving innovation and operational excellence in technology sourcing. Phone: +255710726602, Email: jamesndallah@gmail.com', '2026-03-18 13:41:56');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `feature_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `feature_name`, `description`, `is_enabled`, `created_at`, `updated_at`) VALUES
(1, 'money_agent_balances', 'Allow money agents to access balances page', 0, '2026-03-27 10:30:15', '2026-03-27 10:30:15'),
(2, 'money_agent_reports', 'Allow money agents to view reports', 0, '2026-03-27 10:30:15', '2026-03-27 10:30:15'),
(3, 'money_agent_export', 'Allow money agents to export data', 0, '2026-03-27 10:30:15', '2026-03-27 10:30:15');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `cost_price`, `price`, `quantity`, `category_id`, `description`, `image_url`) VALUES
(1, 'Cover', 2000.00, 5000.00, 180, 1, 'High-quality technology solution from Maison Tech.', ''),
(4, 'Charges 1', 2000.00, 7000.00, 15, 3, 'High-quality technology solution from Maison Tech.', '');

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `return_reason` varchar(255) NOT NULL,
  `item_condition` enum('resellable','damaged') DEFAULT 'resellable',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `processed_by` int(11) NOT NULL,
  `return_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returns`
--

INSERT INTO `returns` (`id`, `sale_id`, `product_id`, `quantity`, `return_reason`, `item_condition`, `refund_amount`, `processed_by`, `return_date`) VALUES
(1, NULL, 4, 1, 'Wrong item', 'resellable', 5000.00, 3, '2026-03-24 09:05:48'),
(2, NULL, 1, 2, 'defective', 'damaged', 10000.00, 3, '2026-03-24 09:06:44');

-- --------------------------------------------------------

--
-- Table structure for table `salary_payments`
--

CREATE TABLE `salary_payments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_payments`
--

INSERT INTO `salary_payments` (`id`, `employee_id`, `amount`, `payment_date`, `payment_method`, `notes`, `processed_by`, `created_at`) VALUES
(1, 3, 120000.00, '2026-03-18', 'Cash', 'Monthly', 2, '2026-03-18 10:35:07'),
(2, 4, 200000.00, '2026-03-18', 'Bank Transfer', 'monthly salary', 1, '2026-03-18 10:48:03');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Mobile Money Wallet','Bank') NOT NULL DEFAULT 'Cash',
  `payment_provider` varchar(50) DEFAULT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `user_id`, `total_amount`, `payment_method`, `payment_provider`, `sale_date`) VALUES
(1, 3, 5000.00, 'Cash', NULL, '2026-03-17 19:38:02'),
(2, 3, 50000.00, 'Cash', NULL, '2026-03-17 20:00:02'),
(3, 3, 20000.00, 'Mobile Money Wallet', 'Mixx By Yass', '2026-03-17 20:21:42'),
(4, 3, 25000.00, 'Bank', 'NBC', '2026-03-17 20:23:56'),
(5, 3, 35000.00, 'Mobile Money Wallet', 'Selcom', '2026-03-18 12:57:31'),
(6, 3, 7000.00, 'Mobile Money Wallet', 'M-Pesa', '2026-03-23 09:29:36');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `price_per_unit`) VALUES
(1, 1, 1, 1, 5000.00),
(2, 2, 1, 10, 5000.00),
(3, 3, 1, 4, 5000.00),
(4, 4, 1, 5, 5000.00),
(5, 5, 4, 5, 7000.00),
(6, 6, 4, 1, 7000.00);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'company_ip', ''),
(2, 'money_agent_balances_permission', '');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `movement_type` varchar(50) NOT NULL,
  `movement_reason` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `quantity_change`, `movement_type`, `movement_reason`, `user_id`, `movement_date`) VALUES
(1, 1, 200, 'New Stock', NULL, 1, '2026-03-17 19:31:13'),
(3, 1, -1, 'Sale', NULL, 3, '2026-03-17 19:38:02'),
(4, 1, -10, 'Sale', NULL, 3, '2026-03-17 20:00:02'),
(5, 1, -4, 'Sale', NULL, 3, '2026-03-17 20:21:42'),
(6, 1, -5, 'Sale', NULL, 3, '2026-03-17 20:23:56'),
(8, 1, -5, 'Restock', NULL, 1, '2026-03-18 05:41:32'),
(9, 1, 5, 'Restock', NULL, 1, '2026-03-18 05:41:57'),
(10, 4, 20, 'New Stock', NULL, 1, '2026-03-18 12:42:24'),
(11, 4, -5, 'Sale', NULL, 3, '2026-03-18 12:57:31'),
(12, 4, -1, 'Sale', NULL, 3, '2026-03-23 09:29:36'),
(13, 4, 1, 'Return', 'Return #1 - Wrong item', 3, '2026-03-24 09:05:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_us`
--
ALTER TABLE `about_us`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `balance_permissions`
--
ALTER TABLE `balance_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_permission` (`user_id`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `client_orders`
--
ALTER TABLE `client_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_category_id` (`expense_category_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `money_agent_permissions`
--
ALTER TABLE `money_agent_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_permissions` (`user_id`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `money_cash_opening`
--
ALTER TABLE `money_cash_opening`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `money_daily_closing`
--
ALTER TABLE `money_daily_closing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_closing_date` (`closing_date`);

--
-- Indexes for table `money_float_opening`
--
ALTER TABLE `money_float_opening`
  ADD PRIMARY KEY (`provider`);

--
-- Indexes for table `money_transactions`
--
ALTER TABLE `money_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `official_profiles`
--
ALTER TABLE `official_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `feature_name` (`feature_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `salary_payments`
--
ALTER TABLE `salary_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_us`
--
ALTER TABLE `about_us`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `balance_permissions`
--
ALTER TABLE `balance_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `client_orders`
--
ALTER TABLE `client_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `money_agent_permissions`
--
ALTER TABLE `money_agent_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `money_daily_closing`
--
ALTER TABLE `money_daily_closing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `money_transactions`
--
ALTER TABLE `money_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `official_profiles`
--
ALTER TABLE `official_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `salary_payments`
--
ALTER TABLE `salary_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `balance_permissions`
--
ALTER TABLE `balance_permissions`
  ADD CONSTRAINT `balance_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `balance_permissions_ibfk_2` FOREIGN KEY (`granted_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_orders`
--
ALTER TABLE `client_orders`
  ADD CONSTRAINT `client_orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `employees` (`id`);

--
-- Constraints for table `money_agent_permissions`
--
ALTER TABLE `money_agent_permissions`
  ADD CONSTRAINT `money_agent_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `money_agent_permissions_ibfk_2` FOREIGN KEY (`granted_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `money_transactions`
--
ALTER TABLE `money_transactions`
  ADD CONSTRAINT `money_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salary_payments`
--
ALTER TABLE `salary_payments`
  ADD CONSTRAINT `salary_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salary_payments_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;






-- Create expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_category_id INT,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    recorded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (recorded_by) REFERENCES employees(id)
);

-- Create expense_categories table
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default expense categories
INSERT INTO expense_categories (name, description) VALUES 
('Rent', 'Monthly rent payments for office space'),
('Food', 'Food and refreshments for staff and meetings'),
('Transport', 'Transportation fees and travel expenses'),
('Website Management', 'Website hosting, domain, and maintenance costs'),
('Offerings', 'Business offerings and charitable contributions'),
('Staff Expenses', 'Staff-related expenses and allowances'),
('Utilities', 'Electricity, water, and other utility bills'),
('Office Supplies', 'Stationery, equipment, and office supplies'),
('Marketing', 'Advertising and promotional expenses'),
('Other', 'Miscellaneous expenses not covered by other categories');






CREATE DATABASE IF NOT EXISTS maison_tech;

USE maison_tech;

-- Drop existing tables if they exist to start fresh
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS
    money_daily_closing,
    money_transactions,
    money_float_opening,
    money_cash_opening,
    sale_items,
    sales,
    stock_movements,
    activity_logs,
    products,
    employees,
    categories;
SET FOREIGN_KEY_CHECKS = 1;

-- Employees Table (used for login and activity)
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'chairman', 'manager', 'money_agent') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin user (password: password)
INSERT INTO employees (username, password, role) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Product Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    category_id INT,
    image_url VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Stock Movements
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity_change INT NOT NULL, -- Positive for IN, Negative for OUT
    movement_type VARCHAR(50) NOT NULL, -- e.g., 'New Stock', 'Sale', 'Correction'
    movement_reason VARCHAR(255) DEFAULT NULL,
    user_id INT NOT NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Sales Table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'Mobile Money Wallet', 'Bank') NOT NULL DEFAULT 'Cash',
    payment_provider VARCHAR(50) DEFAULT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Sale Items
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Employee Activity Logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Salary Payments Table
CREATE TABLE salary_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    notes TEXT,
    processed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES employees(id) ON DELETE CASCADE
);

-- Mobile Money Transactions (Tanzania)
CREATE TABLE money_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tx_type ENUM('cash_in', 'cash_out') NOT NULL,
    provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'bank_agency', 'other') NOT NULL DEFAULT 'other',
    amount DECIMAL(10, 2) NOT NULL,
    commission DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    customer_msisdn VARCHAR(20),
    customer_name VARCHAR(100),
    payment_service VARCHAR(50),
    bank_name VARCHAR(50),
    reference VARCHAR(80),
    notes VARCHAR(255),
    tx_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Mobile Money Opening Balances
CREATE TABLE money_cash_opening (
    id TINYINT PRIMARY KEY,
    opening_cash DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO money_cash_opening (id, opening_cash) VALUES (1, 0.00);

CREATE TABLE money_float_opening (
    provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'bank_agency', 'other') PRIMARY KEY,
    opening_float DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO money_float_opening (provider, opening_float) VALUES
('mpesa',0.00),('mixx_by_yass',0.00),('airtelmoney',0.00),('halopesa',0.00),('kingamuzi',0.00),('government',0.00),('bank_agency',0.00),('other',0.00);

-- Banks for Bank Agency transactions
CREATE TABLE banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(50) NOT NULL UNIQUE,
    bank_code VARCHAR(10) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default banks
INSERT INTO banks (bank_name, bank_code) VALUES 
('CRDB', 'CRDB'),
('NMB', 'NMB'),
('NBC', 'NBC'),
('Selcom', 'SELC'),
('TCB', 'TCB');

-- End of day reconciliation (daily closing)
CREATE TABLE money_daily_closing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    closing_date DATE NOT NULL,
    user_id INT NOT NULL,
    expected_cash DECIMAL(12, 2) NOT NULL,
    counted_cash DECIMAL(12, 2) NOT NULL,
    variance_cash DECIMAL(12, 2) NOT NULL,
    expected_float_mpesa DECIMAL(12, 2) NOT NULL,
    counted_float_mpesa DECIMAL(12, 2) NOT NULL,
    variance_float_mpesa DECIMAL(12, 2) NOT NULL,
    expected_float_mixx_by_yass DECIMAL(12, 2) NOT NULL,
    counted_float_mixx_by_yass DECIMAL(12, 2) NOT NULL,
    variance_float_mixx_by_yass DECIMAL(12, 2) NOT NULL,
    expected_float_kingamuzi DECIMAL(12, 2) NOT NULL,
    counted_float_kingamuzi DECIMAL(12, 2) NOT NULL,
    variance_float_kingamuzi DECIMAL(12, 2) NOT NULL,
    expected_float_government DECIMAL(12, 2) NOT NULL,
    counted_float_government DECIMAL(12, 2) NOT NULL,
    variance_float_government DECIMAL(12, 2) NOT NULL,
    expected_float_airtelmoney DECIMAL(12, 2) NOT NULL,
    counted_float_airtelmoney DECIMAL(12, 2) NOT NULL,
    variance_float_airtelmoney DECIMAL(12, 2) NOT NULL,
    expected_float_halopesa DECIMAL(12, 2) NOT NULL,
    counted_float_halopesa DECIMAL(12, 2) NOT NULL,
    variance_float_halopesa DECIMAL(12, 2) NOT NULL,
    expected_float_bank_agency DECIMAL(12, 2) NOT NULL,
    counted_float_bank_agency DECIMAL(12, 2) NOT NULL,
    variance_float_bank_agency DECIMAL(12, 2) NOT NULL,
    expected_float_other DECIMAL(12, 2) NOT NULL,
    counted_float_other DECIMAL(12, 2) NOT NULL,
    variance_float_other DECIMAL(12, 2) NOT NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_closing_date (closing_date)
);



-- Update database schema to support Bill Payments (Kingamuzi TV and Government payments)

-- Add kingamuzi and government to the provider ENUM in money_transactions table
ALTER TABLE money_transactions 
MODIFY COLUMN provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other') NOT NULL DEFAULT 'other';

-- Add customer_name field for bill payments
ALTER TABLE money_transactions 
ADD COLUMN customer_name VARCHAR(100) AFTER customer_msisdn;

-- Update money_float_opening table to include the new providers
ALTER TABLE money_float_opening 
MODIFY COLUMN provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other') PRIMARY KEY;

-- Insert opening balances for the new providers (0.00 as default)
INSERT IGNORE INTO money_float_opening (provider, opening_float) VALUES 
('kingamuzi', 0.00),
('government', 0.00);

-- Update money_daily_closing table to include the new providers
ALTER TABLE money_daily_closing 
ADD COLUMN expected_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER variance_float_mixx_by_yass,
ADD COLUMN counted_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER expected_float_kingamuzi,
ADD COLUMN variance_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER counted_float_kingamuzi,
ADD COLUMN expected_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER variance_float_kingamuzi,
ADD COLUMN counted_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER expected_float_government,
ADD COLUMN variance_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER counted_float_government;
