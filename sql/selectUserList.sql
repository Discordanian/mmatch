DROP PROCEDURE IF EXISTS selectUserList;

DELIMITER $$
CREATE PROCEDURE selectUserList()
BEGIN
	SELECT usr.user_id, usr.person_name, usr.email, usr.email_is_verified,
	   usr.active_ind, usr.admin_user_ind
	FROM app_user usr
	ORDER BY usr.person_name, usr.email;
END
$$
DELIMITER ;


GRANT EXECUTE ON PROCEDURE selectUserList TO movemusr@localhost;

