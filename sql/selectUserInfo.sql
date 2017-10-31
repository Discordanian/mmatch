DROP PROCEDURE IF EXISTS selectUserInfo;

DELIMITER $$
CREATE PROCEDURE selectUserInfo(p_user_id INT(10) UNSIGNED)
BEGIN
	SELECT usr.user_id, usr.person_name, usr.email_verified, usr.email_unverified, usr.pwhash 
	FROM app_user usr 
	WHERE usr.user_id = p_user_id ;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectUserInfo TO movemusr@localhost;


