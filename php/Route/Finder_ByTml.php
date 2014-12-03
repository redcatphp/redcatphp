<?php namespace Surikat\Route;
use ArrayAccess;
class Finder_ByTml extends Finder implements ArrayAccess{
	function match($url){
		$this->setPath($url);
		if(is_file(SURIKAT_PATH.'tml/'.$this[0].'.tml'))
			return $this->getParam();
	}
	
	private static $__instances = [];
	static function getInstance($k=0){
		if(!isset(self::$__instances[$k]))
			self::$__instances[$k] = new self();
		return self::$__instances[$k];
	}
	
	protected $separatorWord = '-';
	protected $forbiddenChrParam = [
		'?','%',',','!','^','¨','#','~',"'",'"',"\r","\n","\t"," ",
		'{','(','_','$','@',')',']','}','=','+','$','£','*','µ','§','/',
		';','.'
	];
	protected $separators = [
		'Eq'=>':',
		'And'=>'+',
		'Or'=>'&',
	];
	protected $resolved;
	protected $baseHref;
	protected $lang;
	protected static $baseHrefSuffix = '';
	static function getSubdomain($domain=null){
		if(!isset($domain))
			$domain = $_SERVER['HTTP_HOST'];
		$urlParts = explode('.', $domain);
		if(count($urlParts)>2)
			return $urlParts[0];
		else
			return null;
	}
	static function baseHref($href=null,$k=0){
		return func_num_args()?self::getInstance($k)->setBaseHref($href):self::getInstance($k)->getBaseHref();
	}
	static function lang($l=null,$k=0){
		return func_num_args()?self::getInstance($k)->setLang($l):self::getInstance($k)->getLang();
	}
	static function param($k=null){
		return self::getInstance()->getParam($k);
	}
	static function resolved($k=null){
		return self::getInstance()->getResolved($k);
	}
	static function encode($v){
		return str_replace('%2F','/',urlencode(urldecode(trim($v))));
	}
	function filterParam($s){
		$s = trim($s);
		$s = strip_tags($s);
		$forbid = array_merge(array_values($this->separators),$this->forbiddenChrParam);
		$s = str_replace($forbid,$this->separatorWord,$s);
		$s = trim($s,$this->separatorWord);
		if(strpos($s,$this->separatorWord.$this->separatorWord)!==false){
			$ns = '';
			$l = strlen($s)-1;
			for($i=0;$i<=$l;$i++)
				if(!($i&&$s[$i]==$this->separatorWord&&$s[$i-1]==$this->separatorWord))
					$ns .= $s[$i];
			$s = $ns;
		}
		return $s;
	}
	
	protected $PATH;
	protected $uriParams = [];
	protected $resolvedParams = [];
	protected $orderParams = [];
	function __construct($path=null){
		if(isset($path))
			$this->setPath($path);
	}
	function setPath($path){
		$this->PATH = (string)$path;
		$this->uriParams = $this->parseUriParams(ltrim($this->PATH,'/'));
		foreach(array_keys($this->uriParams) as $k)
			$this->resolvedParams[$k] = null;
	}
	static function autoBaseHref(){
		$ssl = @$_SERVER["HTTPS"]=="on";
		$port = @$_SERVER['SERVER_PORT']&&((!$ssl&&(int)$_SERVER['SERVER_PORT']!=80)||($ssl&&(int)$_SERVER['SERVER_PORT']!=443))?':'.$_SERVER['SERVER_PORT']:'';
		return 'http'.($ssl?'s':'').'://'.$_SERVER['SERVER_NAME'].$port.'/'.static::$baseHrefSuffix;
	}
	function setBaseHref($href=null){
		if(!isset($href))
			$href = static::autoBaseHref();
		$this->baseHref = $href;
	}
	function getBaseHref(){
		if(!isset($this->baseHref))
			$this->baseHref = static::autoBaseHref();
		return $this->baseHref;
	}
	function setLang($lang){
		$this->lang = $lang;
	}
	function getLang(){
		return $this->lang;
	}
	function getPath(){
		return $this->PATH;
	}
	function parseUriParams($path){
		$uriParams = [];
		$min = [];
		if(($pos=strpos($path,$this->separators['Eq']))!==false)
			$min[] = $pos;
		if(($pos=strpos($path,$this->separators['And']))!==false)
			$min[] = $pos;
		if(!empty($min)){
			$sepDir = min($min);
			$uriParams[0] = substr($path,0,$sepDir);
			$path = substr($path,$sepDir);
			$x = explode($this->separators['And'],$path);
			foreach($x as $v){
				$x2 = explode($this->separators['Or'],$v);
				if($k=$i=strpos($v,$this->separators['Eq'])){
					$k = substr($v,0,$i);
					$v = substr($v,$i+1);
				}
				$v = strpos($v,$this->separators['Or'])?explode($this->separators['Or'],$v):$v;
				if($k)
					$uriParams[$k] = $v;
				elseif(!empty($v))
					$uriParams[] = $v;
			}
		}
		else
			$uriParams[0] = $path;
		return $uriParams;
	}
	function getParam($k=null){
		return $k===null?$this->uriParams:(isset($this->uriParams[$k])?$this->uriParams[$k]:null);
	}
	function __toString(){
		return (string)$this->PATH;
	}
	function __set($k,$v){
		$this->uriParams[$k] = $v;
	}
	function __get($k){
		return $this->param($k);
	}
	function __isset($k){
		return isset($this->uriParams[$k]);
	}
	function __unset($k){
		if(isset($this->uriParams[$k]))
			unset($this->uriParams[$k]);
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
	static function __set_state($a){
		$uri = new self();
		foreach($a as $k=>$v)
			$uri->$k = $v;
		return $uri;
	}
}