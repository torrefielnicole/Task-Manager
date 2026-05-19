-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2026 at 12:54 AM
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
-- Database: `todolist`
--

-- --------------------------------------------------------

--
-- Table structure for table `habits`
--

CREATE TABLE `habits` (
  `id` int(11) NOT NULL,
  `user` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `streak` int(11) DEFAULT 0,
  `done_today` tinyint(1) DEFAULT 0,
  `last_done` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habits`
--

INSERT INTO `habits` (`id`, `user`, `name`, `streak`, `done_today`, `last_done`, `created_at`) VALUES
(1, 'nico', 'drawing', 1, 1, '2026-05-20', '2026-05-19 22:24:45');

-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE `task` (
  `task_id` int(11) NOT NULL,
  `task_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `priority` int(11) DEFAULT 1,
  `category` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`task_id`, `task_name`, `description`, `due_date`, `status`, `priority`, `category`) VALUES
(1, 'project of finman', 'humana natawn ni', '2026-04-30', 'pending', 1, NULL),
(2, 'project system of sir russel', 'mahuman na unta', '2026-04-13', 'completed', 3, NULL),
(3, 'oral paper', 'done tomorrow', '2026-04-14', 'pending', 3, NULL),
(4, 'project of halohalo', 'lami', '2026-04-12', 'pending', 3, NULL),
(5, 'omygoodnessgracious', 'my twinbrother is a criminal', '0000-00-00', 'pending', 2, NULL),
(6, 'excel for sir sydney', 'wapa mahuman', '2026-04-20', 'pending', 3, 'project'),
(8, 'exam', 'study', '2026-06-02', 'pending', 1, 'academic'),
(9, 'excel for sir sydney', 'gsdfdgsdfggg', '2026-09-07', 'pending', 3, 'project'),
(10, 'fgxcvxfbdfb', 'dfbdfbdfcbc', '0000-00-00', 'completed', 3, 'project'),
(11, 'pag diet', 'yes', '2024-09-05', 'pending', 3, 'personal');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `created_at`) VALUES
(1, 'junjunbayot', '$2y$10$M6toW3tglyhNu0k4iFmJpuFPFSewqGX1pLApy.n/S8VT6LBhnT00u', '2026-04-11 14:35:49'),
(2, 'nico', '$2y$10$iLCN1SOTq/0JudKyYPloAeXHStMxdf9KC6MqGDhguPQ6sNIZVdQ.y', '2026-05-19 12:19:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `habits`
--
ALTER TABLE `habits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`task_id`);

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
-- AUTO_INCREMENT for table `habits`
--
ALTER TABLE `habits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task`
--
ALTER TABLE `task`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
