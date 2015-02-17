<?php namespace Surikat\Geo;
class Geocoding{
	static $earthRadiusTypes = [
		'km'=>6371,
		'miles'=>3959,
	];
	static function getEarthRadius($type='km'){
		if(isset(Geocoding::$earthRadiusTypes[$type]))
			return Geocoding::$earthRadiusTypes[$type];
	}
	static function getBoundingBox( array $center,$rad,$R='km'){
		list($lat,$lon) = $center;
		if(is_string($R))
			$R = self::getEarthRadius($R);	
		// coordinate limits
		$MIN_LAT = deg2rad(-90);
		$MAX_LAT = deg2rad(90);
		$MIN_LON = deg2rad(-180);
		$MAX_LON = deg2rad(180);
		$radDist = $rad/$R; // angular distance in radians on a great circle
		// center point coordinates (rad)
		$radLat = deg2rad($lat);
		$radLon = deg2rad($lon);
		// minimum and maximum latitudes for given distance
		$minLat = $radLat - $radDist;
		$maxLat = $radLat + $radDist;
		// minimum and maximum longitudes for given distance
		$minLon = 0;
		$maxLon = 0;
		$deltaLon = asin(sin($radDist) / cos($radLat)); // define deltaLon to help determine min and max longitudes
		if ($minLat > $MIN_LAT && $maxLat < $MAX_LAT) {
			$minLon = $radLon - $deltaLon;
			$maxLon = $radLon + $deltaLon;
			if ($minLon < $MIN_LON) {
				$minLon = $minLon + 2 * pi();
			}
			if ($maxLon > $MAX_LON) {
				$maxLon = $maxLon - 2 * pi();
			}
		}
		else { // a pole is within the given distance
			$minLat = max($minLat, $MIN_LAT);
			$maxLat = min($maxLat, $MAX_LAT);
			$minLon = $MIN_LON;
			$maxLon = $MAX_LON;
		}
		$minLon = rad2deg($minLon);
		$minLat = rad2deg($minLat);
		$maxLon = rad2deg($maxLon);
		$maxLat = rad2deg($maxLat);
		return [
			$minLon,
			$minLat,
			$maxLon,
			$maxLat,
		];
	}
}