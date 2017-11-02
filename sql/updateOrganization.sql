DROP PROCEDURE IF EXISTS updateOrganization;

DELIMITER $$
CREATE PROCEDURE updateOrganization(p_orgid INT UNSIGNED, p_org_name VARCHAR(128), p_person_name VARCHAR(128), p_org_website VARCHAR(255), p_money_url VARCHAR(255),
	p_mission TEXT, p_active_ind TINYINT UNSIGNED, p_abbreviated_name VARCHAR(15), p_customer_notice VARCHAR(255), p_customer_contact VARCHAR(255),
	p_admin_contact VARCHAR(255), p_pwhash VARCHAR(128), p_email VARCHAR(255), p_user_id INT UNSIGNED)
BEGIN
	/* DECLARE v_user_id INT UNSIGNED; */
	/* first update org */
	UPDATE org SET org_name = p_org_name, org_website = p_org_website, money_url = p_money_url, 
            mission = p_mission, active_ind = p_active_ind, abbreviated_name = p_abbreviated_name, customer_notice = p_customer_notice, 
            customer_contact = p_customer_contact, admin_contact = p_admin_contact WHERE orgid = p_orgid;
     /* now update the user table */
     UPDATE app_user SET person_name = p_person_name WHERE user_id = p_user_id; 
    /* The next line is to only update the password if a new password was specified */
       UPDATE app_user SET pwhash = p_pwhash WHERE user_id = p_user_id AND p_pwhash IS NOT NULL;
       /* The next two lines are to set the email to unverified if the email address is changed */ 
       UPDATE app_user SET email_unverified = p_email WHERE user_id = p_user_id AND email_verified IS NOT NULL AND email_verified != p_email; 
       UPDATE app_user SET email_unverified = p_email WHERE user_id = p_user_id AND email_verified IS NULL AND email_unverified != p_email; 
END
            $$
DELIMITER ;

GRANT EXECUTE ON PROCEDURE updateOrganization TO movemusr@localhost;
            