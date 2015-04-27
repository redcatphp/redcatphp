<?php namespace Surikat\Component\Templix\MarkupX;
use Surikat\Component\Markdown\Parsedown;
class TmlMarkdown extends \Surikat\Component\Templix\Tml {
	protected $hiddenWrap = true;
	protected $noParseContent = true;
	function load(){
		$this->remapAttr('file');
		if($this->file)
			$text = file_get_contents($this->file);
		else{
			$text = $this->getInnerTml();
			$x = explode("\n",$text);
			foreach($x as &$v)
				$v = ltrim($v);
			$text = implode("\n",$x);
		}
		$this->clearInner();
		
		$md = new Parsedown();
		$this->innerHead[] = $md->text($text);
	}
}