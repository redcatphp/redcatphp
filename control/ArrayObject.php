<?php namespace surikat\control;
class ArrayObject extends \ArrayObject implements \ArrayAccess{
	function __construct($a){
		foreach($a as $k=>$v)
            if (is_array($v))
                $a[$k] = self::array2object($v);
		parent::setFlags(parent::ARRAY_AS_PROPS);
		parent::__construct((array)$a);
	}
	function offsetGet($k){
		return @parent::offsetGet($k);
	}
	function getArray(){
		return self::object2array($this);
	}
	static function object2array($a){
		foreach($a as $k=>$v)
            if(is_object($v)||is_array($v))
                $a[$k] = self::object2array($v);
        return $a->getArrayCopy();
	}
	static function array2object(array $a){
        foreach($a as $k=>$v)
            if(is_array($v))
                $a[$k] = self::array2object($v);
        return new ArrayObject($a);
    } 
}
