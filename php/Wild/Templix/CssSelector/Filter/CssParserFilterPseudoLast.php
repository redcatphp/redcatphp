<?php
namespace Wild\Templix\CssSelector\Filter;
use Wild\Templix\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoLast extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position == (count($items) - 1);
	}
}