CREATE TABLE IF NOT EXISTS `app_user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique, auto generated user identifier (primary key)',
  `person_name` varchar(128) NOT NULL COMMENT 'The full name of the user',
  `pwhash` varchar(128) NOT NULL COMMENT 'Hash of the users password (using php password_hash function)',
  `active_ind` tinyint(1) unsigned DEFAULT '1' COMMENT 'Indicator specifying if the user is allowed to log on',
  `admin_active_ind` tinyint(1) unsigned DEFAULT '1' COMMENT 'Indicator specifying if an administrator wants to allow the user to log on',
  `admin_user_ind` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Indicator specifying this user is an administrator',
  `email` varchar(255) NOT NULL COMMENT 'Email address of the user',
  `email_is_verified` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Has the email addresss been verified by sending to the address?',
  `update_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `ix_app_user_email_unique` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Application Users'
