-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 21, 2026 at 04:46 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_relawan`
--

-- --------------------------------------------------------

--
-- Table structure for table `dapil`
--

CREATE TABLE `dapil` (
  `id` int NOT NULL,
  `daerah_pemilihan` varchar(225) NOT NULL,
  `provinsi` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `kab_kota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dapil`
--

INSERT INTO `dapil` (`id`, `daerah_pemilihan`, `provinsi`, `kab_kota`) VALUES
(2, 'kalsel I', 'KALIMANTAN SELATAN', '[\"KABUPATEN BANJAR\",\"KABUPATEN BARITO KUALA\",\"KABUPATEN TAPIN\",\"KABUPATEN HULU SUNGAI SELATAN\",\"KABUPATEN HULU SUNGAI TENGAH\",\"KABUPATEN HULU SUNGAI UTARA\",\"KABUPATEN TABALONG\",\"KABUPATEN BALANGAN\"]');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dapil`
--
ALTER TABLE `dapil`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dapil`
--
ALTER TABLE `dapil`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
