DROP PROCEDURE IF EXISTS updateUser;

DELIMITER $$
CREATE PROCEDURE updateUser(p_user_id INT UNSIGNED, p_person_name VARCHAR(128), p_email VARCHAR(255),
	p_pwhash VARCHAR(128), p_active_ind TINYINT, p_admin_active_ind TINYINT, p_admin_user_ind TINYINT)
BEGIN
	UPDATE app_user SET person_name = p_person_name WHERE user_id = p_user_id;

	/* set email to unverified if email address changed */
	UPDATE app_user SET email = LOWER(p_email), email_is_verified = FALSE WHERE user_id = p_user_id AND email != p_email;

	/* if a password was specified, update that */
	IF p_pwhash IS NOT NULL THEN
		UPDATE app_user SET pwhash = p_pwhash WHERE user_id = p_user_id AND p_pwhash IS NOT NULL;
	END IF;

	/* if the active indicator was specified update it */
	IF p_active_ind IS NOT NULL THEN
		UPDATE app_user SET active_ind = p_active_ind WHERE user_id = p_user_id AND p_active_ind IS NOT NULL;
	END IF;

	/* if the administrative active indicator was specified update it */
	IF p_admin_active_ind IS NOT NULL THEN
		UPDATE app_user SET admin_active_ind = p_admin_active_ind WHERE user_id = p_user_id AND p_admin_active_ind IS NOT NULL;
	END IF;

	/* if the administrative user indicator was specified update it */
	IF p_admin_user_ind IS NOT NULL THEN
		UPDATE app_user SET admin_user_ind = p_admin_user_ind WHERE user_id = p_user_id AND p_admin_user_ind IS NOT NULL;
	END IF;

END
            $$
DELIMITER ;

GRANT EXECUTE ON PROCEDURE updateUser TO movemusr@localhost;
            