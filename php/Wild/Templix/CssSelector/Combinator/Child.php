<?php
namespace Wild\Templix\CssSelector\Combinator;
class Child implements CombinatorInterface{
	function filter($node, $tagname){
		return $node->childNodes;
	}
}