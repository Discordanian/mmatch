DROP PROCEDURE IF EXISTS selectEmailInfo;

DELIMITER $$
CREATE PROCEDURE selectEmailInfo(p_orgid INT UNSIGNED, p_email VARCHAR(255))
BEGIN
	SELECT orgid, email_unverified FROM org WHERE orgid = p_orgid AND email_unverified = p_email; 
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectEmailInfo TO movemusr@localhost;
