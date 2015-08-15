-- Adminer 4.2.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `invitation`;
CREATE TABLE `invitation` (
  `invitationID` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(70) COLLATE utf8_czech_ci NOT NULL,
  `regHash` char(32) COLLATE utf8_czech_ci NOT NULL,
  `validity` datetime DEFAULT NULL,
  PRIMARY KEY (`invitationID`),
  UNIQUE KEY `email_regHash_UNIQUE` (`email`,`regHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `listing`;
CREATE TABLE `listing` (
  `listingID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID` int(10) unsigned NOT NULL,
  `description` varchar(40) COLLATE utf8_czech_ci DEFAULT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `hourlyWage` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`listingID`),
  KEY `userID_year_month_listingID` (`userID`,`year`,`month`,`listingID`),
  CONSTRAINT `userID` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `listing_item`;
CREATE TABLE `listing_item` (
  `listingItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listingID` int(10) unsigned NOT NULL,
  `day` tinyint(3) unsigned NOT NULL,
  `localityID` int(10) unsigned NOT NULL,
  `workedHoursID` int(10) unsigned NOT NULL,
  `description` varchar(30) COLLATE utf8_czech_ci DEFAULT NULL,
  `descOtherHours` varchar(30) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`listingItemID`),
  UNIQUE KEY `listingID_day` (`listingID`,`day`),
  KEY `localityID` (`localityID`),
  KEY `workingHourID` (`workedHoursID`),
  CONSTRAINT `listing_item_ibfk_1` FOREIGN KEY (`listingID`) REFERENCES `listing` (`listingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `listing_item_ibfk_10` FOREIGN KEY (`workedHoursID`) REFERENCES `worked_hours` (`workedHoursID`),
  CONSTRAINT `listing_item_ibfk_9` FOREIGN KEY (`localityID`) REFERENCES `locality` (`localityID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `locality`;
CREATE TABLE `locality` (
  `localityID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`localityID`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `locality_user`;
CREATE TABLE `locality_user` (
  `localityUserID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID` int(10) unsigned NOT NULL,
  `localityID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`localityUserID`),
  UNIQUE KEY `localityID_userID` (`localityID`,`userID`),
  KEY `userID` (`userID`),
  CONSTRAINT `locality_user_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  CONSTRAINT `locality_user_ibfk_3` FOREIGN KEY (`localityID`) REFERENCES `locality` (`localityID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
  `messageID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sent` datetime NOT NULL,
  `subject` varchar(80) COLLATE utf8_czech_ci NOT NULL,
  `message` varchar(3000) COLLATE utf8_czech_ci NOT NULL,
  `author` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`messageID`),
  KEY `author_deleted_messageID` (`author`,`deleted`,`messageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `userID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(60) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(70) COLLATE utf8_czech_ci NOT NULL,
  `name` varchar(70) COLLATE utf8_czech_ci DEFAULT NULL,
  `role` varchar(20) COLLATE utf8_czech_ci NOT NULL DEFAULT 'employee',
  `ip` varchar(39) COLLATE utf8_czech_ci NOT NULL,
  `lastLogin` datetime NOT NULL,
  `lastIP` varchar(39) COLLATE utf8_czech_ci NOT NULL,
  `token` varchar(32) COLLATE utf8_czech_ci DEFAULT NULL,
  `tokenValidity` datetime DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username_UNIQUE` (`username`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  KEY `role_username` (`role`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `user` (`userID`, `username`, `password`, `email`, `name`, `role`, `ip`, `lastLogin`, `lastIP`, `token`, `tokenValidity`) VALUES
(1,	'TEST',	'test1',	'test@test.test',	'test',	'test',	'127.0.0.1',	'2015-05-05 05:55:55',	'127.0.0.1',	NULL,	NULL);

DROP TABLE IF EXISTS `user_message`;
CREATE TABLE `user_message` (
  `userMessageID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `messageID` int(10) unsigned NOT NULL,
  `recipient` int(10) unsigned DEFAULT NULL,
  `read` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`userMessageID`),
  UNIQUE KEY `recipient_messageID` (`recipient`,`messageID`),
  KEY `messageID` (`messageID`),
  KEY `recipient_read_deleted_messageID` (`recipient`,`read`,`deleted`,`messageID`),
  CONSTRAINT `user_message_ibfk_2` FOREIGN KEY (`messageID`) REFERENCES `message` (`messageID`),
  CONSTRAINT `user_message_ibfk_7` FOREIGN KEY (`recipient`) REFERENCES `user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `worked_hours`;
CREATE TABLE `worked_hours` (
  `workedHoursID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workStart` time NOT NULL,
  `workEnd` time NOT NULL,
  `lunch` time NOT NULL,
  `otherHours` time DEFAULT '00:00:00',
  PRIMARY KEY (`workedHoursID`),
  UNIQUE KEY `workStart_workEnd_lunch_otherHours` (`workStart`,`workEnd`,`lunch`,`otherHours`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- 2015-08-11 13:50:07