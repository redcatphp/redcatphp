<?php
namespace RedCat\Templix\CssSelector\Filter;
use RedCat\Templix\CssSelector\Filter\Pseudo;
class PseudoLast extends Pseudo{
	function match($node, $position, $items){
		return $position == (count($items) - 1);
	}
}