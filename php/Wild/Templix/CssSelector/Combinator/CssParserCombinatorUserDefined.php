<?php
namespace Wild\Templix\CssSelector\Combinator;
use Wild\Templix\CssSelector\CssParserException;
use Wild\Templix\CssSelector\Combinator\CssParserCombinator;
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