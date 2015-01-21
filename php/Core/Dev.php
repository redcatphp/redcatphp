<?php namespace Surikat\Core;
use Surikat\Core\Debug;
abstract class Dev{
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
	private static $phpDev;
	private static $level = 0;
	static function has($d){
		return !!($d&self::$level);
	}
	static function on($d){
		return self::level($d|self::$level);
	}
	static function off($d){
		return self::level($d^self::$level);
	}
	static function level($l=null){
		$oldLevel = self::$level;
		if(isset($l)){
			self::$level = $l;
			$php = self::has(self::PHP);
			if(!isset(self::$phpDev)||($php&&!self::$phpDev)||(!$php&&self::$phpDev)){
				self::$phpDev = $php;
				Debug::errorHandler($php);
			}
		}
		return $oldLevel;
	}
}