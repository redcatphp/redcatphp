<?php
namespace Surikat\Templator\CssSelector\Filter;
class CssParserFilterPseudoFactory{
	static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "Surikat\\Templator\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}