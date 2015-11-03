<?php
namespace RedCat\Templix;
class BeautifyHtml{
	protected $tagsToIgnore = ['script','textarea','pre','style','code',];
	protected $tagsToIgnoreBlocks = [];
	protected $currentTagToIgnore;
	protected $trimTagsToIgnore = true;
	protected $spaceCharacter = "\t";
	protected $removeComments = false;
	protected $openTagsPattern = [];
	protected $closeTagsPattern = [];
	protected $indentOpenTagsPattern = '';
	protected $indentCloseTagsPattern = '';
	protected static $tagsIndentForce = [
		'html','head','body',
	];
	protected static $tagsIndent = [
		'header','main','article','section','nav','footer',
		'div',
		'table','thead','tbody','tr','th','td',
		'ol','ul','li','dl','dt','dd',
		'figure',
		'b','i','em','strong','span','a','abbr','p','option',
		'h[0-9]',
	];
	protected static $tagsAdd = ['meta','link','select',];
	function __construct(){
		foreach(self::$tagsIndentForce as $tag){
			$this->openTagsPattern[] = "/(<$tag\b[^>]*>)/i";
			$this->closeTagsPattern[] = "/(<\/$tag>)/i";
		}
		foreach(array_merge(self::$tagsIndent,self::$tagsAdd) as $tag){
			$this->openTagsPattern[] = "/(\s<$tag\b[^>]*>)/i";
			$this->closeTagsPattern[] = "/(\s<\/$tag>)/i";
			$this->openTagsPatternEnd[] = "/(<$tag\b[^>]*>\s)/i";
			$this->closeTagsPatternEnd[] = "/(<\/$tag>\s)/i";
		}
		$this->indentOpenTagsPattern = "/<(".implode('|',array_merge(self::$tagsIndent,self::$tagsIndentForce)).")\b[ ]*[^>]*[>]/i";
		$this->indentCloseTagsPattern = "/<\/(".implode('|',array_merge(self::$tagsIndent,self::$tagsIndentForce)).")>/i";
		
	}
	function addTagToIgnore($tagToIgnore){
		if(!preg_match('/^[a-zA-Z]+$/', $tagToIgnore))
			throw new \RuntimeException('Only characters from a to z are allowed as tag');
		if(!in_array($tagToIgnore,$this->tagsToIgnore))
			$this->tagsToIgnore[] = $tagToIgnore;
	}
	function setTrimTagsToIgnore($bool){
		$this->trimTagsToIgnore = $bool;
	}
	function setRemoveComments($bool){
		$this->removeComments = $bool;
	}
	private function tagsToIgnoreCallback($e){
		$key = '<'.$this->currentTagToIgnore.'>'.uniqid('tag-to-ignore',true).'</'.$this->currentTagToIgnore.'>'; // build key for reference
		if($this->trimTagsToIgnore){ // trim each line
			$lines = explode("\n",$e[0]);
			array_walk($lines, function(&$n){
				$n = trim($n);
			});
			$e[0] = implode("\n",$lines);
		}
		$this->tagsToIgnoreBlocks[$key] = $e[0]; // add block to storage
		return $key;
	}
	function beautify($buffer){
		// remove blocks, which should not be processed and add them later again using keys for reference 
		foreach($this->tagsToIgnore as $tag){
			$this->currentTagToIgnore = $tag;
			$buffer = preg_replace_callback('/<'.$this->currentTagToIgnore.'\b[^>]*>([\s\S]*?)<\/'.$this->currentTagToIgnore.'>/mi',[$this,'tagsToIgnoreCallback'],$buffer);
		}

		// temporarily remove comments to keep original linebreaks
		$this->currentTagToIgnore = 'htmlcomment';
		$buffer = preg_replace_callback( "/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/ms", [$this,'tagsToIgnoreCallback'], $buffer);
		
		$buffer = preg_replace(["/\s\s+|\n/","/ +/","/\t+/" ], [" "," "," " ], $buffer); // cleanup source: all in one line, remove double spaces, remove tabulators
		
		if($this->removeComments)
			$buffer = preg_replace("/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/ms",'',$buffer);

		// add newlines for several tags
		$buffer = preg_replace($this->openTagsPattern, "\n$1", $buffer); // opening tags
		$buffer = preg_replace($this->closeTagsPattern, "\n$1", $buffer); // closing tags
		$buffer = preg_replace($this->openTagsPatternEnd, "$1\n", $buffer); // opening tags
		$buffer = preg_replace($this->closeTagsPatternEnd, "$1\n", $buffer); // closing tags
		
		
		// get the html each line and do indention
		$lines = explode("\n",$buffer);
		$indentionLevel = -1;
		$cleanContent = []; // storage for indented lines
		foreach($lines as $line){
			if(!$line) continue;
			$o = preg_match($this->indentOpenTagsPattern,$line);
			$c = preg_match($this->indentCloseTagsPattern, $line);
			
			if($c&&!$o)
				$indentionLevel--;
				
			$line = trim($line);
			if($indentionLevel>0)
				$line = str_repeat($this->spaceCharacter,$indentionLevel).$line;
			$cleanContent[] = $line;
			
			if($o&&!$c)
				$indentionLevel++;
		}
		$buffer = implode("\n",$cleanContent); // write indented lines back to buffer
		$buffer = str_replace(array_keys($this->tagsToIgnoreBlocks), $this->tagsToIgnoreBlocks, $buffer); // add blocks, which should not be processed
		return $buffer;
	}
}