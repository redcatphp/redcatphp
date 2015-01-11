<?php namespace Surikat\Core;
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
			if(!isset(self::$phpDev)||(self::has(self::PHP)&&!self::$phpDev)||(!self::has(self::PHP)&&self::$phpDev))
				self::errorReport(self::$level);
		}
		return $oldLevel;
	}
	static function errorReport($e=true){
		if($e){
			self::$phpDev = false;
			error_reporting(-1);
			ini_set('display_startup_errors',true);
			ini_set('display_errors','stdout');
			ini_set('html_errors',false);
		}
		else{
			self::$phpDev = true;
			error_reporting(0);
			ini_set('display_startup_errors',false);
			ini_set('display_errors',false);
		}
	}
	static function catchException($e){
		if(!headers_sent())
			header("Content-Type: text/html; charset=utf-8");
		echo '<div style="color:#F00;display:block;position:relative;z-index:99999;">! '.$e->getMessage().' <a href="#" onclick="document.getElementById(\''.($id=uniqid('e')).'\').style.visibility=document.getElementById(\''.$id.'\').style.visibility==\'visible\'?\'hidden\':\'visible\';return false;">StackTrace</a></div><pre id="'.$id.'" style="visibility:hidden;display:block;position:relative;z-index:99999;">';
		echo "\n#".get_class($e);
		if(method_exists($e,'getData')){
			echo ':';
			var_dump($e->getData());
		}
		echo htmlentities($e->getTraceAsString());
		echo '</pre>';
		return false;
	}
}