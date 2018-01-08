DROP PROCEDURE IF EXISTS selectLoginInfo;

DELIMITER $$
CREATE PROCEDURE selectLoginInfo(p_email VARCHAR(128))
BEGIN
	SELECT org.orgid, usr.user_id, usr.email, usr.email_is_verified, usr.pwhash, 
	   usr.active_ind, usr.admin_user_ind
	FROM app_user usr LEFT OUTER JOIN org ON org.user_id = usr.user_id
	WHERE usr.email = p_email AND usr.active_ind = TRUE ;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectLoginInfo TO movemusr@localhost;


