<?php
namespace Wild\Templix\CssSelector\Filter;
class PseudoFactory{
	static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "Wild\\Templix\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}