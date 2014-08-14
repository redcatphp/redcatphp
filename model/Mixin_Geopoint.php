<?php namespace surikat\model;
use surikat\model\RedBeanPHP\QueryWriter\PostgreSQL;
use surikat\model\RedBeanPHP\QueryWriter\MySQL;
trait Mixin_Geopoint{
	static $columnPointCast	= 'point';
	static $columnPointWriteCol;
	static $columnPointReadCol;
	private $_pointPrefix = '';
	private $_pointSeparator = ',';
	protected static $earthRadiusTypes = array(
		'km'=>6371,
		'miles'=>3959,
	);
	protected $earthRadius = 'km';
	function _binder($table){
		if($this->queryWriter instanceof MySQL){
			$this->_pointPrefix = 'POINT';
			$this->_pointSeparator = ' ';
			static::$columnPointWriteCol = 'GeomFromText';
			static::$columnPointReadCol = 'asText';
		}
		elseif($this->queryWriter instanceof PostgreSQL){
			$this->_pointPrefix = '';
			$this->_pointSeparator = ',';
		}
		parent::_binder($table);
	}
	function getEarthRadius(){
		if(is_string($this->earthRadius)&&isset(static::$earthRadiusTypes[$this->earthRadius]))
			$this->earthRadius = static::$earthRadiusTypes[$this->earthRadius];
		return $this->earthRadius;
	}
	function checkLat(&$lat,$nul=true){
		if($lat!='')
			$lat = (float)$lat;
		if(($nul&&(!isset($lat)||$lat===false))||($lat<=90.0&&$lat>=-90.0))
			return true;
		$lat = null;
	}
	function checkLon(&$lon,$nul=true){
		if($lon!='')
			$lon = (float)$lon;
		if(($nul&&(!isset($lon)||$lon===false))||($lon<=180.0&&$lon>=-180.0))
			return true;
		$lon = null;
	}
	function LatLon2Point($lat,$lon){
		$lat = str_replace(',','.',$lat);
		$lon = str_replace(',','.',$lon);
		return $this->_pointPrefix.'('.$lat.$this->_pointSeparator.$lon.')';
	}
	function setPoint($lat,$lon){
		$this->point = $this->LatLon2Point($lat,$lon);
	}
	function setBounds($lat,$lon,$rad){
		list($this->west, $this->south, $this->east, $this->north) = $this->getBoundingBox(array($lat,$lon),$rad);
	}
	function getBoundingBox(array $center,$rad){
		list($lat,$lon) = $center;
		if(!$rad)
			return array($lon,$lat,$lon,$lat);
		$R = $this->getEarthRadius();

		/*
			//Naïve approch don't work in somes special cases
			$latVector = $rad?rad2deg($rad/$R):0;
			$lonVector = $rad?rad2deg($rad/$R/cos(deg2rad($lat))):0;
			$minLat = $lat - $latVector;
			$maxLat = $lat + $latVector;
			$minLon = $lon - $lonVector;
			$maxLon = $lon + $lonVector;
		*/
		
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
		// define deltaLon to help determine min and max longitudes
		$deltaLon = asin(sin($radDist) / cos($radLat));
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
		// a pole is within the given distance
		else {
			$minLat = max($minLat, $MIN_LAT);
			$maxLat = min($maxLat, $MAX_LAT);
			$minLon = $MIN_LON;
			$maxLon = $MAX_LON;
		}
		$minLon = rad2deg($minLon);
		$minLat = rad2deg($minLat);
		$maxLon = rad2deg($maxLon);
		$maxLat = rad2deg($maxLat);

		return array(
			$minLon,
			$minLat,
			$maxLon,
			$maxLat,
		);
	}
}