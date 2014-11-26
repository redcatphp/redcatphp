<?php namespace Surikat\View;
use Surikat\Factory;
use Surikat\Dev;
use Surikat\View;
use Surikat\Control;
use Surikat\Control\sync;
use Surikat\Control\FS;
use Surikat\Control\PHP;
use Surikat\Control\Min\HTML as minHTML;
use Surikat\Control\Min\PHP as minPHP;
class FILE {
	use factory;
	var $forceCompile;
	var $path;
	var $dirCwd;
	var $dirAdd = [];
	var $dirCompile;
	var $dirCache;
	var $compile = [];
	var $toCachePHP = [];
	var $toCacheV = [];
	var $childNodes = [];
	var $isXhtml;
	var $present;
	function __construct($path=null,$options=null){
		$this->setDirCompile(Control::$TMP.'viewCompile/');
		$this->setDirCache(Control::$TMP.'viewCache/');
		$this->setDirCwd(Control::$CWD.'View/');
		$this->registerDirCwd(Control::$SURIKAT.'View/');
		if(Dev::has(Dev::VIEW))
			$this->forceCompile = true;
		if(isset($path))
			$this->setPath($path);
		if(isset($options))
			$this->setOptions($options);
	}
	function setOptions($options=[]){
		foreach($options as $k=>$v){
			if(is_integer($k))
				continue;
			if(is_array($v)&&is_array($this->$k))
				$this->$k = array_merge($this->$k,$v);
			else
				$this->$k = $v;
		}
	}
	function setPath($path){
		if(strpos($path,$this->dirCwd)===0)
			$path = substr($path,strlen($this->dirCwd));
		$this->path = $path;
	}
	function setDirCompile($d){
		$this->dirCompile = rtrim($d,'/').'/';
	}
	function setDirCache($d){
		$this->dirCache = rtrim($d,'/').'/';
	}
	function setDirCwd($d){
		$this->dirCwd = rtrim($d,'/').'/';
	}
	function getDirCwd(){
		return $this->dirCwd;
	}
	function getAddDirCwd($d){
		return $this->dirAdd;
	}
	function registerDirCwd($d){
		if(!in_array($d,$this->dirAdd))
			$this->dirAdd[] = $d;
	}
	function registerCompile($call){
		$this->compile[] = $call;
	}
	function path($path=null){
		if(!func_num_args())
			return $this->path($this->path);
		if(strpos($path,$this->dirCwd)===0)
			$path = substr($path,strlen($this->dirCwd));
		if(strpos($path,'/')===0&&is_file($file=$this->dirCwd.$path))
			return $file;
		$local = $this->dirCwd.dirname($this->path).'/'.$path;
		if(strpos($path,'./')===0)
			return $local;
		if($file=realpath($local))
			return $file;
		return $this->dirCwd.$path;
	}
	function prepare(){
		if(!($file=$this->find()))
			throw new Exception('404');
		$node = new TML(file_get_contents($file),$this);
		foreach($this->compile as $callback)
			call_user_func($callback,$node);
		$this->childNodes[] = $node;
		return $node;
	}
	function __eval(){
		eval('?>'.$this->prepare());
		return $this;
	}
	function evalue(){
		$compileFile = $this->dirCompile.$this->path.'.svar';
		if($this->forceCompile||!is_file($compileFile))
			$this->compileStore($compileFile,serialize($ev=$this->prepare()));
		else
			$ev = unserialize(file_get_contents($compileFile,LOCK_EX));
		eval('?>'.$ev);
		return $this;
	}
	function display($vars=[]){
		if($this->forceCompile||!is_file($this->dirCompile.$this->path))
			$this->compilePHP($this->dirCompile.$this->path,(string)$this->prepare());
		if(!empty($vars))
			extract($vars);
		include($this->dirCompile.$this->path);
		return $this;
	}
	function find(){
		if(is_file($this->dirCwd.$this->path))
			return $this->dirCwd.$this->path;
		foreach((array)$this->dirAdd as $dir)
			if(is_file($dir.$this->path)){
				$this->dirCwd = $dir;
				return $dir.$this->path;
			}
	}
	function mtime($file,$sync,$forceCache=true){
		return sync::mtime($this->dirCache.$file,$sync,$forceCache);
	}
	function cacheInc($h,$sync=null){
		if(func_num_args()<2)
			return $this->dirCache.$this->path.'/'.$h;
		if($this->mtime($h,$sync))
			return $this->dirCache.$this->path.'/'.$h.'.php';
		else
			readfile($this->dirCache.$this->path.'/'.$h);
	}
	function cacheRegen($file,$str){
		$file = substr($file,0,-4);
		file_put_contents($file,$str,LOCK_EX);
		clearstatcache(true,$file);
		echo $str;
	}
	function cacheV($file,$str){
		$this->toCacheV[] = [$file,$str];
	}
	function cachePHP($file,$str){
		$this->toCachePHP[] = [$file,$str];
	}
	protected function _cacheV($file,$str){
		$file = $this->dirCache.$this->path.'/'.$file;
		if(!Dev::has(Dev::VIEW))
			$str = minHTML::minify($str);
		return $this->compileStore($file,$str);
	}
	protected function _cachePHP($file,$str){
		$file = $this->dirCache.$this->path.'/'.$file.'.php';
		if(!Dev::has(Dev::VIEW))
			$str = minPHP::minify($str);
		return $this->compileStore($file,$str);
	}
	function dirCompileToDirCwd($v){
		if(strpos($v,$this->dirCompile)===0)
			$v = $this->dirCwd.substr($v,strlen($this->dirCompile));
		return realpath($v);
	}
	function T_FILE($v){
		return $this->dirCompileToDirCwd($v);
	}
	function T_DIR($v){
		return $this->dirCompileToDirCwd($v);
	}
	protected static function phpEmulations($str){
		$tokens = token_get_all($str);
		$str = '';
		foreach($tokens as $token)
			switch(is_array($token)?$token[0]:null){
				case T_DIR:
					$str .= '$this->T_DIR(__DIR__)';
				break;
				case T_FILE:
					$str .= '$this->T_FILE(__FILE__)';
				break;
				default:
					$str .= is_array($token)?$token[1]:$token;
				break;
			}
		return $str;
	}
	protected function compileStore($file,$str){
		FS::mkdir($file,true);
		$str = self::phpEmulations($str);
		if(!Dev::has(Dev::VIEW))
			$str = minPHP::minify($str);
		return file_put_contents($file,$str,LOCK_EX);
	}
	protected function compilePHP($file,$str){
		foreach($this->toCachePHP as $cache)
			$this->_cachePHP($cache[0],$cache[1]);
		foreach($this->toCacheV as $cache)
			$this->_cacheV($cache[0],$cache[1]);
		return $this->compileStore($file,$str);
	}
}