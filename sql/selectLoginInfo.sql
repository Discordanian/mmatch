DROP PROCEDURE IF EXISTS selectLoginInfo;

DELIMITER $$
CREATE PROCEDURE selectLoginInfo(p_email VARCHAR(128))
BEGIN
	SELECT org.orgid, usr.user_id, usr.email_verified, usr.email_unverified, usr.pwhash 
	FROM app_user usr LEFT OUTER JOIN org ON org.user_id = usr.user_id
	WHERE usr.email_verified = p_email OR usr.email_unverified = p_email ;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectLoginInfo TO movemusr@localhost;


