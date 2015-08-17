<?php
namespace Wild\Templix\CssSelector\Combinator;
use Wild\Templix\CssSelector\CssParserException;
class UserDefined implements CombinatorInterface{
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