<?php
namespace Surikat\Component\Templix\CssSelector\Filter;
use Surikat\Component\Templix\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoLt extends CssParserFilterPseudo{
	private $_position;
	function __construct($input){
		$this->_position = intval($input);
	}
	function match($node, $position, $items){
		return $position < $this->_position;
	}
}