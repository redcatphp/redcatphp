<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoGt extends CssParserFilterPseudo{
	private $_position;
	public function __construct($input){
		$this->_position = intval($input);
	}
	public function match($node, $position, $items){
		return $position > $this->_position;
	}
}