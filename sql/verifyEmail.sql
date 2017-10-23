DROP PROCEDURE IF EXISTS verifyEmail;

DELIMITER $$
CREATE PROCEDURE verifyEmail(p_orgid INT UNSIGNED, p_email VARCHAR(255))
BEGIN
	UPDATE org SET email_verified = p_email, email_unverified = NULL WHERE orgid = p_orgid AND email_unverified = p_email; 
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE verifyEmail TO movemusr@localhost;
