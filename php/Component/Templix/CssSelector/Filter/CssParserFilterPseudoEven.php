<?php
namespace Surikat\Component\Templix\CssSelector\Filter;
use Surikat\Component\Templix\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoEven extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position % 2 == 0;
	}
}