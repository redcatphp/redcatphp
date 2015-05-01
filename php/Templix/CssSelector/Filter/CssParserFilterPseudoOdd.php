<?php
namespace Templix\CssSelector\Filter;
use Templix\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoOdd extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position % 2 > 0;
	}
}