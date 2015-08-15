<?php
namespace Templix\MarkupHtml5;
class Script extends \Templix\Markup{
	protected $noParseContent = true;
	function load(){
		if(!isset($this->type))
			$this->type = 'text/javascript';
	}
}