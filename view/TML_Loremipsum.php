<?php namespace surikat\view; 
use surikat\control\LoremIpsum;
class TML_Loremipsum extends TML {
	protected $selfClosed = true;
	function __toString(){
		if($this->mini){
			$this->html="false";
			$this->count=filter_var($this->mini,FILTER_VALIDATE_INT)?$this->mini:"1";
			$this->wordsPerParagraph="1";
		}
		if(($c=key($this->attributes))==current($this->attributes)&&is_integer(filter_var($c,FILTER_VALIDATE_INT)))
		$this->count = $c;
		$count = $this->count?$this->count:100;
		$wordsPerParagraph = $this->wordsPerParagraph?$this->wordsPerParagraph:100;
		$format = $this->format?$this->format:'html';
		$loremipsum = $this->loremipsum;
		$g = new LoremIpsum(uniqid(),$wordsPerParagraph);
		$str = $g->getContent($count,$format,$loremipsum);
		if($this->html==='false')
			$str = strip_tags($str);
		if($this->mini)
			$str = ucfirst(rtrim(trim($str),'.'));
		return $str;
	}
}
?>
