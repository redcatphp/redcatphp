<?php namespace surikat;
class uri{
	protected $separatorWord = '-';
	protected $forbiddenChrParam = array(
		'?','%',',','!','^','¨','#','~',"'",'"',"\r","\n","\t"," ",
		'{','(','_','$','@',')',']','}','=','+','$','£','*','µ','§','/',
		';','.'
	);
	protected $separators = array(
		'Eq'=>':',
		'And'=>'|',
		'Or'=>'&',
	);
	protected $resolved;
	private static $__factory = array();
	static function factory($k=0,$path=null){
		if(!isset(self::$__factory[$k]))
			self::$__factory[$k] = new URI($path);
		return self::$__factory[$k];
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
	protected $uriParams = array();
	protected $resolvedParams = array();
	protected $orderParams = array();
	function __construct($path=''){
		$this->PATH = (string)$path;
		$this->uriParams = $this->parseUriParams(ltrim($this->PATH,'/'));
		foreach(array_keys($this->uriParams) as $k)
			$this->resolvedParams[$k] = null;
	}
	function getPath(){
		return $this->PATH;
	}
	function parseUriParams($path){
		$uriParams = array();
		$min = array();
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
				$ints = array();
				foreach(array_keys($this->uriParams) as $i){
					if($i
						&&is_integer($i)
						&&!in_array($this->uriParams[$i],$ints)
						&&(!$validate||$this->resolvedParams[$i])
					){
						$uri .= $this->separators['And'].$this->uriParams[$i];
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
		return $k===null?$this->uriParams:(isset($this->uriParams[$k])?$this->uriParams[$k]:null);
	}
	function __toString(){
		return $this->PATH;
	}
}