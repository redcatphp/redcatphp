<?php
namespace Surikat\Templator\CssSelector\Filter;
use Surikat\Templator\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirstChild extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return !$node->previousSibling;
	}
}