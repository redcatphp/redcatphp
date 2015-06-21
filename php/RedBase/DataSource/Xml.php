<?php
namespace RedBase\DataSource;
use RedBase\AbstractDataSource;
class Xml extends AbstractDataSource{
	function createRow($type,$obj,$primaryKey='id'){
		debug(func_get_args());
	}
	function readRow($type,$id,$primaryKey='id'){
		
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id'){
		
	}
	function deleteRow($type,$id,$primaryKey='id'){
		
	}
}