<?php
namespace Wild\Templix\CssSelector\Combinator;
class Descendant implements CombinatorInterface{
	function filter($node, $tagname){
		return $node->getElementsByTagName($tagname);
	}
}