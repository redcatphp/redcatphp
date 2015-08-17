<?php
namespace Wild\Templix\CssSelector\Filter;
use Wild\Templix\CssSelector\Filter\Pseudo;
class PseudoFirst extends Pseudo{
	function match($node, $position, $items){
		return $position == 0;
	}
}