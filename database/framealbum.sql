
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `framealbum`
--

-- --------------------------------------------------------

--
-- Table structure for table `batch_stats`
--

DROP TABLE IF EXISTS `batch_stats`;
CREATE TABLE IF NOT EXISTS `batch_stats` (
  `batch_id` int(11) NOT NULL,
  `rundate` datetime NOT NULL,
  `wall_time` int(11) NOT NULL,
  `stats` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channel_types`
--

DROP TABLE IF EXISTS `channel_types`;
CREATE TABLE IF NOT EXISTS `channel_types` (
  `idchanneltypes` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `channel_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `channel_script` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `channel_icon_url` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `frame_icon_url` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `channel_category` enum('photo','text','news','weather','info') COLLATE utf8_unicode_ci DEFAULT 'photo',
  `active` enum('Y','N','T') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'T',
  `default_item_limit` int(8) NOT NULL DEFAULT '100',
  `channel_type_ttl` int(11) NOT NULL DEFAULT '60' COMMENT 'in minutes',
  PRIMARY KEY (`idchanneltypes`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_cache`
--

DROP TABLE IF EXISTS `flickr_cache`;
CREATE TABLE IF NOT EXISTS `flickr_cache` (
  `request` char(35) COLLATE utf8_unicode_ci NOT NULL,
  `response` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `expiration` datetime NOT NULL,
  KEY `request` (`request`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frames`
--

DROP TABLE IF EXISTS `frames`;
CREATE TABLE IF NOT EXISTS `frames` (
  `idframes` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `frame_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` smallint(8) unsigned DEFAULT NULL,
  `user_nickname` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y',
  `product_id` varchar(24) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL COMMENT 'When was this frame added to the system',
  `last_seen` datetime NOT NULL,
  `feed_ttl` int(11) NOT NULL DEFAULT '60' COMMENT 'in minutes',
  `feed_pin` int(6) DEFAULT NULL,
  `item_limit` smallint(6) DEFAULT NULL,
  `shuffle_items` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `security_key` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `activation_key` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idframes`),
  UNIQUE KEY `activation_key` (`activation_key`),
  KEY `owner_id` (`user_id`),
  KEY `frame_id` (`frame_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frame_channels`
--

DROP TABLE IF EXISTS `frame_channels`;
CREATE TABLE IF NOT EXISTS `frame_channels` (
  `frame_id` int(8) unsigned NOT NULL,
  `user_channel_id` smallint(8) unsigned NOT NULL,
  `active` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y',
  `attrib` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `item_limit` smallint(4) unsigned DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  KEY `frame_id` (`frame_id`,`user_channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frame_items`
--

DROP TABLE IF EXISTS `frame_items`;
CREATE TABLE IF NOT EXISTS `frame_items` (
  `frame_id` int(12) NOT NULL,
  `user_channel_id` smallint(12) NOT NULL,
  `item_id` int(12) NOT NULL,
  `feed_order` smallint(4) NOT NULL COMMENT 'NOT YET IMPLEMENTED',
  KEY `idframe_items` (`item_id`),
  KEY `frame_id` (`frame_id`,`feed_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grabber_stats`
--

DROP TABLE IF EXISTS `grabber_stats`;
CREATE TABLE IF NOT EXISTS `grabber_stats` (
  `channel_type_id` int(8) NOT NULL,
  `rundate` datetime NOT NULL,
  `wall_time` int(8) DEFAULT NULL COMMENT 'runtime in seconds',
  `stats` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  KEY `channel_type_id` (`channel_type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `iditems` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `link` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `category` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `user_channel_id` smallint(8) unsigned NOT NULL,
  `description` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pubDate` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `guid` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `media_content_url` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `media_thumbnail_url` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `media_content_duration` smallint(4) unsigned NOT NULL DEFAULT '10',
  PRIMARY KEY (`iditems`),
  KEY `channel_id` (`user_channel_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_ids`
--

DROP TABLE IF EXISTS `product_ids`;
CREATE TABLE IF NOT EXISTS `product_ids` (
  `idproduct` smallint(8) unsigned NOT NULL AUTO_INCREMENT,
  `productid` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `manuf` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `model` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hres` smallint(4) unsigned DEFAULT NULL,
  `vres` smallint(4) unsigned DEFAULT NULL,
  `custom_rss_support` enum('Y','N','?') COLLATE utf8_unicode_ci NOT NULL DEFAULT '?',
  `active` enum('Y','N','T') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'T',
  PRIMARY KEY (`idproduct`),
  KEY `productid` (`productid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sys_parms`
--

DROP TABLE IF EXISTS `sys_parms`;
CREATE TABLE IF NOT EXISTS `sys_parms` (
  `idsys_parms` smallint(8) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `scope` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'global',
  `notes` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idsys_parms`),
  KEY `key` (`key`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `idusers` smallint(8) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `active` enum('Y','N','R','P') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'R' COMMENT 'R- registered but not confirmed',
  `email` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `passwd` varbinary(40) NOT NULL,
  `ZIP` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '7 chars to allow for Canadian "xNx xNx'' formats',
  `date_registered` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL COMMENT 'When did this user login to the website last.',
  `admin` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `token` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Validation token -- md5 of email+salt',
  `fb_auth` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idusers`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `ZIP` (`ZIP`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_channels`
--

DROP TABLE IF EXISTS `user_channels`;
CREATE TABLE IF NOT EXISTS `user_channels` (
  `iduserchannels` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` smallint(8) unsigned NOT NULL,
  `channel_type_id` smallint(8) unsigned NOT NULL,
  `chan_nickname` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y',
  `attrib` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attrib_valid` enum('Y','N','?') COLLATE utf8_unicode_ci NOT NULL DEFAULT '?',
  `item_limit` smallint(4) unsigned DEFAULT NULL,
  `status` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  `channel_ttl` int(11) NOT NULL DEFAULT '60' COMMENT 'in minutes',
  UNIQUE KEY `iduserchannels` (`iduserchannels`),
  KEY `user_id` (`user_id`),
  KEY `channel_id` (`channel_type_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `words`
--

DROP TABLE IF EXISTS `words`;
CREATE TABLE IF NOT EXISTS `words` (
  `word` varchar(8) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

