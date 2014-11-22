<?php
namespace surikat\view\CssSelector\Filter;
use surikat\view\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoNthChild extends CssParserFilterPseudo{
	private $_position;
	public function __construct($input){
		$this->_position = intval($input);
	}
	public function match($node, $position, $items){
		$i = 1;
		while ($node = $node->previousSibling) {
			$i++;
			if ($i > $this->_position)
				return false;
		}
		return ($i == $this->_position);
	}
}