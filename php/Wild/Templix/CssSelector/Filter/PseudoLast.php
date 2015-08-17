<?php
namespace Wild\Templix\CssSelector\Filter;
use Wild\Templix\CssSelector\Filter\Pseudo;
class PseudoLast extends Pseudo{
	function match($node, $position, $items){
		return $position == (count($items) - 1);
	}
}