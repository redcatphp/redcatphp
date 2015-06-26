<?php
namespace RedBase\DataSource;
use RedBase\AbstractDataSource;
use RedBase\Globality;
use RedBase\DataSource\Filesystem\Table;
class Filesystem extends AbstractDataSource{
	private $directory;
	function __construct(Globality $globality,$entityClassPrefix=null,$entityClassDefault='stdClass',$primaryKey='id',$uniqTextKey='uniq',array $config=[]){
		parent::__construct($globality,$entityClassPrefix,$entityClassDefault,$primaryKey,$uniqTextKey,$config);
		
		if(isset($config[0]))
			$this->directory = rtrim($config[0],'/');
		else
			$this->directory = isset($config['directory'])?rtrim($config['directory'],'/'):'.';
	}
	function getDirectory(){
		return $this->directory;
	}
	function createRow($type,$obj,$primaryKey='id'){
		
	}
	function readRow($type,$id,$primaryKey='id'){
		
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id'){
		
	}
	function deleteRow($type,$id,$primaryKey='id'){
		
	}
	function debug($enable=true){
		
	}
	function loadTable($k,$primaryKey,$uniqTextKey){
		return new Table($k,$primaryKey,$uniqTextKey,$this);
	}
}