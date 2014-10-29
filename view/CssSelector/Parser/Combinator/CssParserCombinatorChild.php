<?php
namespace surikat\view\CssSelector\Parser\Combinator;
use surikat\view\CssSelector\Parser\CssParserHelper;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;
class CssParserCombinatorChild extends CssParserCombinator{
    public function filter($node, $tagname){
        return $node->childNodes;
    }
}