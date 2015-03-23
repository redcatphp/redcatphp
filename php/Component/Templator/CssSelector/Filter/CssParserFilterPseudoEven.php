<?php
namespace Surikat\Component\Templator\CssSelector\Filter;
use Surikat\Component\Templator\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoEven extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position % 2 == 0;
	}
}