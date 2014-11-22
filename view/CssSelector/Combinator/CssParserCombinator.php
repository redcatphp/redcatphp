<?php
namespace surikat\view\CssSelector\Combinator;
abstract class CssParserCombinator{
	abstract function filter($node, $tagname);
}