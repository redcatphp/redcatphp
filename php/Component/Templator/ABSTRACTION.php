<?php namespace Surikat\Component\Templator; 
class ABSTRACTION extends CORE{
	protected $hiddenWrap = true;
	function __construct($nodeName,$attributes){
		$this->nodeName = $nodeName;
		$this->attributes = $attributes;
	}
}