<?php namespace Surikat\Dev;
use Surikat\DependencyInjection\Mutator;
class Level{
	use Mutator;
	private $phpDev;
	private $level = 0;
	private $levels;
	const NO = 0;
	const PHP = 2;
	const CONTROL = 4;
	const VIEW = 8;
	const PRESENT = 16;
	const MODEL = 32;
	const DB = 64;
	const DBSPEED = 128;
	const SQL = 256;
	const ROUTE = 512;
	const I18N = 1024;
	const JS = 2048;
	const CSS = 4096;
	const IMG = 8192;
	const STD = 1150; //PHP+CONTROL+VIEW+PRESENT+MODEL+DB+I18N
	const SERVER = 1406; //PHP+CONTROL+VIEW+PRESENT+MODEL+DB+SQL+I18N
	const NAV = 14848; //ROUTE+JS+CSS+IMG
	const ALL = 16382;
	function __construct(){
		$this->levels = [
			'NO'		=> self::NO,
			'PHP'		=> self::PHP,
			'CONTROL'	=> self::CONTROL,
			'VIEW'		=> self::VIEW, 
			'PRESENT'	=> self::PRESENT,
			'MODEL'		=> self::MODEL,
			'DB'		=> self::DB,
			'DBSPEED'	=> self::DBSPEED,
			'SQL'		=> self::SQL,
			'ROUTE'		=> self::ROUTE,
			'I18N'		=> self::I18N,
			'JS'		=> self::JS,
			'CSS'		=> self::CSS,
			'IMG'		=> self::IMG,
			'STD'		=> self::STD,
			'SERVER'	=> self::SERVER,
			'NAV'		=> self::NAV,
			'ALL'		=> self::ALL,
		];
	}
	function valueOf(&$d){
		if(!is_integer($d))
			$d = isset($this->levels[$d])?$this->levels[$d]:null;
	}
	function has($d){
		$this->valueOf($d);
		return !!($d&$this->level);
	}
	function on($d){
		$this->valueOf($d);
		return $this->level($d|$this->level);
	}
	function off($d){
		$this->valueOf($d);
		return $this->level($d^$this->level);
	}
	function __get($k){
		return $this->has($k);
	}
	function __set($k,$b){
		if($b)
			$this->on($k);
		else
			$this->off($k);
	}
	function __call($f,$args){
		$this->__set($f,empty($args)?true:$args[0]);
	}
	function level($l=null){
		$oldLevel = $this->level;
		if(isset($l)){
			$this->level = $l;
			$php = $this->has(self::PHP);
			if(!isset($this->phpDev)||($php&&!$this->phpDev)||(!$php&&$this->phpDev)){
				$this->phpDev = $php;
				$this->getDependency('Dev_Debug')->errorHandler($php);
			}
		}
		return $oldLevel;
	}
}