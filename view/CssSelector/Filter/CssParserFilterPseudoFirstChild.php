<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirstChild extends CssParserFilterPseudo{
	public function match($node, $position, $items){
		return !$node->previousSibling;
	}
}