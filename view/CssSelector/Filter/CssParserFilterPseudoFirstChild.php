<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirstChild extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return !$node->previousSibling;
	}
}