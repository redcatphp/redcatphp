<?php
namespace Wild\Templix\CssSelector\Combinator;
abstract class CssParserCombinator{
	abstract function filter($node, $tagname);
}