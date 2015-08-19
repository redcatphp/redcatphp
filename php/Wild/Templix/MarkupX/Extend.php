<?php namespace Wild\Templix\MarkupX; 
use Wild\Templix\COMMENT;
class Extend extends \Wild\Templix\Markup{
	protected $hiddenWrap = true;
	var $_extender;
	var $_extended;
	function load(){
		$this->remapAttr('file');
		if(!isset($this->file))
			$this->file = '';
		if(!pathinfo($this->file,PATHINFO_EXTENSION))
			$this->file .= '.xtml';
		if(!$this->_extended){
			$this->_extender = clone $this;
			$this->__closed = true;
			$this->_extender->_extended = $this;
			$this->parseFile($this->file,null,'extend');
			foreach($this->_extender->childNodes as $extender){
				if($extender instanceof COMMENT)
					continue;
				if(method_exists($extender,'extendLoad'))
					$extender->extendLoad();
				else{
					$selector = $extender->nodeName;
					foreach($extender->attributes as $k=>$v)
						$selector .= '['.$k.'="'.$v.'"]';
					$this->children($selector,true)->write($extender->getInner());
				}
			}					
		}
	}
}