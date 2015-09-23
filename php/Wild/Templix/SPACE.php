<?php
namespace Wild\Templix;
class SPACE extends Markup{
	var $nodeName = 'SPACE';
	protected $hiddenWrap = true;
	function parse($text){
		$this->innerHead = [' '];
	}
}