<?php
namespace Wild\DataMap\DataSource;
use Wild\DataMap\DataSource;
use Wild\DataMap\Wild\DataMap;
class Filesystem extends DataSource{
	private $directory;
	function construct(array $config=[]){
		if(isset($config[0]))
			$this->directory = rtrim($config[0],'/');
		else
			$this->directory = isset($config['directory'])?rtrim($config['directory'],'/'):'.';
	}
	function getDirectory(){
		return $this->directory;
	}
	function readId($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		return file_exists($this->directory.'/'.$type.'/'.$id)?$id:false;
	}
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		
	}
	function putRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		
	}
	function deleteRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		
	}
	function debug($enable=true){
		
	}
}