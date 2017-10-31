CREATE TABLE `app_user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `person_name` varchar(128) NOT NULL,
  `email_verified` varchar(255) DEFAULT NULL,
  `email_unverified` varchar(255) DEFAULT NULL,
  `pwhash` varchar(128) NOT NULL,
  `active_ind` tinyint(4) DEFAULT '1',
  `admin_active_ind` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
