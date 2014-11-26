<?php
namespace Surikat\View\CssSelector\Filter;
use Surikat\View\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirstChild extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return !$node->previousSibling;
	}
}