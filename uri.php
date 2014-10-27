<?php namespace surikat;
use ArrayAccess;
class uri implements ArrayAccess{
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
	private static $__factory = [];
	static function getSubdomain($domain=null){
		if(!isset($domain))
			$domain = $_SERVER['HTTP_HOST'];
		$urlParts = explode('.', $domain);
		if(count($urlParts)>2)
			return $urlParts[0];
		else
			return null;
	}
	static function factory($k=0,$path=null){
		if(!isset(self::$__factory[$k]))
			self::$__factory[$k] = new URI($path);
		return self::$__factory[$k];
	}
	static function baseHref($href=null,$k=0){
		return func_num_args()?self::factory($k)->setBaseHref($href):self::factory($k)->getBaseHref();
	}
	static function lang($l=null,$k=0){
		return func_num_args()?self::factory($k)->setLang($l):self::factory($k)->getLang();
	}
	static function param($k=null){
		return self::factory()->getParam($k);
	}
	static function resolved($k=null){
		return self::factory()->getResolved($k);
	}
	static function filterParam($s){
		return self::factory()->getFilterParam($s);
	}
	static function encode($v){
		return str_replace('%2F','/',urlencode(urldecode(trim($v))));
	}
	function getFilterParam($s){
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
	function __construct($path=''){
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

	function resolveParam($k,$callback){
		if($k==':int'){
			$r = true;
			foreach(array_keys($this->uriParams) as $i)
				if($i&&is_integer($i)&&!$this->resolveParam($i,$callback))
					$r = false;
			return $r;
		}
		if($callback===true||($value = $this->getParam($k))===null)
			$this->resolvedParams[$k] = true;
		elseif(is_callable($callback))
			$this->resolvedParams[$k] = call_user_func($callback,$value);
		else
			$this->resolvedParams[$k] = false;
		return $this->resolvedParams[$k];
	}
	function orderParams($orderParams=null){
		if(func_num_args())
			$this->orderParams = (array)$orderParams;
		return $this->orderParams;
	}
	function validatedUri($orderParams=null){
		return self::orderedUri($orderParams,true);
	}
	function orderedUri($orderParams=null,$validate=null){
		if(isset($orderParams))
			$this->orderParams($orderParams);
		$uri = $this->getParam(0);
		foreach($this->orderParams as $k=>$callback){
			if(is_integer($k)){
				$k = $callback;
				$callback = null;
			}
			if($k==':int'){
				$ints = [];
				foreach(array_keys($this->uriParams) as $i){
					if($i
						&&is_integer($i)
						&&!in_array($this->uriParams[$i],$ints)
						&&(!$validate||$this->resolvedParams[$i])
					){
						if(is_array($this->uriParams[$i])){
							$uri .= $this->separators['And'].implode($this->separators['Or'],$this->uriParams[$i]);
						}
						else{
							$uri .= $this->separators['And'].$this->uriParams[$i];
						}
						$ints[] = $this->uriParams[$i];
					}
				}
			}
			elseif(isset($this->uriParams[$k])&&(!$validate||$this->resolvedParams[$k]))
				$uri .= $this->separators['And'].$k.$this->separators['Eq'].$this->uriParams[$k];
		}
		$uri = ltrim($uri,$this->separators['And']);
		return $uri;
	}
	function resolveParams($orderParams=null){
		if(isset($orderParams))
			$this->orderParams($orderParams);
		foreach($this->orderParams as $k=>$callback){
			if(is_integer($k)){
				$k = $callback;
				$callback = null;
			}
			$this->resolved = $this->resolveParam($k,$callback);
		}
		return $this->resolved = true;
	}
	function isResolved(){
		return $this->resolved;
	}
	function resolveMap($resolveParams=null){
		if(isset($resolveParams))
			$this->resolveParams($resolveParams);
		if(($uri=$this->validatedUri())!=ltrim($this->getPath(),'/')||$this->resolved===false){
			if(!control::devHas(control::dev_uri))
				header('Location: /'.$uri,true,301);
			else
				echo 'Location: /'.$uri;
			exit;
		}
	}
	function getResolved($k=null){
		return $k===null?$this->resolvedParams:(isset($this->resolvedParams[$k])?$this->resolvedParams[$k]:null);
	}
	function __toString(){
		return $this->PATH;
	}
	function __set($k,$v){
		if(substr($k,-2)=='Id'){
			$k = substr($k,0,-2);
			$this->resolvedParams[$k] = $v;
		}
		else
			$this->uriParams[$k] = $v;
	}
	function __get($k){
		if(substr($k,-2)=='Id')
			return $this->getResolved(substr($k,0,-2));
		else
			return $this->param($k);
	}
	function __isset($k){
		if(substr($k,-2)=='Id'){
			$k = substr($k,0,-2);
			return isset($this->resolvedParams[$k]);
		}
		else
			return isset($this->uriParams[$k]);
	}
	function __unset($k){
		if(substr($k,-2)=='Id'){
			$k = substr($k,0,-2);
			if(isset($this->resolvedParams[$k]))
				unset($this->resolvedParams[$k]);
		}
		else
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
		$uri = new uri();
		foreach($a as $k=>$v)
			$uri->$k = $v;
		return $uri;
	}
}