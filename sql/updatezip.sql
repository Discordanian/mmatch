SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;
DELETE FROM postal_code_ref;
LOAD DATA LOCAL INFILE 'sorted.txt' INTO TABLE postal_code_ref
	(country_code, postal_code, place_name, admin_name1, admin_code1, admin_name2,
	admin_code2, admin_name3, admin_code3, latitude, longitude);
COMMIT;
SET FOREIGN_KEY_CHECKS=1;

