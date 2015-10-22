<?php
/*
 * Templix - HTML5 based Template Engine with Recursive Extends and CSS3 Selectors to work on DOM like with Jquery
 *
 * @package Templix
 * @version 1.6
 * @link http://github.com/surikat/Templix/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
namespace Wild\Templix;
class Templix implements \ArrayAccess {
	
	private $foundPath;
	
	protected $cleanCallback;
	protected $devCompileFile;
	protected $dirCompileSuffix = '';
	protected $__pluginPrefix = [];
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
	
	public $devTemplate;
	public $devJs;
	public $devCss;
	public $devImg;
	
	function __construct($file=null,$vars=null,
		$devTemplate=true,$devJs=true,$devCss=true,$devImg=false
	){
		$this->devTemplate = $devTemplate;
		$this->devCss = $devCss;
		$this->devJs = $devJs;
		$this->devImg = $devImg;
		
		$this->setDirCompile('.tmp/templix/compile/');
		$this->setDirCache('.tmp/templix/cache/');
		$this->setDirSync('.tmp/sync/');
		$this->addDirCwd([
			'template/',
			'surikat/template/',
		]);
		$this->setPluginPrefix(self::getPluginPrefixDefault());
		if(isset($file))
			$this->setPath($file);
		if(isset($vars))
			$this->set($vars);
	}
	function setDevTemplate($b){
		$this->devTemplate = $b;
	}
	function setDevCss($b){
		$this->devCss = $b;
	}
	function setDevJs($b){
		$this->devJs = $b;
	}
	function setDevImg($b){
		$this->devImg = $b;
	}
	function getPluginPrefix(){
		return $this->__pluginPrefix;
	}
	static function getPluginPrefixDefault(){
		return [
			__NAMESPACE__.'\\MarkupX\\',
			__NAMESPACE__.'\\MarkupHtml5\\',
			__NAMESPACE__.'\\',
		];
	}
	function setPluginPrefix($prefixs){
		$this->__pluginPrefix = (array)$prefixs;
	}
	function addPluginPrefix($prefix,$prepend=true){
		if($prepend)
			array_unshift($this->__pluginPrefix,$prefix);
		else
			array_push($this->__pluginPrefix,$prefix);
	}
	function appendPluginPrefix($prefix){
		$this->addPluginPrefix($prefix,false);
	}
	function prependPluginPrefix($prefix){
		$this->addPluginPrefix($prefix,true);
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
	function onCompile($call,$append=false){
		if(is_integer($append))
			$this->compile[$append] = $call;
		elseif($append)
			array_push($this->compile,$call);
		else
			array_unshift($this->compile,$call);
	}
	function removeTmp($node){
		$node('[tmp-wrap]')->each(function($el){
			$el->replaceWith($el->getInnerMarkups());
		});
		$node('[tmp-tag]')->remove();
		$node('[tmp-attr]')->removeAttr('tmp-attr');
		$node('[tmp-once]')->removeAttr('tmp-once');
	}
	function devRegeneration(){
		$exist = is_file($this->devCompileFile);
		if($exist){
			if(!$this->devTemplate){
				unlink($this->devCompileFile);
				self::rmdir($this->dirCompile.$this->dirCompileSuffix);
				self::rmdir($this->dirCache.$this->dirCompileSuffix);
				if($this->cleanCallback)
					call_user_func($this->cleanCallback,$this);
			}
		}
		else{
			if($this->devTemplate){
				@mkdir(dirname($this->devCompileFile),0777,true);
				file_put_contents($this->devCompileFile,'');
			}
			if($this->cleanCallback&&($this->devCss||$this->devJs))
				call_user_func($this->cleanCallback,$this);
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
		if((!isset($this->forceCompile)&&$this->devTemplate)||!is_file($this->dirCompile.$this->dirCompileSuffix.$file))
			$this->writeCompile();
		$this->includeVars($this->dirCompile.$this->dirCompileSuffix.$file,$this->vars);
		return $this;
	}
	
	function writeCompile(){
		$file = $this->getPath();
		$node = new Markup();
		$node->setTemplix($this);		
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
		if(!$this->devTemplate)
			$str = Minify::HTML($str);
		return $this->compileStore($file,$str);
	}
	protected function _cachePHP($file,$str){
		$file = $this->dirCache.$this->dirCompileSuffix.$this->path.'/'.$file.'.php';
		if(!$this->devTemplate)
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
		if(!$this->devTemplate)
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
	function setCleanCallback($f){
		$this->cleanCallback = $f;
	}
	static function rmdir($dir){
		if(is_dir($dir)){
			$dh = opendir($dir);
			if($dh){
				while(false!==($file=readdir($dh))){
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
	function __clone(){
		foreach($this->childNodes as $i=>$node){
			$this->childNodes[$i] = clone $node;
		}
	}
}