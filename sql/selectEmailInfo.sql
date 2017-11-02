DROP PROCEDURE IF EXISTS selectEmailInfo;

DELIMITER $$
CREATE PROCEDURE selectEmailInfo(p_user_id INT UNSIGNED, p_email VARCHAR(255))
BEGIN
	SELECT user_id, email_unverified, email_verified FROM app_user WHERE user_id = p_user_id AND email_unverified = p_email; 
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectEmailInfo TO movemusr@localhost;
