<?php
namespace Surikat\Component\Templix\CssSelector\Filter;
use Surikat\Component\Templix\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirst extends CssParserFilterPseudo{
	function match($node, $position, $items){
		return $position == 0;
	}
}