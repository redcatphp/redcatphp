<?php
namespace Surikat\View\CssSelector\Combinator;
use Surikat\View\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorChild extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->childNodes;
	}
}