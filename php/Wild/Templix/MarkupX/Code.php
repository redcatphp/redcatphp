<?php namespace Wild\Templix\MarkupX; 
class Code extends \Wild\Templix\Markup{
	protected $noParseContent = true;
	function load(){
		$this->remapAttr('file');
		if($this->file){
			if($this->templix&&($find = $this->templix->findPath($this->file))){
				$text = file_get_contents($find);
			}
			else
				return;
			$this->selfClosed = false;
			$this->removeAttr('file');
		}
		else{
			$text = $this->getInnerMarkups();
			$this->clearInner();
		}
		$text = htmlentities($text);
		$text = str_replace('&amp;lt;','&lt;',$text);
		$text = str_replace('&amp;gt;','&gt;',$text);
		$text = ltrim($text,"\n");
		
		$x = explode("\n",$text);
		$pos = false;
		foreach($x as &$tx){
			if(($p=strlen($tx)-strlen(ltrim($tx)))!==false&&($pos===false||$p<$pos))
				$pos = $p;
		}
		if($pos){
			foreach($x as &$tx){
				$tx = substr($tx,$pos+1);
			}
		}
		$text = implode("\n",$x);
			
		if($this->parent->nodeName!='pre'&&!$this->keepNl){
			$text = str_replace("\t","    ",$text);
			$text = str_replace(" ","&nbsp;",$text);
			$text = nl2br($text);
		}
		$this->innerHead[] = $text;
	}
}