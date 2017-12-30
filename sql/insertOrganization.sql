DROP PROCEDURE IF EXISTS insertOrganization;

DELIMITER $$
CREATE PROCEDURE insertOrganization(p_org_name VARCHAR(128), p_org_website VARCHAR(255), p_money_url VARCHAR(255),
	p_mission TEXT, p_active_ind TINYINT UNSIGNED, p_abbreviated_name VARCHAR(15), p_customer_notice VARCHAR(255), p_customer_contact VARCHAR(255),
	p_admin_contact VARCHAR(255), p_user_id INT(10) UNSIGNED)
BEGIN
	DECLARE v_orgid INT(10) UNSIGNED;
	INSERT INTO org (org_name, org_website,	money_url, mission,
		abbreviated_name, customer_notice, customer_contact, admin_contact, active_ind, user_id)
            VALUES (p_org_name, p_org_website, p_money_url, p_mission,
            	p_abbreviated_name, p_customer_notice, p_customer_contact, p_admin_contact, p_active_ind, p_user_id);
	/* capture the org ID */
     SET v_orgid = LAST_INSERT_ID();
     /* select these to return back to the client */
     SELECT v_orgid AS orgid;
END
$$
DELIMITER ;

GRANT EXECUTE ON PROCEDURE insertOrganization TO movemusr@localhost;
