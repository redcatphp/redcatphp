<?php namespace Surikat\Component\Config;
class ConfigMethods extends \ArrayObject implements \ArrayAccess{
	function __construct($a=[]){
		parent::setFlags(parent::ARRAY_AS_PROPS);
		parent::__construct((array)$a);
	}
}