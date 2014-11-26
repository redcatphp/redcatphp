<?php namespace Surikat\View; 
use Surikat\View\COMMENT;
class TML_Extend extends TML {
	protected $hiddenWrap = true;
	var $_extender;
	var $_extended;
	function load(){
		//if(!$this->vFile)
			//return;
		$this->remapAttr('file');
		if(!$this->_extended){
			$this->_extender = clone $this;
			$this->__closed = true;
			$this->_extender->_extended = $this;
			if(!$this->file)
				$this->file = 'TML';
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
