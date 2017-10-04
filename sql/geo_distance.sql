DELIMITER $$
CREATE FUNCTION geo_distance (lat1 double, long1 double, lat2 double, long2 double)
RETURNS double DETERMINISTIC

BEGIN
	DECLARE lat_diff double;
	DECLARE long_diff double;
	DECLARE lat_dist double;
	DECLARE long_dist double;
	DECLARE dist double;
	SET lat_diff = lat1 - lat2;
	SET lat_dist = 111 * lat_diff;
	SET long_diff = long1 - long2;
	SET long_dist = (111 * COS(long1 / 2 * PI())) * long_diff;
	SET dist = SQRT(POW(long_dist,2) + POW(lat_dist,2));
RETURN dist;
END$$
DELIMITER ;

GRANT EXECUTE ON FUNCTION geo_distance TO movemusr@localhost;
