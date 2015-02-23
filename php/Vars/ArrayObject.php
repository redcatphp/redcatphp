<?php namespace Surikat\Vars;
use Surikat\DependencyInjection\MutatorCall;
class ArrayObject extends \ArrayObject implements \ArrayAccess{
	use MutatorCall;
	function __construct($a=[]){
		foreach($a as $k=>$v)
            if (is_array($v))
                $a[$k] = self::array2object($v);
		parent::setFlags(parent::ARRAY_AS_PROPS);
		parent::__construct((array)$a);
	}
	private function offsetDefine($k,$v){
		return parent::offsetSet($k,$v);
	}
	function offsetExists($k){
		$e = parent::offsetExists($k);
		if($e&&($a=parent::offsetGet($k)) instanceof \ArrayObject)
			return count($a);
		return $e;
	}
	function offsetSet($k,$v){
		if(is_array($v))
			$v = self::array2object($v);
		return parent::offsetSet($k,$v);
	}
	function offsetGet($k){
		return parent::offsetExists($k)?parent::offsetGet($k):null;
	}
	function getArray(){
		return self::object2array($this);
	}
	function merge(){
		foreach(func_get_args() as $a)
			foreach($a as $k=>$v)
				$this->offsetSet($k,$v);
	}
	static function merge_recursive(){
		$args = func_get_args();
		array_unshift($args,$this);
		call_user_func_array(['self','array_merge_recursive'],$args);
	}
	static function array_merge_recursive(){
		$args = func_get_args();
		$merged = array_shift($args);
		foreach($args as $array2){
			if(!is_array($array2))
				continue;
			foreach($array2 as $key => &$value){
				if(($value instanceof ArrayObject||is_array($value))&&isset($merged [$key])&&($merged[$key] instanceof ArrayObject||is_array($merged[$key])))
					$merged[$key] = self::array_merge_recursive($merged[$key],$value);
				else
					$merged[$key] = $value;
			}
		}
		return $merged;
	}
	function extend(){
		$c = clone $this;
		foreach(func_get_args() as $a)
			$c->merge($a);
		return $c;
	}
	static function __recurseKey($v,$key,$depth=null){
		$c = new static();
		foreach($v as $k=>$v){
			if($key===$k){
				$c[] = $v;
			}
			elseif($depth&&$v instanceof ArrayObject){
				foreach(self::__recurseKey($v,$key,$depth===true?true:$depth-1) as $_v)
					$c[] = $_v;
			}
		}
		return $c;
	}
	static function __groupKey($o,$group,$key,$depth=null){
		$c = new static();
		foreach($group as $g){
			$v = $o->offsetGet($g);
			if(!$v instanceof ArrayObject)
				continue;
			foreach(self::__recurseKey($v,$key,$depth) as $v)
				$c[] = $v;
		}
		return $c;
	}
	function groupKey($group,$key,$depth=null){
		return self::__groupKey($this,$group,$key,$depth);
	}
	function group(){
		$c = new static();
		foreach(func_get_args() as $k)
			if($this->$k instanceof ArrayObject||is_array($this->$k))
				$c->push($this->$k);
		return $c;
	}
	function submerge(){
		$c = new static();
		foreach(func_get_args() as $k)
			if($this->$k instanceof ArrayObject||is_array($this->$k))
				$c->merge($this->$k);
		return $c;
	}
	function push(){
		foreach(func_get_args() as $v)
			$this[] = $v;
	}
	function keys(){
		return array_keys((array)$this);
	}
	function values(){
		return array_values((array)$this);
	}
	function unshift(){
		$a = (array)$this;
		foreach(func_get_args() as $v)
			array_unshift($a, $v);
        $this->exchangeArray($a);
	}
	function shift(){
		$a = (array)$this;
		$r = array_shift($a);
        $this->exchangeArray($a);
        return $r;
	}
	function pop(){
		$a = (array)$this;
		$r = array_pop($a);
        $this->exchangeArray($a);
        return $r;
	}
	function asort($flag=null){
		$a = (array)$this;
		asort($a,$flag);
        $this->exchangeArray($a);
        return $this;
	}
	function sort($flag=null){
		$a = (array)$this;
		sort($a,$flag);
        $this->exchangeArray($a);
        return $this;
	}
	function in($v){
        return in_array($v,(array)$this);
	}
	function search($v){
        return array_search($v,(array)$this);
	}
	function unique(){
		return new static(array_unique((array)$this));
	}
	function filter($cb){
		return new static(array_filter((array)$this,$cb));
	}
	function __toString(){
		if($this->Dev_Level()->CONTROL)
			return $this->__debug();
		else
			return '';
	}
	function __debug(){
		return '<pre>'.htmlentities(print_r($this->getArray(),true)).'<pre>';
	}
	function debug(){
		echo $this->__debug();
	}
	static function object2array($a){
		foreach($a as $k=>$v)
            if($v instanceof \ArrayObject||is_array($v))
				if($a instanceof \ArrayObject)
					$a->offsetDefine($k,self::object2array($v));
				else
					$a[$k] = self::object2array($v);
        return $a instanceof \ArrayObject?$a->getArrayCopy():(array)$a;
	}
	static function array2object( array $a){
        foreach($a as $k=>$v)
            if(is_array($v))
                $a[$k] = self::array2object($v);
        return new static($a);
    }
    function ___call($f,$args){
		if(strpos($f,'array_')===0)
			array_unshift($args,$this->getArray());
		else
			array_push($args,$this->getArray());
		return call_user_func_array($f,$args);
	}
	function getClone(){
		foreach($this as $k=>$v){
			if($v instanceof ArrayObject)
				$this[$k] = $v->getClone();
		}
	}
}