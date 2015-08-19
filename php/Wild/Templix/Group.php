<?php
namespace Wild\Templix;
use Wild\Templix\Markup;
class Group extends Markup{
	protected $hiddenWrap = true;
	function addToGroup($node){
		parent::offsetSet(null,$node);
	}
	function offsetSet($k,$v){
		foreach($this->childNodes as $g)
			if($k===null)
				$g[] = $v;
			else
				$g[$k] = $v;
	}
	function load(){
		foreach($this->childNodes as $g)
			$g->load();
	}
	function extendLoad(){
		if($extend = $this->closest('extend')){
			foreach($this->childNodes as $extender){
				if((!$extender instanceof COMMENT)){
					if(method_exists($extender,'extendLoad'))
						$extender->extendLoad();
					else{
						$selector = $extender->nodeName;
						foreach($extender->attributes as $k=>$v)
							$selector .= '['.$k.'="'.$v.'"]';
						$extend->children($selector,true)->write($extender->getInner());
					}
				}
			}
		}
	}
}