<?php
namespace Unit;
class DiExpand{
	private $x;
	function __construct($x){
		$this->x = $x;
	}
	function __invoke(Di $di, $share = []){
		if(is_string($this->x))
			return $di->create($this->x,[],false,$share);
		else
			return call_user_func($this->x);
	}
}