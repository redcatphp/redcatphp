<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoNot extends CssParserFilterPseudo{
	private $_items;
	function __construct($input){
		$this->_items = $input;
	}
	function match($node, $position, $items){
		return in_array($node,$items);
	}
}