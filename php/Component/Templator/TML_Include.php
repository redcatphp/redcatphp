<?php namespace Surikat\Templator; 
use Surikat\FileSystem\FS;
class TML_Include extends CALL_SUB{
	protected $selfClosed = true;
	
	protected $hiddenWrap = true;

	function load(){
		//if(!$this->Template)
			//return;

		$this->remapAttr('file');
		$file = $this->__get('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.tml';
		
		$Template = clone $this->Template;
		$Template->setParent($this->Template);
		$Template->setPath($file);
		$find = $Template->find();
		if(!$find)
			$this->throwException('&lt;include "'.$file.'"&gt; template not found ');
		$Template->writeCompile();
		
		$r = FS::findRelativePath($this->Template->find(),$find);
		$relativity = "__DIR__.'/".addslashes($r)."'";
		$this->innerHead('<?php include '.$relativity.';?>');
	}
}