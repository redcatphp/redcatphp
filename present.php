<?php namespace surikat;
use surikat\control\PHP;
use surikat\control\HTTP;
use surikat\view\TML;
class present extends TML{
	static $options;
	static $template;
	static $implementation;
	private static $dynamicVars = array();
	static function compileDocument($TML){}
	static function compileElement(){}
	static function compileVars(&$vars=array()){}
	static function exec(){}
	static function execVars(&$vars=array()){}
	static function variable(){
		if($n=func_num_args())
			$k = func_get_arg(0);
		else
			return self::$dynamicVars;
		if($n>1)
			self::$dynamicVars[$k] = func_get_arg(1);
		elseif(is_array($k))
			foreach($k as $_k=>$v)
				self::variable($_k,$v);
		else
			return self::$dynamicVars[$k];
	}
	protected $hiddenWrap = true; //overload TML
	static function execute($opts,$tml){
		static::$options = $opts;
		static::$template = $tml;
		 if(isset(static::$options['uri'])&&static::$options['uri']=='static'&&(count(view::param())>1||!empty($_GET)))
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
	protected function load(){}
	protected function loaded(){
		$this->preventLoad = true;
		$a = array();
		foreach($this->getX('compileVars()') as $c){
			$r = $c::compileVars($a);
			if($r)
				$a = array_merge($a,(array)$r);
		}
		$code = '<?php ';
		foreach($a as $k=>$v)
			$code .= '$'.$k.'='.var_export($v,true).';';
			
		$code .= '\\'.get_class($this).'::execute('.var_export($this->attributes,true).',$this->path);';
		foreach($this->getX('exec()') as $c)
			$code .= '\\'.$c.'::exec();';
		$xev = $this->getX('execVars()');
		$cxev = count($xev);
		$code .= 'foreach('.($cxev?'array_merge(':'');
		$code .= '$__execVars=\\'.get_class($this).'::variable(),';
		foreach($xev as $c)
			$code .= '($__execVars=(array)\\'.$c.'::execVars($__execVars)),';
		$code = substr($code,0,-1);
		if($cxev)
			$code .= ')';
		$code .= '  as $k=>$v) $$k = $v;';
		$code .= '?>';

		$this->head($code);

		foreach($this->getX('compileElement()') as $c)
			$c::compileElement();
		$a = array();
		foreach($this->getX('implementation') as $c)
			$a = array_merge_recursive($a,(array)$c::$implementation);
		foreach($a as $tpl=>$params){
			if(is_integer($tpl)){
				$tpl = $params;
				$params = array();
			}
			$this->closest()->applyFile($tpl,$params);
		}
	}
}
