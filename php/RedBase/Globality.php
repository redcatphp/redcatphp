<?php
namespace RedBase;
class Globality implements \ArrayAccess{
	private $map;
	private $mapObjects= [];
	private $entityClassPrefix;
	private $entityClassDefault;
	private $dataSourceDefault;
	private $primaryKeyDefault;
	function __construct(array $map = [],$entityClassPrefix=null,$entityClassDefault='stdClass',$dataSourceDefault='relational',$primaryKeyDefault='id'){
		$this->map = $map;
		$this->entityClassPrefix = (array)$entityClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->dataSourceDefault = $dataSourceDefault;
		$this->primaryKeyDefault = $primaryKeyDefault;
	}
	function offsetGet($k){
		if(!isset($this->map[$k]))
			throw new \Exception('Try to access undefined DataSource layer "'.$k.'"');
		if(!isset($this->mapObjects[$k]))
			$this->mapObjects[$k] = $this->loadDataSource($this->map[$k]);
		return $this->mapObjects[$k];
	}
	function offsetSet($k,$v){
		$this->map[$k] = (array)$v;
	}
	function offsetExists($k){
		return isset($this->map[$k]);
	}
	function offsetUnset($k){
		if(isset($this->map[$k]))
			unset($this->map[$k]);
	}
	private function loadDataSource(array $config){
		$dataSourceType = $this->dataSourceDefault;
		$entityClassPrefix = $this->entityClassPrefix;
		$entityClassDefault = $this->entityClassDefault;
		$primaryKey = $this->primaryKeyDefault;
		if(isset($config['dataSourceType'])){
			$dataSourceType = $config['dataSourceType'];
			unset($config['dataSourceType']);
		}
		if(isset($config['entityClassPrefix'])){
			$entityClassPrefix = $config['entityClassPrefix'];
			unset($config['entityClassPrefix']);
		}
		if(isset($config['entityClassDefault'])){
			$entityClassDefault = $config['entityClassDefault'];
			unset($config['entityClassDefault']);
		}
		if(isset($config['primaryKey'])){
			$primaryKey = $config['primaryKey'];
			unset($config['primaryKey']);
		}
		$class = __NAMESPACE__.'\\DataSource\\'.ucfirst($dataSourceType);
		return new $class($this,$entityClassPrefix,$entityClassDefault,$primaryKey,$config);
	}
}