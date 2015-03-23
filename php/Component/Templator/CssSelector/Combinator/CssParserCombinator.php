<?php
namespace Surikat\Component\Templator\CssSelector\Combinator;
abstract class CssParserCombinator{
	abstract function filter($node, $tagname);
}