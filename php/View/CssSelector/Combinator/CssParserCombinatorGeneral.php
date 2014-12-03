<?php
namespace Surikat\View\CssSelector\Combinator;
use Surikat\View\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorGeneral extends CssParserCombinator{
	function filter($node, $tagname){
		$ret = [];
		while($node=$node->nextSibling)
			array_push($ret, $node);
		return $ret;
	}
}