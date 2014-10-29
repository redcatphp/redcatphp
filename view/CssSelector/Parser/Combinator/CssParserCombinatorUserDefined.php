<?php
namespace surikat\view\CssSelector\Parser\Combinator;
use surikat\view\CssSelector\Parser\Exception\CssParserException;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;
class CssParserCombinatorUserDefined extends CssParserCombinator{
    private $_userDefFunction;
    public function __construct($userDefFunction){
        $this->_userDefFunction = $userDefFunction;
    }
    public function filter($node, $tagname){
        $userDefFunction = $this->_userDefFunction;
        $nodes = $userDefFunction($node, $tagname);
        if (!is_array($nodes))
            throw new CssParserException(
                "The user defined combinator is not an array"
            );
        return $nodes;
    }
}