<?php
namespace Wild\Templix\MarkupHtml5;
class Script extends \Wild\Templix\Markup{
	protected $noParseContent = true;
	function load(){
		if(!isset($this->type))
			$this->type = 'text/javascript';
	}
}