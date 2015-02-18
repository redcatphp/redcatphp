<?php
namespace Surikat\Templator\CssSelector\Filter;
use Surikat\Templator\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoLast extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position == (count($items) - 1);
	}
}