<?php
namespace Templix;
class Templix implements \ArrayAccess {
	
	const DEV_TEMPLATE = 2;
	const DEV_JS = 4;
	const DEV_CSS = 8;
	const DEV_IMG = 16;
	const DEV_CHRONO = 32;
	
	private $foundPath;
	private $devLevel = 46;

	protected $cleanRegister;
	protected $devCompileFile;
	protected $dirCompileSuffix = '';
	protected $__pluginNamespaces = [];
	protected $vars = [];
	
	public $forceCompile;
	public $path;
	public $parent;
	public $dirCwd = [];
	public $dirCompile;
	public $dirCache;
	public $dirSync;
	public $compile = [];
	public $toCachePHP = [];
	public $toCacheV = [];
	public $childNodes = [];
	public $isXhtml;
	
	function __construct($file=null,$vars=null,$options=null){
		$this->setDirCompile('.tmp/templix/compile/');
		$this->setDirCache('.tmp/templix/cache/');
		$this->setDirSync('.tmp/sync/');
		$this->addDirCwd([
			'template/',
			'Surikat/template/',
		]);
		$this->setCleanRegister('.tmp/synaptic/min-registry.txt');
		$this->setPluginNamespace(self::getPluginNamespaceDefault());
		if(isset($file))
			$this->setPath($file);
		if(isset($vars))
			$this->set($vars);
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
	function getPluginNamespace(){
		return $this->__pluginNamespaces;
	}
	static function getPluginNamespaceDefault(){
		return [
			'',
			__NAMESPACE__.'\\MarkupX',
			__NAMESPACE__.'\\MarkupHtml5',
			__NAMESPACE__,
		];
	}
	function setPluginNamespace($namespace){
		$this->__pluginNamespaces = (array)$namespace;
	}
	function addPluginNamespace($namespace,$prepend=null){
		if($prepend)
			array_unshift($this->__pluginNamespaces,$namespace);
		else
			array_push($this->__pluginNamespaces,$namespace);
	}
	function appendPluginNamespace($namespace){
		$this->addPluginNamespace($namespace,true);
	}
	function prependPluginNamespace(){
		$this->addPluginNamespace($namespace,false);
	}
	function getAncestor($defaulThis=false){
		$ancestor = $this;
		do{
			$ancestor = $ancestor->parent;
		}
		while($ancestor->parent);
		if(!$ancestor&&$defaulThis)
			return $this;
		return $ancestor;
	}
	function getParent($defaulThis=false){
		if(!$this->parent&&$defaulThis)
			return $this;
		return $this->parent;
	}
	function setParent($templix){
		$this->parent = $templix;
	}
	function setDirCompile($d){
		$this->dirCompile = rtrim($d,'/').'/';
		$this->devCompileFile = $this->dirCompile.'.dev';
	}
	function setDirCompileSuffix($suffix){
		$this->dirCompileSuffix .= $suffix;
	}
	function setDirCache($d){
		$this->dirCache = rtrim($d,'/').'/';
	}
	function setDirSync($d){
		$this->dirSync = rtrim($d,'/').'/';
	}
	function setDirCwd($d){
		$this->dirCwd = [];
		$this->addDirCwd($d);
	}
	function getDirCwd(){
		return $this->dirCwd;
	}
	function addDirCwd($d){
		foreach((array)$d as $dir)
			if(!in_array($dir,$this->dirCwd))
				$this->dirCwd[] = rtrim($dir,'/').'/';
	}
	function onCompile($call,$prepend=false){
		if(is_integer($prepend))
			$this->compile[$prepend] = $call;
		elseif($prepend)
			array_push($this->compile,$call);
		else
			array_unshift($this->compile,$call);
	}
	function removeTmp($node){
		$node('[tmp-wrap]')->each(function($el){
			$el->replaceWith($el->getInnerTml());
		});
		$node('[tmp-tag]')->remove();
		$node('[tmp-attr]')->removeAttr('tmp-attr');
	}
	function devRegeneration(){
		$exist = is_file($this->devCompileFile);
		if($exist){
			if(!$this->devLevel&self::DEV_TEMPLATE){
				unlink($this->devCompileFile);
				self::rmdir($this->dirCompile.$this->dirCompileSuffix);
				self::rmdir($this->dirCache.$this->dirCompileSuffix);
				$this->cleanRegisterAuto();
			}
		}
		else{
			if($this->devLevel&self::DEV_TEMPLATE){
				@mkdir(dirname($this->devCompileFile),0777,true);
				file_put_contents($this->devCompileFile,'');
			}
			if($this->devLevel&self::DEV_CSS)
				$this->cleanRegisterAuto('css');
			if($this->devLevel&self::DEV_JS)
				$this->cleanRegisterAuto('js');
		}
	}
	function fetch($file=null,$vars=[]){
		ob_start();
		$this->display($file,$vars);
		return ob_get_clean();
	}
	function display($file=null,$vars=[]){
		if(isset($file))
			$this->setPath($file);
		$file = $this->getPath();
		if(!$file)
			throw new TemplixException('<display "'.$this->path.'"> template not found ');
		if(!empty($vars))
			$this->vars = array_merge($this->vars,$vars);
		$this->devRegeneration();
		if((!isset($this->forceCompile)&&$this->devLevel&self::DEV_TEMPLATE)||!is_file($this->dirCompile.$this->dirCompileSuffix.$file))
			$this->writeCompile();
		$this->includeVars($this->dirCompile.$this->dirCompileSuffix.$file,$this->vars);
		return $this;
	}
	function writeCompile(){
		$file = $this->getPath();
		$node = new Tml();
		$node->settemplix($this);		
		$node->parse(file_get_contents($file));
		ksort($this->compile);
		foreach($this->compile as $callback)
			call_user_func($callback,$node);
		$this->removeTmp($node);
		$this->childNodes[] = $node;
		$this->compilePHP($this->dirCompile.$this->dirCompileSuffix.$file,(string)$node);
	}
	function includeVars(){
		if(func_num_args()>1&&count(func_get_arg(1)))
			extract(func_get_arg(1));
		return include(func_get_arg(0));
	}
	function setPath($path){
		$this->path = $path;
		return $this->foundPath = $this->findPath($path);
	}
	function getPath($origin=false){
		return $origin?$this->path:$this->foundPath;
	}
	function exists(){
		if(func_num_args()){
			foreach(func_get_args() as $path){
				if($this->findPath($path)){
					return true;
				}
			}
		}
		elseif($this->foundPath){
			return true;
		}
	}
	function findPath($path){
		if(strpos($path,'//')===0&&is_file($path=substr($path,1))){
			return $path;
		}
		else{
			foreach($this->dirCwd as $d){
				if(strpos($path,'/')===0&&is_file($file=$d.$path))
					return $file;
				$template = $this;
				do{
					$local = $d.dirname($template->path).'/'.$path;
					if(is_file($local)){
						return str_replace('/./','/',$local);
					}
				}
				while($template=$template->parent);
				if(strpos($path,'./')!==0&&is_file($file=$d.$path)){
					return $file;
				}
			}
		}
	}
	function mtime($file,$sync,$forceCache=true){
		$syncF = $this->dirSync.$sync.'.sync';
		if($forceCache&&!is_file($syncF)){
			@mkdir(dirname($syncF),0777,true);
			file_put_contents($syncF,'');
		}
		return @filemtime($this->dirCache.$this->dirCompileSuffix.$file)<@filemtime($syncF);
	}
	function cacheInc($h,$sync=null){
		if(func_num_args()<2)
			return $this->dirCache.$this->dirCompileSuffix.$this->path.'/'.$h;
		if($this->mtime($h,$sync))
			return $this->dirCache.$this->dirCompileSuffix.$this->path.'/'.$h.'.php';
		else
			readfile($this->dirCache.$this->dirCompileSuffix.$this->path.'/'.$h);
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
		$file = $this->dirCache.$this->dirCompileSuffix.$this->path.'/'.$file;
		if(!$this->devLevel&self::DEV_TEMPLATE)
			$str = Minify::HTML($str);
		return $this->compileStore($file,$str);
	}
	protected function _cachePHP($file,$str){
		$file = $this->dirCache.$this->dirCompileSuffix.$this->path.'/'.$file.'.php';
		if(!$this->devLevel&self::DEV_TEMPLATE)
			$str = Minify::PHP($str);
		return $this->compileStore($file,$str);
	}
	function dirCompileToDirCwd($v){
		$dirC = rtrim($this->dirCompile.$this->dirCompileSuffix,'/');
		$path = $v;
		$cwd = getcwd();
		if(strpos($path,$cwd)===0)
			$path = ltrim(substr($path,strlen($cwd)),'/');
		if(strpos($path,$dirC)===0)
			$path = ltrim(substr($path,strlen($dirC)),'/');
		$path = realpath($path);
		return $path?$path:$v;
	}
	protected function compileStore($file,$str){
		if(!is_dir($dir=dirname($file)))
			@mkdir($dir,0777,true);
		if(!$this->devLevel&self::DEV_TEMPLATE)
			$str = Minify::PHP($str);
		return file_put_contents($file,$str,LOCK_EX);
	}
	protected function compilePHP($file,$str){
		foreach($this->toCachePHP as $cache)
			$this->_cachePHP($cache[0],$cache[1]);
		foreach($this->toCacheV as $cache)
			$this->_cacheV($cache[0],$cache[1]);		
		return $this->compileStore($file,$str);
	}
    function get($key=null){
		if(!func_num_args())
			return $this->vars;
        return isset($this->vars[$key])?$this->vars[$key]:null;
    }
    function set($key, $value = null) {
        if(is_array($key)||is_object($key)){
            foreach ($key as $k => $v)
                $this->vars[$k] = $v;
        }
        else
            $this->vars[$key] = $value;
    }
    function has($key) {
        return isset($this->vars[$key]);
    }
    function clear($key = null){
        if (is_null($key))
            $this->vars = [];
        else
            unset($this->vars[$key]);
    }
    
    function getVars(){
		return $this->vars;
	}
    function __set($k,$v){
		$this->vars[$k] = $v;
	}
	function __get($k){
		return isset($this->vars[$k])?$this->vars[$k]:null;
	}
	function __isset($k){
		return isset($this->vars[$k]);
	}
	function __unset($k){
		if(isset($this->vars[$k]))
			unset($this->vars[$k]);
	}
	function offsetSet($k,$v){
		return $this->__set($k,$v);
	}
	function offsetGet($k){
		return $this->__get($k);
	}
	function offsetExists($k){
		return $this->__isset($k);
	}
	function offsetUnset($k){
		return $this->__unset($k);
	}
	function setCleanRegister($f){
		$this->cleanRegister = $f;
	}
	function getCleanRegister(){
		return $this->cleanRegister;
	}
	function cleanRegisterAuto($ext=null){
		if(!$this->cleanRegister||!is_file($this->cleanRegister))
			return;
		foreach(file($this->cleanRegister) as $file){
			$file = trim($file);
			if(empty($file))
				continue;
			if($ext&&$ext!=pathinfo($file,PATHINFO_EXTENSION))
				continue;
			$file = realpath($file);
			if($file)
				unlink($file);
		}
		unlink($this->cleanRegister);
	}
	static function rmdir($dir){
		if(is_dir($dir)){
			$dh = opendir($dir);
			if($dh){
				while($file=readdir($dh)){
					if($file!='.'&&$file!='..'){
						$fullpath = $dir.'/'.$file;
						if(is_file($fullpath))
							unlink($fullpath);
						else
							self::rmdir($fullpath);
					}
				}
				closedir($dh);
			}
		}
		return is_dir($dir);
	}
	function devLevel(){
		if(func_num_args()){
			$this->devLevel = 0;
			foreach(func_get_args() as $l){
				$this->devLevel = $this->devLevel|$l;
			}
		}
		return $this->devLevel;
	}
}