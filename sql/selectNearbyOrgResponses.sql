-- MySQL dump 10.13  Distrib 5.7.18, for Linux (x86_64)
--
-- Host: localhost    Database: mmaz
-- ------------------------------------------------------
-- Server version	5.7.18-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!50003 DROP PROCEDURE IF EXISTS `selectNearbyOrgResponses` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `selectNearbyOrgResponses`(zipcode INT, max_range FLOAT)
BEGIN
  
SET @sql = CONCAT('
/* To display questions to customers */
/* Includes
-	question group, a heading
-	question text written for the customer
-	choice text, always the same for both customers and organizations
*/
/* ordered by
-	question group default order
-	question default order then by
-	choice order which has been randomized if indicated on question
*/
/*
Filtered 
1) to only include choices THAT ARE RANKED by at least one of the 
	Organizations that are in the zip code radius selected by the customer
	When a choice is offered to an Organization and they do not select it, that choice is marked as a 0.
    When a choice is added after the organization was able to choose, then that choice is left as a NULL.
    This SQL uses a switch to decide whether to include or exclude a choice if the selection is NULL.
2) only active questions.  We can over-ask the organization then drop the question before asking the customer.

*/
/* Note that displaying a single choice is okay because
	it means that an organization has identified that way
	another organization may have not made any selection
	resulting that a customer who choses that will advantage
	the organization that did make a selection for that question.
*/

SELECT 
org.orgid, org.org_name, org.customer_notice, dist.distance, org_website, org.mission, 
	org.money_url, org.customer_contact, org.abbreviated_name,
	C.group_text, C.question_id, C.customer_question_text, C.choice_id, C.choice_text, 
	ORGR.org_response_id, 
    ORGR.selected AS selected

FROM /*get the orgnaizations in range */
    	(SELECT org.orgid, 
		MIN(geo_distance(pcr1.latitude, pcr1.longitude, pcr2.latitude, pcr2.longitude)) AS distance 
		FROM org 
		INNER JOIN org_zip_code ozc ON org.orgid = ozc.org_id
		INNER JOIN postcode.postal_code_ref pcr2 ON pcr2.postal_code = ozc.zip_code
		INNER JOIN postcode.postal_code_ref pcr1 ON pcr1.postal_code = ',zipcode,'
        WHERE org.active_ind = 1 AND org.admin_active_ind = 1
		GROUP BY org.orgid
		HAVING distance <= ',max_range,'
		) dist

INNER JOIN org
		ON (dist.orgid = org.orgid)		

INNER JOIN org_response AS ORGR 
		ON (dist.orgid = ORGR.org_id
			AND ORGR.selected = 1)	
/* get the choices; randomize order if called for */        
INNER JOIN 
		(SELECT 
			QG.group_text,
            QG.page_num,
			Q.question_id,
            Q.null_selection_flag,
            Q.customer_question_text,
            Q.sort_order Q_ORDER,
            Q.active_flag,
            C1.choice_id,
			C1.choice_text,
				CASE
					WHEN Q.randomize_order = \'Y\' THEN RAND()
					ELSE C1.sort_order
				END AS CHOICE_ORDER
		FROM
			question_choice C1
		INNER JOIN question Q 
			ON (Q.question_id = C1.question_id)
		INNER JOIN question_group AS QG 
			ON (Q.question_group_id = QG.group_id)
		WHERE Q.active_flag <> \'N\'
		) AS C 
		ON (C.choice_id = ORGR.choice_id)
ORDER BY org.orgid, org.org_name, dist.distance, C.question_id, C.choice_id
'
        );
    
    PREPARE stmt FROM @sql;
	EXECUTE stmt;
	DEALLOCATE PREPARE stmt;
END ;;
DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectNearbyOrgResponses TO movemusr@localhost;

