<?php namespace surikat;
use surikat\control\ArrayObject;
use surikat\control\PHP;
use surikat\control\HTTP;
use surikat\view\TML;
class present extends TML{
	static $implementation;
	static function document($TML){}
	static function assign($o){}
	static function dynamic($o){}
	protected $hiddenWrap = true; //overload TML
	static function assignDefault($path,$namespaces,$attributes,&$o=array()){
		if(!$o instanceof ArrayObject)
			$o = new ArrayObject((array)$o);
		$o->templatePath = $path;
		$o->presentAttributes = $attributes;
		$o->presentNamespaces = $namespaces;
		$o->presentClass = get_called_class();
	}
	static function execute($path,$namespaces,$attributes,&$o=array()){
		static::assignDefault($path,$namespaces,$attributes,$o);
		 if(isset($o->presentAttributes->uri)&&$o->presentAttributes->uri=='static'&&(count(view::param())>1||!empty($_GET)))
			view::error(404);
	}
	private $__x;
	protected function getX($method=null){
		if(!isset($this->__x)){
			$p = get_class($this);
			$x = array($p);
			while(($p=get_parent_class($p))!=__CLASS__)
				array_unshift($x,$p);
			$this->__x = $x;
		}
		if($method){
			$c = substr($method,-2)=='()'&&($method=substr($method,0,-2))?'methods':'properties';
			$x = array();
			foreach($this->__x as $_x)
				if(in_array($method,PHP::getOverriden($_x,$c)))
					$x[] = $_x;
			return $x;
		}
		return $this->__x;
	}
	function loaded(){
		$ns = $this->namespace.':'.$this->namespaceClass;
		if($this->vFile->present){
			$_ns = $this->vFile->present->namespace.':'.$this->vFile->present->namespaceClass;
			if(strpos($_ns,$ns)===0)
				return;
		}
		$this->vFile->present = $this;
		$namespaces = explode(':',$ns);
		$o = new ArrayObject();
		static::assignDefault($this->vFile->path,$namespaces,$this->attributes,$o);
		foreach($this->getX('assign()') as $c)
			$c::assign($o);
		$code = '<?php ';
		foreach($o->getArray() as $k=>$v)
			$code .= '$'.$k.'='.var_export($v,true).';';
		$code .= '$o=get_defined_vars();unset($o["this"]);';
		$code .= '\\'.get_class($this).'::execute($this->path,'.var_export($namespaces,true).','.var_export($this->attributes,true).',$o);';
		foreach($this->getX('dynamic()') as $c)
			$code .= '\\'.$c.'::dynamic($o);';
		$code .= 'extract((array)$o);?>';
		//print('<pre>'.htmlentities($code).'</pre>');exit;
		$this->head($code);
		$a = array();
		foreach($this->getX('implementation') as $c)
			$a = array_merge_recursive($a,(array)$c::$implementation);
		foreach($a as $tpl=>$params){
			if(is_integer($tpl)){
				$tpl = $params;
				$params = array();
			}
			$this->closest()->applyFile($tpl.'.tpl',$params);
		}
	}
}
