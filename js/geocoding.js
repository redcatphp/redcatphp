'use strict';
window.geocoding = {
	getBoundingBox: function(centerPoint, distance){
		/**
		 * @param {number} distance - distance (km) from the point represented by centerPoint
		 * @param {array} centerPoint - two-dimensional array containing center coords [latitude, longitude]
		 * @description
		 *   Computes the bounding coordinates of all points on the surface of a sphere
		 *   that has a great circle distance to the point represented by the centerPoint
		 *   argument that is less or equal to the distance argument.
		 *   Technique from: Jan Matuschek <http://JanMatuschek.de/LatitudeLongitudeBoundingCoordinates>
		 * @author Alex Salisbury
		*/
		var MIN_LAT, MAX_LAT, MIN_LON, MAX_LON, R, radDist, degLat, degLon, radLat, radLon, minLat, maxLat, minLon, maxLon, deltaLon;
		if (distance < 0) {
			return 'Illegal arguments';
		}
		// helper functions (degrees<–>radians)
		Number.prototype.degToRad = function () {
			return this * (Math.PI / 180);
		};
		Number.prototype.radToDeg = function () {
			return (180 * this) / Math.PI;
		};
		// coordinate limits
		MIN_LAT = (-90).degToRad();
		MAX_LAT = (90).degToRad();
		MIN_LON = (-180).degToRad();
		MAX_LON = (180).degToRad();
		// Earth's radius (km)
		R = 6378.1;
		// angular distance in radians on a great circle
		radDist = distance / R;
		// center point coordinates (deg)
		degLat = centerPoint[0];
		degLon = centerPoint[1];
		// center point coordinates (rad)
		radLat = degLat.degToRad();
		radLon = degLon.degToRad();
		// minimum and maximum latitudes for given distance
		minLat = radLat - radDist;
		maxLat = radLat + radDist;
		// minimum and maximum longitudes for given distance
		minLon = void 0;
		maxLon = void 0;
		// define deltaLon to help determine min and max longitudes
		deltaLon = Math.asin(Math.sin(radDist) / Math.cos(radLat));
		if (minLat > MIN_LAT && maxLat < MAX_LAT) {
			minLon = radLon - deltaLon;
			maxLon = radLon + deltaLon;
			if (minLon < MIN_LON) {
				minLon = minLon + 2 * Math.PI;
			}
			if (maxLon > MAX_LON) {
				maxLon = maxLon - 2 * Math.PI;
			}
		}
		// a pole is within the given distance
		else {
			minLat = Math.max(minLat, MIN_LAT);
			maxLat = Math.min(maxLat, MAX_LAT);
			minLon = MIN_LON;
			maxLon = MAX_LON;
		}
		return [
			minLon.radToDeg(),
			minLat.radToDeg(),
			maxLon.radToDeg(),
			maxLat.radToDeg()
		];
	},
	getDistance: function(lat1, lon1, lat2, lon2){
		var R,dLat,dLon,a;
		R = 6371; // Radius of the earth in km
		dLat = (lat2 - lat1) * Math.PI / 180;  // deg2rad below
		dLon = (lon2 - lon1) * Math.PI / 180;
		a = 0.5 - Math.cos(dLat)/2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * (1 - Math.cos(dLon))/2;
		return R * 2 * Math.asin(Math.sqrt(a));
	}
};