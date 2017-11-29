-- MySQL dump 10.14  Distrib 5.5.56-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: rate_stat
-- ------------------------------------------------------
-- Server version	5.5.56-MariaDB

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
-- Table structure for table `ad_statistics`
--

DROP TABLE IF EXISTS `ad_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ad_id` int(11) NOT NULL COMMENT 'Реклама',
  `date` date NOT NULL COMMENT 'Дата',
  `views` int(11) NOT NULL DEFAULT '0' COMMENT 'Показов',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=401 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ads`
--

DROP TABLE IF EXISTS `ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uuid` varchar(64) NOT NULL COMMENT 'UUID',
  `name` varchar(255) NOT NULL COMMENT 'Название',
  `position` tinyint(4) NOT NULL COMMENT 'Позиция',
  `url` varchar(255) DEFAULT NULL COMMENT 'URL',
  `active` tinyint(1) DEFAULT '1' COMMENT 'Активен',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ads_categories`
--

DROP TABLE IF EXISTS `ads_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ads_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ad_id` int(11) NOT NULL COMMENT 'Реклама',
  `category_id` int(11) NOT NULL COMMENT 'Категория',
  PRIMARY KEY (`id`),
  KEY `ads_categories_ads_id_fk` (`ad_id`),
  KEY `ads_categories_categories_id_fk` (`category_id`),
  CONSTRAINT `ads_categories_ads_id_fk` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ads_categories_categories_id_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_key_statistics`
--

DROP TABLE IF EXISTS `api_key_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_key_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `api_key_id` int(11) NOT NULL COMMENT 'Ключ',
  `date` date NOT NULL COMMENT 'Дата',
  `quota` int(11) NOT NULL COMMENT 'Квота',
  PRIMARY KEY (`id`),
  KEY `api_key_id` (`api_key_id`),
  CONSTRAINT `api_key_statistics_ibfk_1` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=584 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_keys`
--

DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `key` varchar(255) NOT NULL COMMENT 'Ключ',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `load_last_days` int(11) DEFAULT NULL COMMENT 'Загружать видео за период',
  `tags` text COMMENT 'Тэги',
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_code_uindex` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='Рубрики';
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
  `load_last_days` int(11) DEFAULT NULL COMMENT 'Загружать видео за период',
  `subscribers_count` int(11) DEFAULT '0' COMMENT 'Количество подписчиков',
  PRIMARY KEY (`id`),
  KEY `channels_categories_id_fk` (`category_id`),
  CONSTRAINT `channels_categories_id_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2144 DEFAULT CHARSET=utf8 COMMENT='Каналы';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `position_statistics`
--

DROP TABLE IF EXISTS `position_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `position_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `position_id` int(11) NOT NULL COMMENT 'Позиция',
  `date` date NOT NULL COMMENT 'Дата',
  `views` int(11) NOT NULL DEFAULT '0' COMMENT 'Просмотров',
  PRIMARY KEY (`id`),
  KEY `position_id` (`position_id`),
  CONSTRAINT `position_statistics_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `video_id` int(11) NOT NULL COMMENT 'Видео',
  `position` smallint(8) NOT NULL COMMENT 'Позиция',
  PRIMARY KEY (`id`),
  KEY `video_id` (`video_id`),
  KEY `position` (`position`),
  CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
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
  `memory` decimal(8,2) DEFAULT NULL COMMENT 'Память (МБ)',
  PRIMARY KEY (`id`),
  KEY `profiling_code_datetime_index` (`code`,`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=109427 DEFAULT CHARSET=utf8 COMMENT='Данные профайлинга';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_ip`
--

DROP TABLE IF EXISTS `security_ip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_ip` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ip` varchar(32) NOT NULL COMMENT 'IP-адрес (маска)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `security_ip_ip_uindex` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `id` char(40) NOT NULL,
  `expire` int(11) DEFAULT NULL,
  `data` blob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `slave_profiling`
--

DROP TABLE IF EXISTS `slave_profiling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slave_profiling` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `slave_id` int(11) NOT NULL COMMENT 'Сервер',
  `datetime` datetime NOT NULL COMMENT 'Время',
  `duration` decimal(10,2) NOT NULL COMMENT 'Время выполнения',
  `size` decimal(8,2) NOT NULL COMMENT 'Объем данных',
  `count` int(11) NOT NULL COMMENT 'Количество элементов',
  `type` tinyint(4) NOT NULL COMMENT 'Тип запроса',
  `method` varchar(40) NOT NULL COMMENT 'Путь',
  `parts` varchar(255) NOT NULL COMMENT 'Части',
  PRIMARY KEY (`id`),
  KEY `slave_id` (`slave_id`),
  CONSTRAINT `slave_profiling_ibfk_1` FOREIGN KEY (`slave_id`) REFERENCES `slaves` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=225515 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `slaves`
--

DROP TABLE IF EXISTS `slaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ip` varchar(15) NOT NULL COMMENT 'IP-адрес',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
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
  `viewers` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_day_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=116053628 DEFAULT CHARSET=utf8 COMMENT='Статистика за день';
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
  `viewers` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_hour_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=470685080 DEFAULT CHARSET=utf8 COMMENT='Статистика за час';
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
  `viewers` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_minute_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=473662310 DEFAULT CHARSET=utf8 COMMENT='Статистика за 10 минут';
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
  `viewers` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_month_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=6135060 DEFAULT CHARSET=utf8 COMMENT='Статистика за месяц';
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
  `viewers` int(11) DEFAULT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `statistics_week_datetime_index` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=20585918 DEFAULT CHARSET=utf8 COMMENT='Статистика за неделю';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `video_id` int(11) NOT NULL COMMENT 'Видео',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT 'Тип',
  `text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_id_uindex` (`id`),
  KEY `video_id` (`video_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8324407 DEFAULT CHARSET=utf8 COMMENT='Тэги';
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
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Предпросмотр',
  `channel_id` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Активно',
  PRIMARY KEY (`id`),
  KEY `videos_channels_id_fk` (`channel_id`),
  CONSTRAINT `videos_channels_id_fk` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=459229 DEFAULT CHARSET=utf8 COMMENT='Видео';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-11-29 17:18:23
