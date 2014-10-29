<?php
namespace surikat\view\CssSelector\Parser\Filter;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoUserDefined extends CssParserFilterPseudo{
	private $_input;
	private $_userDefFunction;
	public function __construct($input, $userDefFunction){
		$this->_input = $input;
		$this->_userDefFunction = $userDefFunction;
	}
	public function match($node, $position, $items){
		$userDefFunction = $this->_userDefFunction;
		return $userDefFunction($node, $this->_input, $position, $items);
	}
}