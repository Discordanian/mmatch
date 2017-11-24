DROP PROCEDURE IF EXISTS selectZipcodesForOrganization;

DELIMITER $$
CREATE PROCEDURE selectZipcodesForOrganization(p_orgid INT UNSIGNED)
BEGIN
	SELECT ozc.zip_code, pcr.city, pcr.state FROM org_zip_code ozc
		INNER JOIN postcode.postal_code_ref pcr ON ozc.zip_code = pcr.postal_code WHERE ozc.org_id = p_orgid ORDER BY ozc.zip_code ;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectZipcodesForOrganization TO movemusr@localhost;
