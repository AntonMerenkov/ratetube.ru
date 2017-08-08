-- MySQL dump 10.14  Distrib 5.5.52-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: rate_stat
-- ------------------------------------------------------
-- Server version	5.5.52-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(255) NOT NULL COMMENT 'Наименование',
  `code` varchar(255) NOT NULL COMMENT 'Символьный код',
  `flush_timeframe` varchar(20) DEFAULT NULL COMMENT 'Период очистки',
  `flush_count` int(11) DEFAULT NULL COMMENT 'Минимальное количество просмотров',
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_code_uindex` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Рубрики';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `channels`
--

DROP TABLE IF EXISTS `channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(255) NOT NULL COMMENT 'Наименование',
  `url` text NOT NULL COMMENT 'URL канала',
  `channel_link` varchar(128) NOT NULL COMMENT 'ID канала',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Картинка канала',
  `category_id` int(11) NOT NULL COMMENT 'Рубрика',
  `flush_timeframe` varchar(20) DEFAULT NULL COMMENT 'Период очистки',
  `flush_count` int(11) DEFAULT NULL COMMENT 'Минимальное количество просмотров',
  PRIMARY KEY (`id`),
  KEY `channels_categories_id_fk` (`category_id`),
  CONSTRAINT `channels_categories_id_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='Каналы';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `profiling`
--

DROP TABLE IF EXISTS `profiling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profiling` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `datetime` datetime NOT NULL COMMENT 'Время',
  `code` varchar(32) NOT NULL COMMENT 'Код',
  `duration` decimal(10,2) NOT NULL COMMENT 'Время выполнения',
  PRIMARY KEY (`id`),
  KEY `profiling_code_datetime_index` (`code`,`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=76442 DEFAULT CHARSET=utf8 COMMENT='Данные профайлинга';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_day`
--

DROP TABLE IF EXISTS `statistics_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics_day` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `views` int(11) DEFAULT NULL,
  `likes` int(11) DEFAULT NULL,
  `dislikes` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_day_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=343427 DEFAULT CHARSET=utf8 COMMENT='Статистика за день';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_hour`
--

DROP TABLE IF EXISTS `statistics_hour`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics_hour` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `views` int(11) DEFAULT NULL,
  `likes` int(11) DEFAULT NULL,
  `dislikes` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_hour_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=1732156 DEFAULT CHARSET=utf8 COMMENT='Статистика за час';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_minute`
--

DROP TABLE IF EXISTS `statistics_minute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics_minute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `views` int(11) DEFAULT NULL,
  `likes` int(11) DEFAULT NULL,
  `dislikes` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_minute_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=4669439 DEFAULT CHARSET=utf8 COMMENT='Статистика за 10 минут';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_month`
--

DROP TABLE IF EXISTS `statistics_month`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics_month` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `views` int(11) DEFAULT NULL,
  `likes` int(11) DEFAULT NULL,
  `dislikes` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_month_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=27551 DEFAULT CHARSET=utf8 COMMENT='Статистика за месяц';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_week`
--

DROP TABLE IF EXISTS `statistics_week`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics_week` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `views` int(11) DEFAULT NULL,
  `likes` int(11) DEFAULT NULL,
  `dislikes` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_week_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=64368 DEFAULT CHARSET=utf8 COMMENT='Статистика за неделю';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `videos`
--

DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(255) NOT NULL COMMENT 'Наименование',
  `video_link` varchar(32) DEFAULT NULL COMMENT 'ID видео',
  `channel_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Активно',
  PRIMARY KEY (`id`),
  KEY `videos_channels_id_fk` (`channel_id`),
  CONSTRAINT `videos_channels_id_fk` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9261 DEFAULT CHARSET=utf8 COMMENT='Видео';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-08-08 12:33:15
