CREATE OR REPLACE FUNCTION public.geodistance(lat1 double precision,lon1 double precision,lat2 double precision,lon2 double precision)
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