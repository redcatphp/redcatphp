<?php
namespace surikat\view\CssSelector\Filter;
class CssParserFilterPseudoFactory{
	static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "surikat\\view\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}