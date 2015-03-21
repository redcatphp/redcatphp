<?php
namespace Surikat\Templator\CssSelector\Filter;
use Surikat\Templator\CssSelector\Filter\CssParserFilter;
class CssParserFilterClass extends CssParserFilter{
	private $_className;
	function __construct($className){
		$this->_className = $className;
	}
	private function _isClassInList($class, $classes){
		$items = explode(" ", trim($classes));
		if (count($items) > 0) {
			foreach ($items as $item) {
				if (strcasecmp($class, trim($item)) == 0) {
					return true;
				}
			}
		}
		return false;
	}
	function match($node, $position, $items){
		return $this->_isClassInList($this->_className,$node->getAttribute("class"));
	}
}