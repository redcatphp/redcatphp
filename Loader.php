<?php namespace Surikat;
abstract class Loader{
	private static $namespaces = [];
	private static $superNamespaces = [];
	private static $checked = [];
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
		if(in_array($class,self::$checked))
			return;
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
						if(self::requireFile($file)){
							self::$checked[] = $class;
							return;
						}
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
					if(self::requireFile($file)){
						self::$checked[] = $class;
						return;
					}
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
		$pos = strrpos($c,'\\');
		$ns = 'namespace '.($pos?substr($c,0,$pos):'').'{';
		$cn = ($pos?substr($c,$pos+1):$c);
		foreach(array_keys(self::$superNamespaces) as $prefix){
			$cl = $prefix.$c;
			if(class_exists($cl)){
				eval($ns.'class '.$cn.' extends \\'.$cl.'{}}');
				break;
			}
			elseif(interface_exists($cl,false)){
				eval($ns.'interface '.$cn.' extends \\'.$cl.'{}}');
				break;
			}
			elseif(trait_exists($cl,false)){
				eval($ns.'trait '.$cn.'{use \\'.$cl.';}}');
				break;
			}
		}
	}
}
if(!defined('SURIKAT_PATH'))
	define('SURIKAT_PATH',getcwd().'/');
if(!defined('SURIKAT_SPATH'))
	define('SURIKAT_SPATH',__DIR__.'/');
if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');
if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');
Loader::addNamespace('',SURIKAT_PATH);
Loader::addSuperNamespace('Surikat',SURIKAT_SPATH);
set_include_path('.');
spl_autoload_register(['Surikat\\Loader','classLoad']);
set_exception_handler(['Surikat\\Dev','catchException']);