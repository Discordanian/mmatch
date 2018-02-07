/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `org` (
  `orgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `org_name` varchar(128) DEFAULT NULL,
  `org_website` varchar(255) DEFAULT NULL,
  `money_url` varchar(255) DEFAULT NULL,
  `mission` text,
  `org_type` tinyint(4) NOT NULL DEFAULT '1',
  `abbreviated_name` varchar(15) NOT NULL DEFAULT '',
  `customer_notice` varchar(255) NOT NULL DEFAULT '',
  `customer_contact` varchar(255) NOT NULL DEFAULT '',
  `admin_contact` varchar(255) NOT NULL DEFAULT '',
  `active_ind` tinyint(4) DEFAULT '1',
  `admin_active_ind` tinyint(4) DEFAULT '1',
  `user_id` int(10) unsigned DEFAULT NULL,
  `update_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`orgid`),
  UNIQUE KEY `ix_org_org_name_unique` (`org_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

