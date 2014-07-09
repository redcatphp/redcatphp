<?php namespace surikat;
class control{
	static $SURIKAT;
	static $SURIKAT_X;
	static $CWD;
	static $CWD_X;
	static $TMP;
	static $DEV;
	const dev_control = 2;
	const dev_view = 4;
	const dev_present = 8;
	const dev_model = 16;
	const dev_model_compo = 32;
	const dev_model_redbean = 64;
	const dev_model_sql = 128;
	const dev_css = 256;
	const dev_js = 512;
	const dev_img = 1024;
	const dev_default = 30; //control+view+present+model
	const dev_all = 2046;
	static function CWD_X(){
		return func_num_args()?(self::$CWD_X=func_get_arg(0)):self::$CWD_X;
	}
	static function devHas($d){
		return $d&self::$DEV;
	}
	static function devOn($d){
		return self::$DEV = $d&self::$DEV;
	}
	static function devOff($d){
		return self::$DEV = $d^self::$DEV;
	}
	static function dev(){
		$dev = 0;
		$args = func_num_args()?func_get_args():array(self::dev_default);
		foreach($args as $d)
			$dev = $d|$dev;
		self::$DEV = $dev;
		self::errorReport(self::$DEV);
	}
	static function initialize(){
		self::$SURIKAT = substr(__FILE__,0,-11);
		self::$SURIKAT_X = self::$SURIKAT;
		self::$CWD = getcwd().DIRECTORY_SEPARATOR;
		self::$CWD_X = self::$CWD;
		self::$TMP = self::$CWD.'.tmp'.DIRECTORY_SEPARATOR;
		set_include_path('.');
		spl_autoload_register(array('surikat\\control','classLoad'));
		set_exception_handler(array('surikat\\control','catchException'));
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
}
control::initialize();