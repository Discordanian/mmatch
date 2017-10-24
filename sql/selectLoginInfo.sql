DROP PROCEDURE IF EXISTS selectLoginInfo;

DELIMITER $$
CREATE PROCEDURE selectLoginInfo(p_email VARCHAR(128))
BEGIN
	SELECT orgid, email_verified, email_unverified, pwhash FROM org WHERE email_verified = p_email OR email_unverified = p_email ;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectLoginInfo TO movemusr@localhost;
