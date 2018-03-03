DROP PROCEDURE IF EXISTS selectOrganizationList;

DELIMITER $$
CREATE PROCEDURE selectOrganizationList(p_user_id INT(10) UNSIGNED)
BEGIN
	IF p_user_id IS NULL THEN
		SELECT orgid, org_name, abbreviated_name, user_id 
		FROM org 
		ORDER BY org_name; 
	ELSE		
		SELECT orgid, org_name, abbreviated_name, user_id 
		FROM org 
		WHERE org.user_id = p_user_id
		ORDER BY org_name; 
	END IF;

END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectOrganizationList TO movemusr@localhost;
