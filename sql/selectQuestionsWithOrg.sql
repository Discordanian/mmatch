DROP PROCEDURE IF EXISTS selectQuestionsWithOrg;

CREATE PROCEDURE selectQuestionsWithOrg (org_id INT)
SELECT gg.page_num, gg.group_text, qq.question_id, qq.org_question_text, qq.org_multi_select, 
	qc.choice_id, qc.choice_text, res.org_response_id, res.org_id, res.selected, '0' AS new_selected 
	FROM question_group gg INNER JOIN question qq 
	ON gg.group_id = qq.question_group_id 
	INNER JOIN question_choice qc 
	ON qc.question_id = qq.question_id 
	LEFT OUTER JOIN org_response res 
	ON res.choice_id = qc.choice_id AND (res.org_id IS NULL OR res.org_id = org_id) 
	WHERE qq.active_flag = 'Y' 
	ORDER BY gg.page_num, qq.question_id, qq.sort_order, qc.choice_id, qc.sort_order;

GRANT EXECUTE ON PROCEDURE selectQuestionsWithOrg TO movemusr@localhost;


