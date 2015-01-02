<?php namespace Surikat\View; 
class TML_Code extends TML{
	protected $noParseContent = true;
	function load(){
		$stripTab = false;
		if($this->stripTab){
			$this->removeAttr('stripTab');
			$stripTab = true;
		}
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
		$text = str_replace('&amp;lt;','&lt;',$text);
		$text = str_replace('&amp;gt;','&gt;',$text);
		$text = ltrim($text,"\n");
		if($stripTab)
			$text = str_replace("\t",'',$text);
		if($this->parent->nodeName!='pre'&&!$this->keepNl){
			$text = str_replace("\t","    ",$text);
			$text = str_replace(" ","&nbsp;",$text);
			$text = nl2br($text);
		}
		$this->innerHead[] = $text;
	}
}