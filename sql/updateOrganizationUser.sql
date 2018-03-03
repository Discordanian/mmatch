DROP PROCEDURE IF EXISTS updateOrganizationUser;
DELIMITER $$

CREATE PROCEDURE updateOrganizationUser(p_user_id INT(10) UNSIGNED, p_org_list JSON)
BEGIN
	DECLARE v_orgid INT UNSIGNED;
	/* TODO: do all this work within a transaction */
	/* There is no valid integer array in JSON that would be less than 3 characters */
	IF CHAR_LENGTH(p_org_list) > 3 THEN
	BEGIN
		/* p_list is untrusted data! */
		/* must be careful to process it with care */
		WHILE JSON_LENGTH(p_org_list) > 0 DO
			/* extract first value from array */
			/* make sure to convert to INT which shuts down any parameter tampering */
			SET v_orgid = CAST(JSON_EXTRACT(p_org_list, '$[0]') AS UNSIGNED);
			IF v_orgid > 0 THEN 
				UPDATE org SET user_id = p_user_id WHERE user_id != p_user_id AND orgid = v_orgid;
			END IF;
			/* delete first value from array */
			SET p_org_list = JSON_REMOVE(p_org_list, '$[0]');
		END WHILE;
	END;
	END IF;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE updateOrganizationUser TO movemusr@localhost;
