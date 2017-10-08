DROP PROCEDURE IF EXISTS insertOrganization;

DELIMITER $$
CREATE PROCEDURE insertOrganization(p_org_name VARCHAR(128), p_person_name VARCHAR(128), p_org_website VARCHAR(255), p_money_url VARCHAR(255),
	p_mission TEXT, p_active_ind TINYINT UNSIGNED, p_abbreviated_name VARCHAR(15), p_customer_notice VARCHAR(255), p_customer_contact VARCHAR(255),
	p_admin_contact VARCHAR(255), p_pwhash VARCHAR(128), p_email VARCHAR(255))
BEGIN
	INSERT INTO org (org_name, person_name, email_unverified, pwhash, org_website,
            	money_url, mission, abbreviated_name, customer_notice, customer_contact, admin_contact, active_ind)
            VALUES (p_org_name, p_person_name, p_email, p_pwhash, p_org_website, p_money_url, p_mission,
            	p_abbreviated_name, p_customer_notice, p_customer_contact, p_admin_contact, p_active_ind);

     SELECT LAST_INSERT_ID();
END
$$
DELIMITER ;

GRANT EXECUTE ON PROCEDURE insertOrganization TO movemusr@localhost;
