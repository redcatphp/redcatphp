<?php
namespace surikat\view\CssSelector\Parser\Combinator;
abstract class CssParserCombinator{
    abstract public function filter($node, $tagname);
}