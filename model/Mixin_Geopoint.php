<?php namespace surikat\model;
use surikat\model\RedBeanPHP\QueryWriter\PostgreSQL;
use surikat\model\RedBeanPHP\QueryWriter\MySQL;
use surikat\control\Geocoding;
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
		list($this->west, $this->south, $this->east, $this->north) = Geocoding::getBoundingBox(array($lat,$lon),$rad,$this->getEarthRadius());
	}
	
}