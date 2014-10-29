<?php
namespace surikat\view\CssSelector\Parser\Combinator;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;
class CssParserCombinatorGeneral extends CssParserCombinator{
	public function filter($node, $tagname){
		$ret = [];
		while($node=$node->nextSibling)
			array_push($ret, $node);
		return $ret;
	}
}