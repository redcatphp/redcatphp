<?php
namespace RedCat\Templix; 
class Abstraction extends Markup{
	protected $hiddenWrap = true;
	function __construct($nodeName,$attributes){
		$this->nodeName = $nodeName;
		$this->attributes = $attributes;
	}
}