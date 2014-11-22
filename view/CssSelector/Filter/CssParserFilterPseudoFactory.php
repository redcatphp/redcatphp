<?php
namespace surikat\view\CssSelector\Filter;
class CssParserFilterPseudoFactory{
	public static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "surikat\\view\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}