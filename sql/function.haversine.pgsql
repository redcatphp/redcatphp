CREATE FUNCTION haversine(lat1 DOUBLE, lon1 DOUBLE, lat2 DOUBLE, lon2 DOUBLE)
  RETURNS double precision AS
$BODY$
SELECT asin(sqrt(
  sin(radians($3-$1)/2)^2 +
  (
    sin(radians($4-$2)/2)^2 *
    cos(radians($1)) *
    cos(radians($3))
  )
)) * 6371 * 2 AS distance;
$BODY$
  LANGUAGE sql IMMUTABLE
  COST 100;
