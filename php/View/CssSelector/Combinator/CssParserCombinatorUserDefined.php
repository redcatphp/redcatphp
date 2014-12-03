<?php
namespace Surikat\View\CssSelector\Combinator;
use Surikat\View\CssSelector\CssParserException;
use Surikat\View\CssSelector\Combinator\CssParserCombinator;
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