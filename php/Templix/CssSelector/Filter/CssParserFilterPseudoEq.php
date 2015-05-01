<?php
namespace Templix\CssSelector\Filter;
use Templix\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoEq extends CssParserFilterPseudo{
	private $_position;
	function __construct($input){
		$this->_position = intval($input);
	}
	function match($node, $position, $items){
		return $this->_position == $position;
	}
}