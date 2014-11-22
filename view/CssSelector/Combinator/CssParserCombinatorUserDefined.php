<?php
namespace surikat\view\CssSelector\Combinator;
use surikat\view\CssSelector\CssParserException;
use surikat\view\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorUserDefined extends CssParserCombinator{
	private $_userDefFunction;
	function __construct($userDefFunction){
		$this->_userDefFunction = $userDefFunction;
	}
	function filter($node, $tagname){
		$userDefFunction = $this->_userDefFunction;
		$nodes = $userDefFunction($node, $tagname);
		if (!is_array($nodes))
			throw new CssParserException(
				"The user defined combinator is not an array"
			);
		return $nodes;
	}
}