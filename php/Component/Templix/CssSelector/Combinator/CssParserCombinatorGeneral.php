<?php
namespace Surikat\Component\Templix\CssSelector\Combinator;
use Surikat\Component\Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorGeneral extends CssParserCombinator{
	function filter($node, $tagname){
		$ret = [];
		while($node=$node->nextSibling)
			array_push($ret, $node);
		return $ret;
	}
}