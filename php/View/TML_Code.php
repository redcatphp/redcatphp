<?php namespace Surikat\View; 
class TML_Code extends TML{
	protected $noParseContent = true;
	function load(){
		$this->remapAttr('file');
		if($this->file){
			if($this->TeMpLate&&($find = $this->TeMpLate->find($this->file))){
				$text = file_get_contents($find);
			}
			else
				return;
			$this->selfClosed = false;
			$this->removeAttr('file');
		}
		else{
			$text = $this->getInnerTml();
			$this->clearInner();
		}
		$text = htmlentities($text);
		if($this->parent->nodeName!='pre'&&!$this->keepNl)
			$text = nl2br($text);
		$this->innerHead[] = $text;
	}
}