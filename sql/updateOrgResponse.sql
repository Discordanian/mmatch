DROP PROCEDURE IF EXISTS updateOrgResponse;

DELIMITER $$
CREATE PROCEDURE updateOrgResponse(p_response_id INT UNSIGNED, p_orgid INT UNSIGNED, p_choice_id INT UNSIGNED, p_selected TINYINT UNSIGNED)
BEGIN
	IF p_response_id > 0 THEN
		UPDATE org_response SET selected = p_selected WHERE org_response_id = p_response_id AND org_id = p_orgid AND choice_id = p_choice_id;
	ELSE
		INSERT INTO org_response (choice_id, org_id, selected) VALUES (p_choice_id, p_orgid, p_selected);
	END IF;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE updateOrgResponse TO movemusr@localhost;
