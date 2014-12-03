<?php
namespace Surikat\View\CssSelector\Filter;
use Surikat\View\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoEven extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position % 2 == 0;
	}
}