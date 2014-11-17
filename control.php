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
		
		self::addSuperNamespace('surikat',self::$SURIKAT_X);
		self::addNamespace('',self::$CWD_X);
		spl_autoload_register(['surikat\\control','classLoad']);
		
		set_exception_handler(['surikat\\dev','catchException']);
	}
	
	private static $namespaces = [];
	private static $superNamespaces = [];
	static function addNamespace($prefix, $base_dir, $prepend = false){
		$prefix = trim($prefix, '\\').'\\';
		$base_dir = rtrim($base_dir, '/').'/';
		if(!isset(self::$namespaces[$prefix]))
			self::$namespaces[$prefix] = [];
		if ($prepend)
			array_unshift(self::$namespaces[$prefix], $base_dir);
		else
			array_push(self::$namespaces[$prefix], $base_dir);
	}
	static function addSuperNamespace($prefix, $base_dir, $prepend = false){
		$prefix = trim($prefix, '\\').'\\';
		$base_dir = rtrim($base_dir, '/').'/';
		if(!isset(self::$superNamespaces[$prefix]))
			self::$superNamespaces[$prefix] = [];
		if ($prepend)
			array_unshift(self::$superNamespaces[$prefix], $base_dir);
		else
			array_push(self::$superNamespaces[$prefix], $base_dir);
	}
	static function classLoad($class){
		$prefix = $class;
		while($prefix!='\\'){
			$prefix = rtrim($prefix, '\\');
			$pos = strrpos($prefix, '\\');
			if($pos!==false){
				$prefix = substr($class, 0, $pos + 1);
				$relative_class = substr($class, $pos + 1);
				if(isset(self::$superNamespaces[$prefix])){
					foreach(self::$superNamespaces[$prefix] as $base_dir){
						$file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
						if(self::requireFile($file))
							return;
					}
					return;
				}
			}
			else{
				$prefix = '\\';
				$relative_class = $class;
			}
			if(isset(self::$namespaces[$prefix])){
				foreach(self::$namespaces[$prefix] as $base_dir){
					$file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
					if(self::requireFile($file))
						return;
				}
			}
		}
		self::extendSuperClass($class);
	}
	private static function requireFile($file){
		if(file_exists($file)){
			require $file;
			return true;
		}
		return false;
	}
	private static function extendSuperClass($c){		
		foreach(array_keys(self::$superNamespaces) as $prefix){
			$ns = (($pos=strrpos($c,'\\'))?substr($c,0,$pos):'');
			$cn = ($pos?substr($c,$pos+1):$c);
			$trait = strpos($cn,'Mixin_')===0;
			$cf = $trait?'trait_exists':'class_exists';
			if($cf($prefix.$c)){
				if($trait)
					$ev = 'trait '.$cn.' { use \\'.$prefix.$c.'; }';
				else
					$ev = ((strpos($cn,'Interface_')===0)?'interface':'class').' '.$cn.' extends \\'.$prefix.$c.'{}';
				eval('namespace '.$ns.'{ '.$ev.'}');
				break;
			}
		}
	}
}
control::initialize();