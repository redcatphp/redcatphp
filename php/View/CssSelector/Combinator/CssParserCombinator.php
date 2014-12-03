<?php
namespace Surikat\View\CssSelector\Combinator;
abstract class CssParserCombinator{
	abstract function filter($node, $tagname);
}