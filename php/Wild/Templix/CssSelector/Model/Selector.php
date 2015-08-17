<?php
namespace Wild\Templix\CssSelector\Model;
class Selector{
	private $_factors = [];
	function addFactor($factor){
		array_push($this->_factors, $factor);
	}
	function filter($node){
		$ret = [];
		$items = [$node];
		foreach ($this->_factors as $factor) {
			$ret = $this->_getNodesByFactor($items, $factor);
			$items = $ret;
		}
		return $ret;
	}
	private function _getNodesByFactor($nodes, $factor){
		$ret = [];
		foreach ($nodes as $node)
			$ret = array_merge($ret, $factor->filter($node));
		return array_filter($ret, function($obj){
			static $idList = array();
			if(in_array($obj,$idList,true)){
				return false;
			}
			$idList[] = $obj;
			return true;
		});
	}
}