<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoOdd extends CssParserFilterPseudo{
	public function match($node, $position, $items){
		return $position % 2 > 0;
	}
}