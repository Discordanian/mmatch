DROP PROCEDURE IF EXISTS selectOrganizationList;

DELIMITER $$
CREATE PROCEDURE selectOrganizationList(p_user_id INT(10) UNSIGNED)
BEGIN
	SELECT orgid, org_name, abbreviated_name 
	FROM org 
	WHERE org.user_id = p_user_id
	ORDER BY org_name; 
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectOrganizationList TO movemusr@localhost;
