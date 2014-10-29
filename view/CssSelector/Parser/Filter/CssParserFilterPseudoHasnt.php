<?php namespace surikat\view\CssSelector\Parser\Filter;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoHasnt extends CssParserFilterPseudo{
	private $_items;
	public function __construct($input){
		$this->_items = $input;
	}
	public function match($node, $position, $items){		
		$r = true;
		$_items =& $this->_items;
		$node->arecursive(function($el,&$break)use(&$r,&$_items){
			foreach($_items as $_el)
				if($el===$_el){
					$r = false;
					$break = true;
					break;
				}
		});
		return $r;
	}
}