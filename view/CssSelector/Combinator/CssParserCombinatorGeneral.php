<?php
namespace surikat\view\CssSelector\Combinator;
use surikat\view\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorGeneral extends CssParserCombinator{
	function filter($node, $tagname){
		$ret = [];
		while($node=$node->nextSibling)
			array_push($ret, $node);
		return $ret;
	}
}