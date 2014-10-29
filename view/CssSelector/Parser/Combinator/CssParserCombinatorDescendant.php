<?php
namespace surikat\view\CssSelector\Parser\Combinator;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;
class CssParserCombinatorDescendant extends CssParserCombinator{
    public function filter($node, $tagname){
        return $node->getElementsByTagName($tagname);
    }
}