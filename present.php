<?php namespace surikat;
use surikat\control\ArrayObject;
use surikat\control\PHP;
use surikat\control\HTTP;
use surikat\view\TML;
class present extends TML{
	static $final;
	static $implementation;
	static function document($TML){}
	static function assign($o){}
	static function dynamic($o){}
	protected $hiddenWrap = true; //overload TML
	static function execute($opts,$tml,&$o=array()){
		if(!$o instanceof ArrayObject)
			$o = new ArrayObject((array)$o);
		$o->template = $tml;
		$o->options = $opts;
		 if(isset($o->options->uri)&&$o->options->uri=='static'&&(count(view::param())>1||!empty($_GET)))
			view::error(404);
		static::$final = get_called_class();
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
		$o = new ArrayObject();
		foreach($this->getX('assign()') as $c)
			$c::assign($o);
		$code = '<?php ';
		foreach($o->getArray() as $k=>$v)
			$code .= '$'.$k.'='.var_export($v,true).';';

		$this->attributes['namespaces'] = explode(':',$ns);
		
		$code .= '$o=get_defined_vars();unset($o["this"]);';
		$code .= '\\'.get_class($this).'::execute('.var_export($this->attributes,true).',$this->path,$o);';
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
