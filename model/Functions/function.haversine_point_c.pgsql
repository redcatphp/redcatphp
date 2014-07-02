CREATE FUNCTION haversine_point_c(point, point) RETURNS double precision
    AS '$libdir/haversine', 'haversine'
LANGUAGE C STRICT IMMUTABLE;
