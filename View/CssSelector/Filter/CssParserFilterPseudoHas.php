<?php namespace Surikat\View\CssSelector\Filter;
use Surikat\View\CssSelector\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoHas extends CssParserFilterPseudo{
	private $_items;
	function __construct($input){
		$this->_items = $input;
	}
	function match($node, $position, $items){
		$r = false;
		foreach($node->find('*') as $el){
			foreach($this->_items as $_el)
				if($el===$_el){
					$r = true;
					break;
				}
		}
		return $r;
	}
}