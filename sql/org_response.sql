/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `org_response` (
  `org_response_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `org_id` int(10) unsigned NOT NULL,
  `choice_id` int(10) unsigned NOT NULL,
  `selected` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`org_response_id`),
  KEY `fk_org_response_org_id` (`org_id`),
  KEY `fk_org_response_choice_id` (`choice_id`),
  CONSTRAINT `fk_org_response_choice_id` FOREIGN KEY (`choice_id`) REFERENCES `question_choice` (`choice_id`),
  CONSTRAINT `fk_org_response_org_id` FOREIGN KEY (`org_id`) REFERENCES `org` (`orgid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

