<?php
namespace RedBase;
class RedBase implements \ArrayAccess{
	private $map;
	private $mapObjects= [];
	private $entityClassPrefix;
	private $entityClassDefault;
	private $primaryKeyDefault;
	private $uniqTextKeyDefault;
	function __construct(array $map = [],$entityClassPrefix='Model\\',$entityClassDefault='stdClass',$primaryKeyDefault='id',$uniqTextKeyDefault='uniq'){
		$this->map = $map;
		$this->entityClassPrefix = (array)$entityClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->primaryKeyDefault = $primaryKeyDefault;
		$this->uniqTextKeyDefault = $uniqTextKeyDefault;
	}
	function setEntityClassPrefix($entityClassPrefix='Model\\'){
		$this->entityClassPrefix = (array)$entityClassPrefix;
	}
	function appendEntityClassPrefix($entityClassPrefix){
		$this->entityClassPrefix[] = $entityClassPrefix;
	}
	function prependEntityClassPrefix($entityClassPrefix){
		array_unshift($this->entityClassPrefix,$entityClassPrefix);
	}
	function setEntityClassDefault($entityClassDefault='stdClass'){
		$this->entityClassDefault = $entityClassDefault;
	}
	function setPrimaryKeyDefault($primaryKeyDefault='id'){
		$this->primaryKeyDefault = $primaryKeyDefault;
	}
	function setUniqTextKeyDefault($uniqTextKeyDefault='uniq'){
		$this->uniqTextKeyDefault = $uniqTextKeyDefault;
	}
	function offsetGet($k){
		if(!isset($this->map[$k]))
			throw new Exception('Try to access undefined DataSource layer "'.$k.'"');
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
		$entityClassPrefix = $this->entityClassPrefix;
		$entityClassDefault = $this->entityClassDefault;
		$primaryKey = $this->primaryKeyDefault;
		$uniqTextKey = $this->uniqTextKeyDefault;
		
		if(isset($config['type'])){
			$type = $config['type'];
		}
		elseif((isset($config[0])&&($dsn=$config[0]))||(isset($config['dsn'])&&($dsn=$config['dsn']))){
			$type = strtolower(substr($dsn,0,strpos($dsn,':')));
			$config['type'] = $type;
		}
		else{
			throw new \InvalidArgumentException('Undefined type of DataSource, please use atleast key type, dsn or offset 0');
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
		if(isset($config['uniqTextKey'])){
			$uniqTextKey = $config['uniqTextKey'];
			unset($config['uniqTextKey']);
		}
		$class = __NAMESPACE__.'\\DataSource\\'.ucfirst($type);
		return new $class($this,$type,$entityClassPrefix,$entityClassDefault,$primaryKey,$uniqTextKey,$config);
	}
}