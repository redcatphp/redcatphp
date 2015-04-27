<?php
namespace Surikat\Component\Templix\CssSelector\Combinator;
abstract class CssParserCombinator{
	abstract function filter($node, $tagname);
}