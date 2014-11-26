CREATE FUNCTION haversine_point(a point, b point)
  RETURNS double precision AS
$BODY$
SELECT asin(sqrt(
  sin(radians($2[0]-$1[0])/2)^2 +
  (
    sin(radians($2[1]-$1[1])/2)^2 *
    cos(radians($1[0])) *
    cos(radians($2[0]))
  )
)) * 6371 * 2 AS distance;
$BODY$
  LANGUAGE sql IMMUTABLE
  COST 100;
