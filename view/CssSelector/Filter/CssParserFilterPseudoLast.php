<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoLast extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position == (count($items) - 1);
	}
}