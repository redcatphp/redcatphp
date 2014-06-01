<?php namespace surikat\view\CssSelector\Parser\Filter;
use surikat\view\CssSelector\Parser\CssParserHelper;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoHas extends CssParserFilterPseudo{
	private $_items;
	public function __construct($input){
		$this->_items = $input;
	}
	public function match($node, $position, $items){
		$r = false;
		$_items =& $this->_items;
		$node->recursive(function($el,&$break)use(&$r,&$_items){
			foreach($_items as $_el)
				if($el===$_el){
					$r = true;
					$break = true;
					break;
				}
				
		});
		return $r;
	}
}
//by surikat
