DROP PROCEDURE IF EXISTS selectEmailInfo;

DELIMITER $$
CREATE PROCEDURE selectEmailInfo(p_user_id INT UNSIGNED, p_email VARCHAR(255))
BEGIN
	SELECT user_id, email_is_verified, email FROM app_user WHERE user_id = p_user_id AND email = p_email; 
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectEmailInfo TO movemusr@localhost;
