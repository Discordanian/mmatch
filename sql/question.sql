/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question` (
  `question_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question_group_id` int(10) unsigned NOT NULL,
  `customer_question_text` varchar(255) NOT NULL DEFAULT 'n/a' COMMENT 'Question as it would be posed to a customer?  What issues would you want to work on?',
  `org_question_text` varchar(255) NOT NULL DEFAULT 'n/a' COMMENT 'Question as it would be posed to an organization?  What issues do you work on?',
  `randomize_order` char(1) NOT NULL DEFAULT 'Y' COMMENT 'If Y, then the order of the choices should be randomized and the default order should be ignored.',
  `sort_order` tinyint(4) NOT NULL,
  `org_max_choices` int(11) NOT NULL DEFAULT '99' COMMENT 'Organizations are prevented from gaming the system by selecting all possibilities',
  `min_nbr_customer_choices` int(11) NOT NULL DEFAULT '1' COMMENT 'When presenting choices to customers, this question must have at least this many choices.  Values 1,2.',
  `active_flag` varchar(1) NOT NULL DEFAULT 'N' COMMENT 'N by default, if N then this question is not shown to customers.',
  `null_selection_flag` enum('KEEP','DISCARD') NOT NULL DEFAULT 'KEEP' COMMENT 'When an org has not responded to a choice, this flag determines whether to keep it or discard it',
  `last_update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `question_text` varchar(255) NOT NULL,
  `org_multi_select` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`question_id`),
  KEY `fk_q_group_id` (`question_group_id`),
  CONSTRAINT `fk_q_group_id` FOREIGN KEY (`question_group_id`) REFERENCES `question_group` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

