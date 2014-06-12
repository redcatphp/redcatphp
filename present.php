<?php namespace surikat;
use surikat\control\ArrayObject;
use surikat\control\PHP;
use surikat\control\HTTP;
use surikat\view\TML;
class present extends ArrayObject{
	static $implementation;
	static function document($TML){}
	static function assign($o){}
	static function dynamic($o){}
	protected static function assignDefault($path,$namespaces,$attributes,&$o=array()){
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
	private static $__x;
	protected static function getX($method=null){
		if(!isset(self::$__x)){
			$p = get_called_class();
			$x = array($p);
			while(($p=get_parent_class($p))!=__CLASS__)
				array_unshift($x,$p);
			self::$__x = $x;
		}
		if($method){
			$c = substr($method,-2)=='()'&&($method=substr($method,0,-2))?'methods':'properties';
			$x = array();
			foreach(self::$__x as $_x)
				if(in_array($method,PHP::getOverriden($_x,$c)))
					$x[] = $_x;
			return $x;
		}
		return self::$__x;
	}
	static function load($o){
		$ns = $o->namespace.':'.$o->namespaceClass;
		if($o->vFile->present){
			$_ns = $o->vFile->present->namespace.':'.$o->vFile->present->namespaceClass;
			if(strpos($_ns,$ns)===0)
				return;
		}
		$o->vFile->present = $o;
		$namespaces = explode(':',$ns);
		$a = new ArrayObject();
		static::assignDefault($o->vFile->path,$namespaces,$o->attributes,$a);
		foreach(self::getX('assign()') as $c)
			$c::assign($a);
		$code = '<?php ';
		foreach($a->getArray() as $k=>$v)
			$code .= '$'.$k.'='.var_export($v,true).';';
		$code .= '$o=get_defined_vars();unset($o["this"]);';
		$code .= '\\'.get_called_class().'::execute($this->path,'.var_export($namespaces,true).','.var_export($o->attributes,true).',$o);';
		foreach(self::getX('dynamic()') as $c)
			$code .= '\\'.$c.'::dynamic($o);';
		$code .= 'extract((array)$o);?>';
		//print('<pre>'.htmlentities($code).'</pre>');exit;
		$o->head($code);
		$a = array();
		foreach(self::getX('implementation') as $c)
			$a = array_merge_recursive($a,(array)$c::$implementation);
		foreach($a as $tpl=>$params){
			if(is_integer($tpl)){
				$tpl = $params;
				$params = array();
			}
			$o->closest()->applyFile($tpl.'.tpl',$params);
		}
	}
}
