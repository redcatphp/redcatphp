<?php
namespace RedCat\Templix\MarkupX; 
class _Include extends \RedCat\Templix\Markup{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		if(!$this->templix)
			return;
		$this->remapAttr('file');
		$file = $this->__get('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.tml';
		
		$templix = clone $this->templix;
		$templix->setParent($this->templix);
		$templix->setPath($file);
		$find = $templix->getPath();
		if(!$find)
			$this->throwException('<include "'.$file.'"> template not found ');
		$templix->onCompile(function($rootNode){
			$rootNode->setParent($this);
		});
		$templix->writeCompile();
		$r = self::findRelativePath($this->templix->getPath(),$find);
		$relativity = "__DIR__.'/".addslashes($r)."'";
		$ln = (!$this->temlix||$this->temlix->devTemplate)?"\n":'';
		$this->innerHead($ln.'<?php include '.$relativity.';?>'.$ln);
	}
	static function findRelativePath($frompath, $topath){
		$from = explode('/', $frompath);
		$to = explode('/', $topath);
		$relpath = '';
		$i = 0;
		while(isset($from[$i])&&isset($to[$i])){
			if($from[$i]!=$to[$i])
				break;
			$i++;
		}
		$j = count($from)-2;
		while($i<=$j){
			if(!empty($from[$j]))
				$relpath .= '../';
			$j--;
		}
		while(isset($to[$i])){
			if(!empty($to[$i]))
				$relpath .= $to[$i].'/';
			$i++;
		}
		return substr($relpath, 0, -1);
	}
	
	function extendLoad($extend = null){
		if($extend || ($extend = $this->closest('extend')))
			foreach($this->childNodes as $node)
				if(method_exists($node,'extendLoad'))
					$node->extendLoad($extend);
	}
	function applyLoad($apply = null,$vars = null){
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended))
			foreach($this->childNodes as $node)
				if(method_exists($node,'applyLoad'))
					$node->applyLoad($apply);
	}
}