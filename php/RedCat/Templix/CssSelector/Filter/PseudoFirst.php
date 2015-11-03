<?php
namespace RedCat\Templix\CssSelector\Filter;
use RedCat\Templix\CssSelector\Filter\Pseudo;
class PseudoFirst extends Pseudo{
	function match($node, $position, $items){
		return $position == 0;
	}
}