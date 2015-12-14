<?php
namespace RedCat\Templix\CssSelector\Filter;
use RedCat\Templix\CssSelector\Filter\Pseudo;
class PseudoEven extends Pseudo{
	function match($node, $position, $items){
		return $position % 2 == 0;
	}
}