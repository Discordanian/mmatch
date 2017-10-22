DROP PROCEDURE IF EXISTS updateOrgZipcodes;

DELIMITER $$
CREATE PROCEDURE updateOrgZipcodes(p_orgid INT UNSIGNED, p_zipcodeArray JSON)
BEGIN
	DECLARE zipcode INT UNSIGNED;
	/* TODO: do all this work within a transaction */
	DELETE FROM org_zip_code WHERE org_id = p_orgid;
	/* There is no valid zip code array in JSON that would be less than 9 characters */
	IF CHAR_LENGTH(p_zipcodeArray) > 8 THEN
	BEGIN
		/* p_zipcodeArray is untrusted data! */
		/* must be careful to process it with care */
		WHILE JSON_LENGTH(p_zipcodeArray) > 0 DO
			/* extract first value from array */
			/* make sure to convert to INT which shuts down any parameter tampering */
			SET zipcode = CAST(JSON_EXTRACT(p_zipcodeArray, '$[0]') AS UNSIGNED);
			IF zipcode > 0 AND zipcode < 99999 THEN 
				INSERT INTO org_zip_code (org_id, zip_code) VALUES (p_orgid, zipcode);
			END IF;
			/* delete first value from array */
			SET p_zipcodeArray = JSON_REMOVE(p_zipcodeArray, '$[0]');
		END WHILE;
	END;
	END IF;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE updateOrgZipcodes TO movemusr@localhost;
