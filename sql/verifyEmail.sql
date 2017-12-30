DROP PROCEDURE IF EXISTS verifyEmail;

DELIMITER $$
CREATE PROCEDURE verifyEmail(p_user_id INT UNSIGNED, p_email VARCHAR(255))
BEGIN
	UPDATE app_user SET email_is_verified = TRUE WHERE user_id = p_user_id AND email = p_email; 
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE verifyEmail TO movemusr@localhost;
