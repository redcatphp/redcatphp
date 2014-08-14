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
		'km'=>6371
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
		if(!$rad)
			$rad = 0;
		$R = $this->getEarthRadius();
		$latVector = $rad?rad2deg($rad/$R):0;
		$lonVector = $rad?rad2deg($rad/$R/cos(deg2rad($lat))):0;
		$minLat = $lat - $latVector;
		$maxLat = $lat + $latVector;
		$minLon = $lon - $lonVector;
		$maxLon = $lon + $lonVector;
		$this->south = $minLat;
		$this->west = $minLon;
		$this->north = $maxLat;
		$this->east = $maxLon;
	}
}