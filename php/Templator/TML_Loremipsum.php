<?php namespace Surikat\Templator; 
class TML_Loremipsum extends TML {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	protected $footIndentationForce = true;
	function load(){
		if($this->mini){
			$this->html="false";
			$this->count = filter_var($this->mini,FILTER_VALIDATE_INT)?$this->mini:"1";
			$this->wordsPerParagraph="1";
		}
		if(($c=key($this->attributes))==current($this->attributes)&&is_integer(filter_var($c,FILTER_VALIDATE_INT)))
		$this->count = $c;
		$count = $this->count?$this->count:100;
		$wordsPerParagraph = $this->wordsPerParagraph?$this->wordsPerParagraph:100;
		$format = "'".($this->format?$this->format:'html')."'";
		$loremipsum = $this->loremipsum?'true':'false';
		$html = $this->html==='false'?'false':'true';
		$mini = $this->mini?'true':'false';
		$this->innerHead[] = "<?php echo Surikat\Dev\LoremIpsum::get($count,$wordsPerParagraph,$format,$loremipsum,$html,$mini);?>";
	}
}
