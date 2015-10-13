<?php
namespace Wild\Templix\MarkupHtml5; 
class Style extends \Wild\Templix\Markup{
	protected $noParseContent = true;
	function load(){
		if(!isset($this->type))
			$this->type = 'text/css';
		
		if($this->templix&&$this->templix->isXhtml){
			$str = trim($this->getInner());
			if(!empty($str)){
				if(substr($str,0,13)!='/*<![CDATA[*/'&&substr($str,-7)!='/*]]>*/')
					$this->innerHead("/*<![CDATA[*/\n");
					$this->innerFoot("\n/*]]>*/");
			}
		}
	}
}