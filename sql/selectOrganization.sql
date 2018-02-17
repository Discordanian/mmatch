DROP PROCEDURE IF EXISTS selectOrganization;

DELIMITER $$
CREATE PROCEDURE selectOrganization(p_orgid INT UNSIGNED)
BEGIN
/* the update timestamp is converted to a unix timestamp for return so that no timezone stuff is done to it */
/* mysql typically returns timestamp dates in the tz of the client, but that gets pretty error prone to try */
/* to back a date into a different time zone for display. So we do all of our time zone calculations in PHP */
	SELECT org.orgid, org.org_name, usr.person_name, usr.email, usr.email_is_verified, org.user_id, UNIX_TIMESTAMP(org.update_timestamp) AS update_timestamp,

	org.org_website, org.money_url, org.mission, org.abbreviated_name, org.customer_notice, org.customer_contact, org.admin_contact, org.active_ind 
	FROM org INNER JOIN app_user usr on usr.user_id = org.user_id WHERE org.orgid = p_orgid;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectOrganization TO movemusr@localhost;
