DROP PROCEDURE IF EXISTS updateOrganization;

DELIMITER $$
CREATE PROCEDURE updateOrganization(p_orgid INT UNSIGNED, p_org_name VARCHAR(128), p_org_website VARCHAR(255), p_money_url VARCHAR(255),
	p_mission TEXT, p_active_ind TINYINT UNSIGNED, p_abbreviated_name VARCHAR(15), p_customer_notice VARCHAR(255), p_customer_contact VARCHAR(255),
	p_admin_contact VARCHAR(255), p_user_id INT UNSIGNED)
BEGIN
	/* DECLARE v_user_id INT UNSIGNED; */
	/* first update org */
	UPDATE org SET org_name = p_org_name, org_website = p_org_website, money_url = p_money_url, 
            mission = p_mission, active_ind = p_active_ind, abbreviated_name = p_abbreviated_name, customer_notice = p_customer_notice, 
            customer_contact = p_customer_contact, admin_contact = p_admin_contact WHERE orgid = p_orgid;
END
            $$
DELIMITER ;

GRANT EXECUTE ON PROCEDURE updateOrganization TO movemusr@localhost;
            