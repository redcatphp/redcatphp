<?php namespace surikat\view;
use surikat\view;
use surikat\control;
use surikat\control\FS;
use surikat\control\PHP;
use surikat\control\Min\HTML as minHTML;
use surikat\control\Min\PHP as minPHP;
class FILE {
	static $DIRCWD;
	static $DIRCACHE;
	static $DIRCOMPILE;
	static $FORCECOMPILE;
	static $COMPILE = array();
	static function initialize(){
		self::$DIRCWD = control::$CWD.'view/';
		self::$DIRCOMPILE = control::$TMP.'compile/';
		self::$DIRCACHE = control::$TMP.'cache/';
	}
	var $forceCompile;
	var $path;
	var $dirCwd;
	var $dirCompile;
	var $dirCache;
	var $compile;
	var $toCachePHP = array();
	var $toCacheV = array();
	var $childNodes = array();
	var $isXhtml;
	function __construct($path,$options=array()){
		$this->dirCompile = self::$DIRCOMPILE;
		$this->dirCache = self::$DIRCACHE;
		$this->dirCwd = self::$DIRCWD;
		if(substr($this->dirCwd,-1)!='/')
			$this->dirCwd .= '/';
		$this->compile = self::$COMPILE;
		$this->forceCompile = self::$FORCECOMPILE;
		if(strpos($path,$this->dirCwd)===0)
			$path = substr($path,strlen($this->dirCwd));
		$this->path = $path;
		foreach($options as $k=>$v)
			if(!is_integer($k))
				$this->$k = $v;
	}
	private static $__factory = array();
	static function factoy($path,$options=array(),$instance=0){
		if(!isset(self::$__factory[$path])||!isset(self::$__factory[$path][$instance]))
			self::$__factory[$path][$instance] = new FILE($path,$options);		
		foreach($options as $k=>$v)
			if(!is_integer($k))
				self::$__factory[$path][$instance]->$k = $v;
		return self::$__factory[$path][$instance];
	}
	static function display($path,$options=array()){
		return self::factoy($path,$options)->__display();
	}
	static function evalue($path,$options=array()){
		return self::factoy($path,$options)->__evalue();
	}
	static function evalFree($path,$options=array()){
		return self::factoy($path,$options)->__eval();
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
		if($path=='404.tml'){
		}
		if($file=realpath($local))
			return $file;
		return $this->dirCwd.$path;
	}
	function __prepare(){
		if(!($file=$this->exists()))
			throw new Exception(404);
		$node = new TML(file_get_contents($file),$this);
		foreach($this->compile as $callback)
			call_user_func($callback,$node);
		$this->childNodes[] = $node;
		return $node;
	}
	function __eval(){
		eval('?>'.$this->__prepare());
		return $this;
	}
	function __evalue(){
		$compileFile = $this->dirCompile.$this->path.'.svar';
		if($this->forceCompile||!is_file($compileFile))
			$this->compileStore($compileFile,serialize($ev=$this->__prepare()));
		else
			$ev = unserialize(file_get_contents($compileFile,LOCK_EX));
		eval('?>'.$ev);
		return $this;
	}
	function __display(){
		if($this->forceCompile||!is_file($this->dirCompile.$this->path))
			$this->compilePHP($this->dirCompile.$this->path,(string)$this->__prepare());
		include($this->dirCompile.$this->path);
		return $this;
	}
	function exists(){
		return is_file($this->dirCwd.$this->path)?$this->dirCwd.$this->path:null;
	}
	function mtime($file,$sync,$forceCache=true){
		$file = $this->dirCache.$file;
		$sync = $this->dirCompile.$sync.'.sync';
		if($forceCache&&!is_file($sync))
			file_put_contents($sync,'',LOCK_EX);
		return @filemtime($file)<@filemtime($sync);
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
		$this->toCacheV[] = array($file,$str);
	}
	function cachePHP($file,$str){
		$this->toCachePHP[] = array($file,$str);
	}
	protected function _cacheV($file,$str){
		$file = $this->dirCache.$this->path.'/'.$file;
		if(!control::devHas(control::dev_view))
			$str = minHTML::minify($str);
		return $this->compileStore($file,$str);
	}
	protected function _cachePHP($file,$str){
		//PHP::namespacedConcat($str,true);
		$file = $this->cacheFile($this->path.'/'.$file,'php');
		if(!control::devHas(control::dev_view))
			$str = minPHP::minify($str);
		return $this->compileStore($file,$str);
	}
	function dirCompileToDirCwd($v){
		if(strpos($v,$this->dirCompile)===0)
			$v = $this->dirCwd.substr($v,strlen($this->dirCompile));
		return realpath($v);
	}
	function T_FILE($v){
		return self::dirCompileToDirCwd($v);
	}
	function T_DIR($v){
		return self::dirCompileToDirCwd($v);
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
		if(!control::devHas(control::dev_view))
			$str = minPHP::minify($str);
		return file_put_contents($file,$str,LOCK_EX);
	}
	protected function compilePHP($file,$str){
		foreach($this->toCachePHP as $cache)
			$this->_CachePHP($cache[0],$cache[1]);
		foreach($this->toCacheV as $cache)
			$this->_CacheV($cache[0],$cache[1]);
		return $this->compileStore($file,$str);
	}
}
FILE::initialize();
