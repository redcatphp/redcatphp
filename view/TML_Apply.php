<?php namespace surikat\view;
use surikat\view\COMMENT;
class TML_Apply extends TML {
	protected $hiddenWrap = true;
	var $_extender;
	var $_extended;
	function load(){
		$this->remapAttr('file');
		if(!$this->_extended){
			$file = $this->__get('file');
			$this->_extender = clone $this;
			$this->_extender->_extended = $this;
			$this->_extender->__unset('file');
			$apply = null;
			if($file)
				$this->_extender->parseFile($file,$this->attributes,__CLASS__);
			else
				$apply = $this->closest();
			foreach($this->_extender->childNodes as $extender)
				if(method_exists($extender,'applyLoad')&&!($extender instanceof COMMENT))
					$extender->applyLoad($apply);
		}
		$this->preventLoad = false;
	}
	static function manualLoad($file,&$obj,$params=[]){
		$apply = new self($obj);
		$apply->parseFile($file,$params,__CLASS__);
		foreach($apply->childNodes as $extender)
			if(method_exists($extender,'applyLoad'))
				$extender->applyLoad($obj);
			else{
				$selector = $extender->nodeName;
				foreach($extender->attributes as $k=>$v)
					$selector .= '['.$k.'="'.$v.'"]';
				$obj->find($selector,true)->write($extender);
			}
	}
}
