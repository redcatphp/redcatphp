<?php namespace Surikat\Component\Templix\MarkupX; 
class TmlInclude extends \Surikat\Component\Templix\CALL_SUB{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		//if(!$this->Template)
			//return;
		$this->remapAttr('file');
		$file = $this->__get('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.tml';
		
		$Template = clone $this->Template;
		$Template->setParent($this->Template);
		$Template->setPath($file);
		$find = $Template->find();
		if(!$find)
			$this->throwException('&lt;include "'.$file.'"&gt; template not found ');
		$Template->writeCompile();
		
		$r = self::findRelativePath($this->Template->find(),$find);
		$relativity = "__DIR__.'/".addslashes($r)."'";
		$this->innerHead('<?php include '.$relativity.';?>');
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
}