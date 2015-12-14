<?php
namespace RedCat\Templix\CssSelector\Combinator;
class Child implements CombinatorInterface{
	function filter($node, $tagname){
		$nodes = [];
		foreach($node->children() as $c){
			$this->recusiveTraverseHidden($c,$nodes);
		}
		return $nodes;
	}
	function recusiveTraverseHidden($node,&$nodes){
		if($node->isHiddenWrap()){
			foreach($node->children() as $c){
				$this->recusiveTraverseHidden($c,$nodes);
			}
		}
		else{
			$nodes[] = $node;
		}
	}
}