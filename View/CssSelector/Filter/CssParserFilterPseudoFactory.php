<?php
namespace Surikat\View\CssSelector\Filter;
class CssParserFilterPseudoFactory{
	static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "Surikat\\View\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}