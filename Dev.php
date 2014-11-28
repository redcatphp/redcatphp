<?php namespace Surikat;
abstract class Dev{
	const NO = 0;
	const CONTROL = 2;
	const VIEW = 4;
	const PRESENT = 8;
	const MODEL = 16;
	const MODEL_SCHEMA = 32;
	const URI = 64;
	const I18N = 128;
	const JS = 256;
	const CSS = 512;
	const IMG = 1024;
	const STD = 174; //CONTROL+VIEW+PRESENT+MODEL_SCHEMA+I18N
	const SERVER = 190; //CONTROL+VIEW+PRESENT+MODEL+MODEL_SCHEMA+I18N
	const NAV = 1856; //URI+JS+CSS+IMG
	const ALL = 2046;
	private static $level = 78;
	static function has($d){
		return !!($d&self::$level);
	}
	static function on($d){
		return self::$level = $d^self::$level;
	}
	static function off($d){
		return self::$level = $d&self::$level;
	}
	static function level($l=null){
		$oldLevel = self::$level;
		if(isset($l)){
			self::$level = $l;
			self::errorReport(self::$level);
		}
		return $oldLevel;
	}
	static function errorReport($e=true){
		if($e){
			error_reporting(-1);
			ini_set('display_startup_errors',true);
			ini_set('display_errors','stdout');
			ini_set('html_errors',false);
		}
		else{
			error_reporting(0);
			ini_set('display_startup_errors',false);
			ini_set('display_errors',false);
		}
	}
	static function catchException($e){
		echo '<div style="color:#F00;display:block;position:relative;z-index:99999;">! '.$e->getMessage().' <a href="#" onclick="document.getElementById(\''.($id=uniqid('e')).'\').style.visibility=document.getElementById(\''.$id.'\').style.visibility==\'visible\'?\'hidden\':\'visible\';return false;">StackTrace</a></div><pre id="'.$id.'" style="visibility:hidden;display:block;position:relative;z-index:99999;">'.htmlentities($e->getTraceAsString()).'</pre>';
		return false;
	}
}