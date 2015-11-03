<?php
namespace RedCat\Templix\CssSelector\Filter;
class PseudoFactory{
	static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "RedCat\\Templix\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}