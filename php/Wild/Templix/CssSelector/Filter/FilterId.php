<?php
namespace Wild\Templix\CssSelector\Filter;
class Id implements FilterInterface{
	private $_id;
	function __construct($id){
		$this->_id = $id;
	}
	function match($node, $position, $items){
		return trim($node->getAttribute("id")) == $this->_id;
	}
}