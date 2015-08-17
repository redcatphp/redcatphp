<?php
namespace Wild\Templix\CssSelector\Filter;
use Wild\Templix\CssSelector\Filter\Pseudo;
class PseudoUserDefined extends Pseudo{
	private $_input;
	private $_userDefFunction;
	function __construct($input, $userDefFunction){
		$this->_input = $input;
		$this->_userDefFunction = $userDefFunction;
	}
	function match($node, $position, $items){
		$userDefFunction = $this->_userDefFunction;
		return $userDefFunction($node, $this->_input, $position, $items);
	}
}