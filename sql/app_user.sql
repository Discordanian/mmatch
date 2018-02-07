CREATE TABLE `app_user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `person_name` varchar(128) NOT NULL,
  `pwhash` varchar(128) NOT NULL,
  `active_ind` tinyint(4) DEFAULT '1',
  `admin_active_ind` tinyint(4) DEFAULT '1',
  `email` varchar(255) NOT NULL,
  `email_is_verified` tinyint(4) NOT NULL DEFAULT '0',
  `admin_user_ind` tinyint(4) DEFAULT '0',
  `update_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `ix_app_user_email_unique` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
