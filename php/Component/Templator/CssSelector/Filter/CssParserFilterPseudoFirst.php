<?php
namespace Surikat\Component\Templator\CssSelector\Filter;
use Surikat\Component\Templator\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirst extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position == 0;
	}
}