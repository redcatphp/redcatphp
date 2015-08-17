<?php
namespace Wild\Templix\CssSelector\Filter;
use Wild\Templix\CssSelector\Filter\Pseudo;
class PseudoFirstChild extends Pseudo{
	function match($node, $position, $items){
		return !$node->previousSibling;
	}
}