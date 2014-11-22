<?php
namespace surikat\view\CssSelector\Combinator;
abstract class CssParserCombinator{
	abstract public function filter($node, $tagname);
}