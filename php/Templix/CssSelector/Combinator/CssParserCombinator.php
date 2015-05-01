<?php
namespace Templix\CssSelector\Combinator;
abstract class CssParserCombinator{
	abstract function filter($node, $tagname);
}