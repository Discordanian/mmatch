/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question` (
  `question_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question_group_id` int(10) unsigned NOT NULL,
  `question_text` varchar(255) NOT NULL,
  `org_multi_select` tinyint(1) DEFAULT NULL,
  `sort_order` tinyint(4) NOT NULL,
  PRIMARY KEY (`question_id`),
  KEY `fk_question_group_id` (`question_group_id`),
  CONSTRAINT `fk_question_group_id` FOREIGN KEY (`question_group_id`) REFERENCES `question_group` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

