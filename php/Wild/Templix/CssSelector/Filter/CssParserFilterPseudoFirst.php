<?php
namespace Wild\Templix\CssSelector\Filter;
use Wild\Templix\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirst extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position == 0;
	}
}