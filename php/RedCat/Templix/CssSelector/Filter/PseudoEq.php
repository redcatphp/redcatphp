<?php
namespace RedCat\Templix\CssSelector\Filter;
use RedCat\Templix\CssSelector\Filter\Pseudo;
class PseudoEq extends Pseudo{
	private $_position;
	function __construct($input){
		$this->_position = intval($input);
	}
	function match($node, $position, $items){
		return $this->_position == $position;
	}
}