/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `org_zip_code` (
  `org_zip_code_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `org_id` int(10) unsigned NOT NULL,
  `zip_code` int(10) unsigned NOT NULL,
  PRIMARY KEY (`org_zip_code_id`),
  KEY `fk_org_zip_code_org_id` (`org_id`),
  KEY `fk_org_zip_code_zip_code` (`zip_code`),
  CONSTRAINT `fk_org_zip_code_org_id` FOREIGN KEY (`org_id`) REFERENCES `org` (`orgid`),
  CONSTRAINT `fk_org_zip_code_zip_code` FOREIGN KEY (`zip_code`) REFERENCES `postal_code_ref` (`postal_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

