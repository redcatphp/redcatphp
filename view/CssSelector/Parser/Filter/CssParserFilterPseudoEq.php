<?php
namespace surikat\view\CssSelector\Parser\Filter;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoEq extends CssParserFilterPseudo{
	private $_position;
	public function __construct($input){
		$this->_position = intval($input);
	}
	public function match($node, $position, $items){
		return $this->_position == $position;
	}
}