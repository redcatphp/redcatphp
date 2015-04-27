<?php
namespace Surikat\Component\Templix\CssSelector\Filter;
class CssParserFilterPseudoFactory{
	static function getInstance($classname, $input = "", $userDefFunction = null){
		$fullname = "Surikat\\Component\\Templix\\CssSelector\\Filter\\".$classname;
		return new $fullname($input, $userDefFunction);
	}
}