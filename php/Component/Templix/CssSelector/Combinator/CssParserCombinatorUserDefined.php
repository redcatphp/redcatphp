<?php
namespace Surikat\Component\Templix\CssSelector\Combinator;
use Surikat\Component\Templix\CssSelector\CssParserException;
use Surikat\Component\Templix\CssSelector\Combinator\CssParserCombinator;
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