<?php
namespace Wild\DataMap\DataTable;
use Wild\DataMap\DataTable;
class Filesystem extends DataTable{
	private $directoryIterator;
	private $patterns = [];
	private $antiPatterns = [];
	private $rewind;
	function __construct($name,$primaryKey='id',$uniqTextKey='uniq',$dataSource){
		parent::__construct($name,$primaryKey,$uniqTextKey,$dataSource);
		$this->directoryIterator = new \DirectoryIterator($this->dataSource->getDirectory().'/'.$this->name);
	}
	function rewind(){
		$this->directoryIterator->rewind();
		$this->rewind = true;
		$this->next();
		$this->rewind = false;
	}
	function current(){
		$iterator = $this->directoryIterator->current();
		if($iterator){
			$obj = $this->dataSource->entityFactory($this->name);
			$obj->{$this->primaryKey} = $iterator->getFilename();
			$obj->iterator = $iterator;
			if($this->useCache)
				$this->data[$obj->{$this->primaryKey}] = $obj;
			return $obj;
		}
	}
	function key(){
		return $this->directoryIterator->current()->getFilename();
	}
	function valid(){
		return $this->directoryIterator->valid();
	}
	function next(){
		$iterator = $this->directoryIterator->current();
		if(!$this->rewind)
			$iterator->next();
		while(
			$this->valid()&&
			(
				$iterator->isDot()
				||$this->patternMatch()
				||$this->antiPatternMatch()
			)
		)
			$iterator->next();
	}
	function patternMatch(){
		foreach($this->patterns as $p)
			if($p&&!preg_match($p,$this->key()))
				return true;
	}
	function AntiPatternMatch(){
		foreach($this->antiPatterns as $p)
			if($p&&preg_match($p,$this->key()))
				return true;
	}
	function addPattern($pattern){
		$this->patterns[] = $pattern;
	}
	function addAntiPattern($pattern){
		$this->antiPatterns[] = $pattern;
	}
	function setPattern($pattern){
		$this->patterns = $pattern;
	}
	function setAntiPattern($pattern){
		$this->antiPatterns = $pattern;
	}
	function getPrefixedBy($prefix){
		$a = [];
		foreach($this as $file=>$obj){
			if(strpos($file,$prefix)===0)
				$a[$file] = $obj;
		}
		return $a;
	}
	function __clone(){
		$this->directoryIterator = clone $this->directoryIterator;
	}
}