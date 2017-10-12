DROP FUNCTION IF EXISTS geo_distance;

DELIMITER $$
CREATE FUNCTION geo_distance (lat1 double, long1 double, lat2 double, long2 double)
RETURNS double DETERMINISTIC
BEGIN
	DECLARE pt1 POINT;
	DECLARE pt2 POINT;
	DECLARE dist DOUBLE;
	SET pt1 = POINT(long1, lat1);
	SET pt2 = POINT(long2, lat2);
	SET dist = ST_Distance_Sphere(pt1, pt2)/1000;
	RETURN dist;
END
$$
DELIMITER ;

GRANT EXECUTE ON FUNCTION geo_distance TO movemusr@localhost;
