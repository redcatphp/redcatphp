<?php
namespace Surikat\Templator\CssSelector\Model;
use Surikat\Templator\CssSelector\Filter\CssParserFilter;
class CssParserModelElement{
	private $_tagName;
	private $_filters;
	function __construct($tagName){
		$this->_filters = [];
		$this->_tagName = $tagName;
	}
	function getTagName(){
		return $this->_tagName;
	}
	function getFilters(){
		return $this->_filters;
	}
	function addFilter($filter){
		array_push($this->_filters, $filter);
	}
	function match($node){
		return $this->_tagName == "*" || $node->nodeName == $this->_tagName;
	}
}