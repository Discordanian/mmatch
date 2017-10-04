CREATE PROCEDURE selectNearbyOrgResponses (zipcode INT, max_range FLOAT)
	SELECT dist.orgid, dist.org_name, dist.distance,
	gg.group_text, qq.question_id, qq.question_text, qc.choice_id, qc.choice_text, 
	res.org_response_id, res.selected
	FROM question_group gg 
	INNER JOIN question qq ON gg.group_id = qq.question_group_id
	INNER JOIN question_choice qc ON qc.question_id = qq.question_id
	LEFT OUTER JOIN org_response res ON qc.choice_id = res.choice_id
	INNER JOIN
	(	SELECT org.orgid, max(org_name) AS org_name,
		MIN(geo_distance(pcr1.latitude, pcr1.longitude, pcr2.latitude, pcr2.longitude)) AS distance FROM org 
		INNER JOIN org_zip_code ozc ON org.orgid = ozc.org_id
		INNER JOIN postal_code_ref pcr2 ON pcr2.postal_code = ozc.zip_code
		INNER JOIN postal_code_ref pcr1 ON pcr1.postal_code = zipcode GROUP BY org.orgid) dist
		ON (dist.orgid = res.org_id OR dist.orgid IS NULL)
	WHERE dist.distance <= max_range
	ORDER BY dist.orgid, dist.org_name, dist.distance, qq.question_id, qc.choice_id

grant execute on procedure selectNearbyOrgResponses to movem_usr@localhost;