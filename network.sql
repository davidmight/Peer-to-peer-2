-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 03, 2012 at 12:13 PM
-- Server version: 5.5.16
-- PHP Version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `network`
--

-- --------------------------------------------------------

--
-- Table structure for table `leechers`
--

CREATE TABLE IF NOT EXISTS `leechers` (
  `leechid` int(11) NOT NULL AUTO_INCREMENT,
  `peerid` int(11) NOT NULL,
  `torrent_name` varchar(100) NOT NULL,
  `date_started` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`leechid`),
  KEY `peerid` (`peerid`),
  KEY `torrent_name` (`torrent_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `peers`
--

CREATE TABLE IF NOT EXISTS `peers` (
  `peerid` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL,
  `port` int(4) NOT NULL,
  `public_key` longtext NOT NULL,
  `date_joined` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`peerid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `peers`
--

INSERT INTO `peers` (`peerid`, `ip`, `port`, `public_key`, `date_joined`) VALUES
(1, '192.168.1.254', 9000, 'ABCD', '2012-02-16 13:10:23'),
(2, '192.168.1.253', 9001, 'ABCE', '2012-03-19 15:30:54'),
(3, '192.168.1.252', 9002, 'ABCF', '2012-03-19 15:37:33');

-- --------------------------------------------------------

--
-- Table structure for table `seeds`
--

CREATE TABLE IF NOT EXISTS `seeds` (
  `seedid` int(11) NOT NULL DEFAULT '0',
  `peerid` int(11) NOT NULL,
  `torrent_name` varchar(100) NOT NULL,
  `date_joined` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seedid`),
  KEY `peerid` (`peerid`),
  KEY `torrent_name` (`torrent_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `seeds`
--

INSERT INTO `seeds` (`seedid`, `peerid`, `torrent_name`, `date_joined`) VALUES
(1, 1, 'windows', '2012-03-19 15:02:49'),
(2, 2, 'osx', '2012-03-19 15:31:36'),
(3, 3, 'windows', '2012-03-19 15:38:00');

-- --------------------------------------------------------

--
-- Table structure for table `torrents`
--

CREATE TABLE IF NOT EXISTS `torrents` (
  `torrent_name` varchar(100) NOT NULL,
  `size_MB` int(11) NOT NULL,
  PRIMARY KEY (`torrent_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `torrents`
--

INSERT INTO `torrents` (`torrent_name`, `size_MB`) VALUES
('linux', 2000),
('osx', 7000),
('windows', 7000);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `leechers`
--
ALTER TABLE `leechers`
  ADD CONSTRAINT `leechers_ibfk_1` FOREIGN KEY (`peerid`) REFERENCES `peers` (`peerid`),
  ADD CONSTRAINT `leechers_ibfk_2` FOREIGN KEY (`torrent_name`) REFERENCES `torrents` (`torrent_name`);

--
-- Constraints for table `seeds`
--
ALTER TABLE `seeds`
  ADD CONSTRAINT `seeds_ibfk_1` FOREIGN KEY (`peerid`) REFERENCES `peers` (`peerid`),
  ADD CONSTRAINT `seeds_ibfk_2` FOREIGN KEY (`torrent_name`) REFERENCES `torrents` (`torrent_name`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
