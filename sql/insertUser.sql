DROP PROCEDURE IF EXISTS insertUser;

DELIMITER $$
CREATE PROCEDURE insertUser(p_person_name VARCHAR(128), p_email VARCHAR(255), p_pwhash VARCHAR(128),
	p_active_ind TINYINT UNSIGNED, p_admin_user_ind TINYINT UNSIGNED)
BEGIN
	INSERT INTO app_user (person_name, email, email_is_verified, pwhash, active_ind, admin_user_ind)
            VALUES (p_person_name, p_email, FALSE, p_pwhash, p_active_ind, p_admin_user_ind);
     /* select these to return back to the client */
     SELECT LAST_INSERT_ID() AS user_id;
END
$$
DELIMITER ;

GRANT EXECUTE ON PROCEDURE insertUser TO movemusr@localhost;
