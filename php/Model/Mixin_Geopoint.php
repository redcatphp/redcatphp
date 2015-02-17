<?php namespace Surikat\Model;
use Surikat\Model\RedBeanPHP\QueryWriter\PostgreSQL;
use Surikat\Model\RedBeanPHP\QueryWriter\MySQL;
use Surikat\Geo\Geocoding;
use Surikat\Validation\Ruler;
trait Mixin_Geopoint{
	static $columnPointCast	= 'point';
	static $columnPointWriteCol;
	static $columnPointReadCol;
	private $_pointPrefix = '';
	private $_pointSeparator = ',';
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
		if(is_string($this->earthRadius))
			$this->earthRadius = Geocoding::getEarthRadius($this->earthRadius);
		return $this->earthRadius;
	}
	function checkLat($lat,$nul=true){
		if($lat!=''){
			if(!Ruler::float($lat))
				return false;
			$lat = (float)$lat;
		}
		if(($nul&&(!isset($lat)||$lat===false))||($lat<=90.0&&$lat>=-90.0))
			return $lat;
		return false;
	}
	function checkLon($lon,$nul=true){
		if($lon!=''){
			if(!Ruler::float($lon))
				return false;
			$lon = (float)$lon;
		}
		if(($nul&&(!isset($lon)||$lon===false))||($lon<=180.0&&$lon>=-180.0))
			return $lon;
		return false;
	}
	function LatLon2Point($lat,$lon){
		$lat = str_replace(',','.',$lat);
		$lon = str_replace(',','.',$lon);
		return $this->_pointPrefix.'('.$lat.$this->_pointSeparator.$lon.')';
	}
	function setPoint($lat,$lon){
		if($lat&&$lon)
			$this->point = $this->LatLon2Point($lat,$lon);
	}
	function setBounds($lat,$lon,$rad){
		if($lat&&$lon)
			list($this->minlon, $this->minlat, $this->maxlon, $this->maxlat) = Geocoding::getBoundingBox([$lat,$lon],$rad,$this->getEarthRadius());
	}
	
}