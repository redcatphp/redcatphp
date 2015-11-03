<?php
namespace RedCat\Templix\CssSelector\Combinator;
class Adjacent implements CombinatorInterface{
	function filter($node, $tagname){
		$ret = [];
		if ($element = $node->nextSibling)
			array_push($ret, $element);
		return $ret;
	}
}