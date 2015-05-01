<?php
namespace Templix\CssSelector\Combinator;
use Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorGeneral extends CssParserCombinator{
	function filter($node, $tagname){
		$ret = [];
		while($node=$node->nextSibling)
			array_push($ret, $node);
		return $ret;
	}
}