<?php
namespace Wild\Templix\CssSelector\Filter;
use Wild\Templix\CssSelector\Filter\Pseudo;
class PseudoEven extends Pseudo{
	function match($node, $position, $items){
		return $position % 2 == 0;
	}
}