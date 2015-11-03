<?php
namespace RedCat\Templix\CssSelector\Filter;
use RedCat\Templix\CssSelector\Filter\Pseudo;
class PseudoNthChild extends Pseudo{
	private $_position;
	function __construct($input){
		$this->_position = intval($input);
	}
	function match($node, $position, $items){
		$i = 1;
		while ($node = $node->previousSibling) {
			$i++;
			if ($i > $this->_position)
				return false;
		}
		return ($i == $this->_position);
	}
}