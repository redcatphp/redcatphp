<?php namespace Templix; 
class Abstraction extends Tml{
	protected $hiddenWrap = true;
	function __construct($nodeName,$attributes){
		$this->nodeName = $nodeName;
		$this->attributes = $attributes;
	}
}