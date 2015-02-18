<?php
namespace Surikat\Templator\CssSelector\Filter;
use Surikat\Templator\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoNthChild extends CssParserFilterPseudo{
	private $_position;
	function __construct($input){
		$this->_position = intval($input);
	}
	function match($node, $position, $items){
		$i = 1;
		while ($node = $node->previousSibling) {
			$i++;
			if ($i > $this->_position)
				return false;
		}
		return ($i == $this->_position);
	}
}