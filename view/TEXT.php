<?php namespace surikat\view;
use surikat\view\TML;
use surikat\control\str;
class TEXT extends CORE{
	var $nodeName = '#text';
	protected $hiddenWrap = true;
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		if($this->parent&&$this->parent->vFile&&$this->parent->vFile->isXhtml)
			$text = str::cleanXhtml($text);
		$text = $this->phpImplode($text,$constructor);
		$this->innerHead($text);
	}
	function biohazard(){
		if(!$this->parent||!$this->parent->antibiotique)
			$this->contentText = new TML('<loremipsum mini>');
	}
}
