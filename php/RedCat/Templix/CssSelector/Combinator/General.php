<?php
namespace RedCat\Templix\CssSelector\Combinator;
class General implements CombinatorInterface{
	function filter($node, $tagname){
		$ret = [];
		while($node=$node->nextSibling)
			array_push($ret, $node);
		return $ret;
	}
}