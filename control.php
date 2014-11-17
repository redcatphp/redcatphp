<?php namespace surikat;
class control{
	static $SURIKAT;
	static $SURIKAT_X;
	static $CWD;
	static $CWD_X;
	static $TMP;
	static function CWD_X(){
		return func_num_args()?(self::$CWD_X=func_get_arg(0)):self::$CWD_X;
	}
	static function initialize(){
		self::$SURIKAT = substr(__FILE__,0,-11);
		self::$SURIKAT_X = self::$SURIKAT;
		self::$CWD = getcwd().DIRECTORY_SEPARATOR;
		self::$CWD_X = self::$CWD;
		self::$TMP = self::$CWD.'.tmp'.DIRECTORY_SEPARATOR;
		set_include_path('.');
		spl_autoload_register(['surikat\\control','classLoad']);
		set_exception_handler(['surikat\\dev','catchException']);
	}
	static function classLoad($c){
		$f = str_replace('\\',DIRECTORY_SEPARATOR,$c).'.php';
		if(stripos($c,__NAMESPACE__.'\\')===0)
			is_file($php=self::$SURIKAT_X.substr($f,8))&&include($php);
		elseif(!(is_file($php=self::$CWD_X.$f)&&include($php))){
			$ns = (($pos=strrpos($c,'\\'))?substr($c,0,$pos):'');
			$cn = ($pos?substr($c,$pos+1):$c);
			$trait = strpos($cn,'Mixin_')===0;
			$cf = $trait?'trait_exists':'class_exists';
			if($cf(__NAMESPACE__.'\\'.$c)){
				if($trait)
					$ev = 'trait '.$cn.' { use \\'.__NAMESPACE__.'\\'.$c.'; }';
				else
					$ev = ((strpos($cn,'Interface_')===0)?'interface':'class').' '.$cn.' extends \\'.__NAMESPACE__.'\\'.$c.'{}';
				eval('namespace '.$ns.'{ '.$ev.'}');
			}
		}
	}
	static function autoload($dir){
		spl_autoload_register(function($className)use($dir){
			is_file($f=$dir.DIRECTORY_SEPARATOR.str_replace('\\',DIRECTORY_SEPARATOR,$className).'.php')&&include($f);
		});
	}
}
control::initialize();