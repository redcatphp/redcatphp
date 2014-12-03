#include <stdlib.h>
#include <math.h>
#include <postgres.h>
#include <fmgr.h>
#include <utils/datum.h>
#include <utils/geo_decls.h>

#ifdef PG_MODULE_MAGIC
PG_MODULE_MAGIC;
#endif

#define DEG_TO_RAD      0.017453292
#define DEG_TO_RAD_2    0.008726646
#define EARTH_2     12745.594000000

PG_FUNCTION_INFO_V1(haversine);
Datum haversine(PG_FUNCTION_ARGS) {
  Point* a = PG_GETARG_POINT_P(0);
  Point* b = PG_GETARG_POINT_P(1);
  float8 lat_a = a->x * DEG_TO_RAD;
  float8 lat_b = b->x * DEG_TO_RAD;
  float8 d_lat = (b->x - a->x) * DEG_TO_RAD_2;
  float8 d_lon = (b->y - a->y) * DEG_TO_RAD_2;
  float8 sin_dlat = sin(d_lat);
  float8 sin_dlon = sin(d_lon);
  PG_RETURN_FLOAT8(EARTH_2 * asin(sqrt(sin_dlat*sin_dlat+cos(lat_a)*cos(lat_b)*sin_dlon*sin_dlon)));
}
