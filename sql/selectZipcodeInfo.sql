DROP PROCEDURE IF EXISTS selectZipcodeInfo;

DELIMITER $$

CREATE PROCEDURE selectZipcodeInfo (p_zip_code INT UNSIGNED)
BEGIN
	SELECT postal_code, city, state 
	FROM shared_db.postal_code_ref 
	WHERE postal_code = p_zip_code;
END
$$

DELIMITER ;
GRANT EXECUTE ON PROCEDURE selectZipcodeInfo TO movemusr@localhost;
