<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoEven extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position % 2 == 0;
	}
}