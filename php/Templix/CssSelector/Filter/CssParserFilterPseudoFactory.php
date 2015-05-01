<?php
namespace Templix\CssSelector\Filter;
class CssParserFilterPseudoFactory{
	static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "Templix\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}