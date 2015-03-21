<?php
namespace Surikat\Templator\CssSelector\Combinator;
use Surikat\Templator\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorChild extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->childNodes;
	}
}