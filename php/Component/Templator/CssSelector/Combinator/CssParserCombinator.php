<?php
namespace Surikat\Templator\CssSelector\Combinator;
abstract class CssParserCombinator{
	abstract function filter($node, $tagname);
}