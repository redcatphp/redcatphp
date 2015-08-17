<?php
namespace Wild\Templix\CssSelector\Filter;
use Wild\Templix\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoGt extends CssParserFilterPseudo{
	private $_position;
	function __construct($input){
		$this->_position = intval($input);
	}
	function match($node, $position, $items){
		return $position > $this->_position;
	}
}