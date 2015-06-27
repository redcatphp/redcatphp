<?php
namespace RedBase\DataSource\Filesystem;
use RedBase\AbstractTable;
use RedBase\DataSourceInterface;
class Table extends AbstractTable{
	private $directoryIterator;
	private $pattern;
	private $antiPattern;
	function __construct($name,$primaryKey='id',$uniqTextKey='uniq',DataSourceInterface $dataSource){
		parent::__construct($name,$primaryKey,$uniqTextKey,$dataSource);
		$this->directoryIterator = new \DirectoryIterator($this->dataSource->getDirectory().'/'.$this->name);
	}
	function rewind(){
		$this->directoryIterator->rewind();
	}
	function current(){
		$iterator = $this->directoryIterator->current();
		while(
			$this->valid()&&
			(
				$iterator->isDot()
				||($this->pattern&&!preg_match($this->pattern,$this->key()))
				||($this->antiPattern&&preg_match($this->antiPattern,$this->key()))
			)
		)
			$iterator->next();
		if($iterator){
			$c = $this->dataSource->findEntityClass($this->name);
			$obj = new $c();
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
		$this->directoryIterator->next();
	}
	function setPattern($pattern){
		$this->pattern = $pattern;
	}
	function setAntiPattern($pattern){
		$this->antiPattern = $pattern;
	}
}