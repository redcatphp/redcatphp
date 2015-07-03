<?php namespace KungFu\TemplixPlugin\Markup;
use KungFu\TemplixPlugin\LoremIpsum as LoremIpsumGen;
class Loremipsum extends \Templix\Markup {
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
		$format = $this->format;
		$loremipsum = $this->loremipsum;
		$html = $this->html==='false';
		$mini = $this->mini;
		if($this->dynamic){
			$format = "'".($format?$format:'html')."'";
			$loremipsum = $loremipsum?'true':'false';
			$html = $html?'false':'true';
			$mini = $mini?'true':'false';
			$this->innerHead[] = "<?php echo \KungFu\TemplixPlugin\LoremIpsum::get($count,$wordsPerParagraph,$format,$loremipsum,$html,$mini);?>";
		}
		else{
			$this->innerHead[] = LoremIpsumGen::get($count,$wordsPerParagraph,$format,$loremipsum,$html,$mini);
		}
	}
}