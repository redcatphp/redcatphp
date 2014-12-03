<?php
namespace Surikat\View\CssSelector\Filter;
use Surikat\View\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirst extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position == 0;
	}
}