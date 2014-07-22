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
	private static $__factory = array();
	static function factory($k=0,$path=null){
		if(!isset(self::$__factory[$k]))
			self::$__factory[$k] = new URI($path);
		return self::$__factory[$k];
	}
	static function param($k=null){
		return self::factory()->getParam($k);
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
	protected $orderParams = array();
	function __construct($path=''){
		$this->PATH = (string)$path;
		$this->uriParams = $this->parseUriParams(ltrim($this->PATH,'/'));
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

	function orderParams($orderParams){
		$this->orderParams = (array)$orderParams;
		//foreach($this->orderParams as $k){
			//switch($k){
				//case ':int':
					//foreach($uriA as $i=>$a)
						//if(is_integer($i)){
							//$this->taxonomies[] = $uriA[$i];
							//unset($uriA[$i]);
						//}
						//else
							//break;
					//
				//break;
				//default:
					//if(isset($uriA[$k]))
						//unset($uriA[$k]);
				//break;
			//}
		//}
	}
	function __toString(){
		return $this->PATH;
	}
}