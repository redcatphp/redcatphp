<?php
namespace Surikat\Templator\CssSelector\Model;
use Surikat\Templator\CssSelector\Model\CssParserModelFactor;
use Surikat\Vars\Arrays;
class CssParserModelSelector{
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
		return Arrays::unique($ret);
		return $ret;
	}
}