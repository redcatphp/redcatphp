<?php namespace surikat\control;
class ArrayObject extends \ArrayObject implements \ArrayAccess{
	function __construct($a){
		foreach($a as $k=>$v)
            if (is_array($v))
                $a[$k] = self::array2object($v);
		parent::setFlags(parent::ARRAY_AS_PROPS);
		parent::__construct((array)$a);
	}
	private function offsetDefine($k,$v){
		return parent::offsetSet($k,$v);
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
	static function object2array($a){
		foreach($a as $k=>$v)
            if($v instanceof \ArrayObject||is_array($v))
				if($a instanceof \ArrayObject)
					$a->offsetDefine($k,self::object2array($v));
				else
					$a[$k] = self::object2array($v);
        return $a instanceof \ArrayObject?$a->getArrayCopy():(array)$a;
	}
	static function array2object(array $a){
        foreach($a as $k=>$v)
            if(is_array($v))
                $a[$k] = self::array2object($v);
        return new ArrayObject($a);
    }
}
