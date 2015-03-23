<?php
namespace Surikat\Component\Templator\CssSelector\Combinator;
use Surikat\Component\Templator\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorChild extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->childNodes;
	}
}