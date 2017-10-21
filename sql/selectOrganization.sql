DROP PROCEDURE IF EXISTS selectOrganization;

DELIMITER $$
CREATE PROCEDURE selectOrganization(p_orgid INT UNSIGNED)
BEGIN
	SELECT orgid, org_name, person_name, email_verified, email_unverified, org_website, money_url, mission, 
            abbreviated_name, customer_notice, customer_contact, admin_contact, active_ind FROM org WHERE orgid = p_orgid;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectOrganization TO movemusr@localhost;
