<?php namespace Surikat\Model;
class FolderVars{
	protected static $factory = [];
	static function factory($dir){
		$k = md5(rtrim($dir,'/'));
		if(!isset(self::$factory[$k]))
			self::$factory[$k] = new FolderVars($dir);
		return self::$factory[$k];
	}
	static $vars_types = ['txt','svar','json','ini','php'];
	static $merge_exts = ['ini'];
	var $key = null;
	var $dir = null;
	var $exists = null;
	function __construct($dir){
		$this->key = rtrim($dir,'/').'/';
		$this->dir = SURIKAT_PATH.'content/'.rtrim($dir,'/').'/';
		$this->exists = is_dir($this->dir);
	}
	function getvars($types=null){
		$vars = [];
		if(!$types){
			$types = self::$vars_types;
		}
		foreach($types as $ext){
			foreach($this->lsfile($ext) as $basename){
				$k = pathinfo($basename,PATHINFO_FILENAME);
				if(in_array($ext,self::$merge_exts)){
					$vars = array_merge($vars,$this->getvar($basename));
				}
				else{
					$vars[$k] = $this->getvar($basename);
				}
			}
		}
		return $vars;
	}
	function __get($key){
		return $this->getvar($key);
	}
	function getvar($key=null){
		if($key===null){
			return $this->getvars();
		}
		$file = $this->dir.$key;
		if(!is_file($file)){
			$data = $this->getvars();
			if(isset($data[$key])){
				return $data[$key];
			}
			return;
		}
		$ext = strtolower(pathinfo($key,PATHINFO_EXTENSION));
		switch($ext){
			default:
			
			break;
			case 'txt':
				return nl2br(file_get_contents($file));
			break;
			case 'ini':
				return @parse_ini_file($file,true);
			break;
			case 'json':
				return @json_decode(file_get_contents($file),true);
			break;
			case 'svar':
				return @unserialize(file_get_contents($file));
			break;
			case 'php':
				return @include($file);
			break;
		}
	}
	function lsdir($dir=null){
		$r = [];
		foreach($this->globdir($dir) as $file){
			$r[] = basename($file);
		}
		return $r;
	}
	function globfile($ext=null){
		$ls = $this->dir;
		if($ext){
			$ext = strtolower(ltrim($ext,'.'));
			$ls .= '{*.'.$ext.',*.'.strtoupper($ext).'}';
			return glob($ls,GLOB_BRACE);
		}
		else{
			$ls .= '*';
			return glob($ls);
		}
	}
	function walker(){
		return new FolderVarsWalker($this);
	}
	function globdir($dir=null){
		$ls = $this->dir;
		if($dir){
			$ls .= rtrim($dir,'/').'/';
		}
		$ls .= '*';
		return glob($ls,GLOB_ONLYDIR);
	}
	function lsfile($ext=null){
		$r = [];
		$files = $this->globfile($ext);
		if($files){
			foreach($files as $file){
				if(is_file($file)){
					$r[] = basename($file);
				}
			}
		}
		return $r;
	}
	function getfile($file){
		if(is_file($this->dir.$file)){
			return file_get_contents($this->dir.$file);
		}
	}
}