DROP PROCEDURE IF EXISTS selectResetLinks;

DELIMITER $$
CREATE PROCEDURE selectResetLinks(p_timeout_minutes SMALLINT UNSIGNED) 
/* By making the parameter unsigned smallint, it has a max value of 65535 */
/* So effectively, the longest expiration that can be generated with this */
/* procedure is 65535 minutes, which is just over 45 days. Seems logical. */
BEGIN
	DECLARE v_server_name VARCHAR(30);
	DECLARE v_secret_salt VARCHAR(50);
	DECLARE v_exp_date INT;
	DECLARE v_date_text CHAR(10); /* 10 characters for the time stamp will last until 2286 :-( */
	SET v_server_name = 'mm.movementmatch.org'; /* this should be a FQDN */
	SET v_secret_salt = 'Insert real salt here'; /* the secret salt must match the secret salt found in include/secrets.php */
	SET v_exp_date = UNIX_TIMESTAMP() + (p_timeout_minutes * 60);
	SET v_date_text = CAST(v_exp_date AS CHAR CHARACTER SET utf8);
	/* the email address is actually URL encoded in the password reset code */
	/* in here I did a short workaround which is to replace the @ sign in the */
	/* email address with %40, but that will not work with emails that have any */
	/* less than standard characters in them, like + or quotes (legal in email addresses) */
	/* there is no urlencode function in mysql. To do this right, would need to use or write 1 */
	SELECT app_user.user_id, app_user.email, 
	CONCAT('https://', v_server_name, '/passwordReset.php?email=', REPLACE(app_user.email, '@', '%40'), '&token=',
	LEFT(SHA2(CONCAT(v_server_name, REPLACE(app_user.email, '@', '%40'),v_date_text, app_user.user_id,'passwordReset.php', v_secret_salt), 256), 22),
	'&user_id=', app_user.user_id, '&date=', v_date_text) AS link 
	FROM app_user WHERE active_ind = 1;
END
$$

DELIMITER ;
