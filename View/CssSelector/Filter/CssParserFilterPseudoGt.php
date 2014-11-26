<?php
namespace Surikat\View\CssSelector\Filter;
use Surikat\View\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoGt extends CssParserFilterPseudo{
	private $_position;
	function __construct($input){
		$this->_position = intval($input);
	}
	function match($node, $position, $items){
		return $position > $this->_position;
	}
}