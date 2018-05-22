DROP FUNCTION IF EXISTS geo_distance;

DELIMITER $$
CREATE FUNCTION geo_distance (lat1 DOUBLE, lon1 DOUBLE, lat2 DOUBLE, lon2 DOUBLE)
RETURNS double DETERMINISTIC
BEGIN
/* Based on Haversine formula and adapted from code on:
https://stackoverflow.com/questions/27928/calculate-distance-between-two-latitude-longitude-points-haversine-formula */
	DECLARE dist DOUBLE;
	DECLARE dLon DOUBLE;
	DECLARE dLat DOUBLE;
	DECLARE a DOUBLE;
	DECLARE c DOUBLE;
	SET dLat = RADIANS(lat2 - lat1);
	SET dLon = RADIANS(lon2 - lon1);
	SET a = POW(SIN(dLat / 2), 2) + (COS(RADIANS(lat1)) * COS(RADIANS(lat2))) * POW(SIN(dLon / 2), 2);
	SET c = 2 * ATAN2(SQRT(a), SQRT(1-a));
	SET dist = 6370.985 * c;
	RETURN dist;
END
$$
DELIMITER ;

GRANT EXECUTE ON FUNCTION geo_distance TO movemusr@localhost;
