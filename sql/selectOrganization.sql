DROP PROCEDURE IF EXISTS selectOrganization;

DELIMITER $$
CREATE PROCEDURE selectOrganization(p_orgid INT UNSIGNED)
BEGIN
	SELECT org.orgid, org.org_name, usr.person_name, usr.email, usr.email_is_verified, org.user_id,
	org.org_website, org.money_url, org.mission, org.abbreviated_name, org.customer_notice, org.customer_contact, org.admin_contact, org.active_ind 
	FROM org INNER JOIN app_user usr on usr.user_id = org.user_id WHERE org.orgid = p_orgid;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectOrganization TO movemusr@localhost;
