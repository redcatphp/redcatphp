<?php namespace Surikat\Config;
abstract class Dev{
	const NO = 0;
	const PHP = 2;
	const CONTROL = 4;
	const VIEW = 8;
	const PRESENT = 16;
	const MODEL = 32;
	const MODEL_SCHEMA = 64;
	const URI = 128;
	const I18N = 256;
	const JS = 512;
	const CSS = 1024;
	const IMG = 2048;
	const STD = 350; //PHP+CONTROL+VIEW+PRESENT+MODEL_SCHEMA+I18N
	const SERVER = 382; //PHP+CONTROL+VIEW+PRESENT+MODEL+MODEL_SCHEMA+I18N
	const NAV = 3712; //URI+JS+CSS+IMG
	const ALL = 4094;
	private static $level = 78;
	static function has($d){
		return !!($d&self::$level);
	}
	static function on($d){
		return self::level($d^self::$level);
	}
	static function off($d){
		return self::level($d&self::$level);
	}
	static function level($l=null){
		$oldLevel = self::$level;
		if(isset($l)){
			self::$level = $l;
			if(self::has(self::PHP))
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