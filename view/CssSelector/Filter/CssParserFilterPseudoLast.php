<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoLast extends CssParserFilterPseudo{
	function match($node, $position, $items){
		//var_dump(count(func_get_arg(2)));exit;
		return $position == (count($items) - 1);
	}
}