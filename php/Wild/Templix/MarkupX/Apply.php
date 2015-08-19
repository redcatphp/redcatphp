<?php namespace Wild\Templix\MarkupX;
use Wild\Templix\COMMENT;
class Apply extends \Wild\Templix\Markup {
	protected $hiddenWrap = true;
	var $_extender;
	var $_extended;
	function load(){
		//if(!$this->templix)
			//return;
		$this->remapAttr('file');
		if(!$this->_extended){
			$file = $this->__get('file');
			$this->__closed = true;
			$this->_extender = clone $this;
			$this->_extender->_extended = $this;
			$this->_extender->__unset('file');
			$apply = null;
			if($file)
				$this->_extender->parseFile($file,$this->attributes,'apply');
			else
				$apply = $this->closest();
			foreach($this->_extender->childNodes as $extender)
				if(method_exists($extender,'applyLoad')&&!($extender instanceof COMMENT))
					$extender->applyLoad($apply);
			$this->clear();
		}
		$this->preventLoad = false;
	}
	static function manualLoad($file,&$obj,$params=[]){
		$apply = new self();
		$apply->setParent($obj);
		$apply->parseFile($file,$params,'apply');
		foreach($apply->childNodes as $extender)
			if(method_exists($extender,'applyLoad'))
				$extender->applyLoad($obj);
			else{
				$selector = $extender->nodeName;
				foreach($extender->attributes as $k=>$v)
					$selector .= '['.$k.'="'.$v.'"]';
				$obj->children($selector,true)->write($extender);
			}
	}
}