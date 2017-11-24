CREATE TABLE `postal_code_ref` (
  `postal_code_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_code` char(2) NOT NULL,
  `postal_code` int(10) unsigned NOT NULL,
  `place_name` varchar(180) NOT NULL,
  `admin_name1` varchar(100) NOT NULL,
  `admin_code1` varchar(20) NOT NULL,
  `admin_name2` varchar(100) NOT NULL,
  `admin_code2` varchar(20) NOT NULL,
  `admin_name3` varchar(100) NOT NULL,
  `admin_code3` varchar(20) NOT NULL,
  `latitude` decimal(8,4) NOT NULL,
  `longitude` decimal(8,4) NOT NULL,
  `city` varchar(180) GENERATED ALWAYS AS (`place_name`) VIRTUAL,
  `state` varchar(20) GENERATED ALWAYS AS (`admin_code1`) VIRTUAL,
  PRIMARY KEY (`postal_code_id`),
  UNIQUE KEY `ix_postal_code_ref_postal_code` (`postal_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

