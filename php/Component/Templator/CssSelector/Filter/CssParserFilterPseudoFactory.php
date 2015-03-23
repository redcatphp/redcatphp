<?php
namespace Surikat\Component\Templator\CssSelector\Filter;
class CssParserFilterPseudoFactory{
	static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "Surikat\\Component\\Templator\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}