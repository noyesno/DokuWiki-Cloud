-- phpMyAdmin SQL Dump
-- version 3.3.8.1
-- http://www.phpmyadmin.net
--
-- Generation Time: Dec 26, 2012 at 10:45 AM
-- Server version: 5.5.23
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `app_dokuwiki`
--

-- --------------------------------------------------------

--
-- Table structure for table `attic`
--

CREATE TABLE IF NOT EXISTS `attic` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data` mediumblob NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`path`),
  KEY `mtime` (`mtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE IF NOT EXISTS `cache` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data` mediumblob NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`path`),
  KEY `mtime` (`mtime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `conf`
--

CREATE TABLE IF NOT EXISTS `conf` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`path`),
  KEY `mtime` (`mtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE IF NOT EXISTS `data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`path`),
  KEY `mtime` (`mtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `index`
--

CREATE TABLE IF NOT EXISTS `index` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data` mediumblob NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`path`),
  KEY `mtime` (`mtime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `memory`
--

CREATE TABLE IF NOT EXISTS `memory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`path`),
  KEY `mtime` (`mtime`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `meta`
--

CREATE TABLE IF NOT EXISTS `meta` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`path`),
  KEY `mtime` (`mtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`path`),
  KEY `mtime` (`mtime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

