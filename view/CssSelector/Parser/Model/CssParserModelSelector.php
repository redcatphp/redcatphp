<?php
namespace surikat\view\CssSelector\Parser\Model;
use surikat\view\CssSelector\Parser\Model\CssParserModelFactor;
class CssParserModelSelector{
	private $_factors = [];
	public function addFactor($factor){
		array_push($this->_factors, $factor);
	}
	public function filter($node){
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
		return array_unique($ret);
	}
}