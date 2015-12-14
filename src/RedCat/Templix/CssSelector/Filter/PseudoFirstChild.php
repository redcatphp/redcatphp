<?php
namespace RedCat\Templix\CssSelector\Filter;
use RedCat\Templix\CssSelector\Filter\Pseudo;
class PseudoFirstChild extends Pseudo{
	function match($node, $position, $items){
		return !$node->previousSibling;
	}
}