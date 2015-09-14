<?php
/*
 * Bases - Data-Map - ORM-like structuring database on the fly - inspired by RedBean
 *
 * Support Opensource Relational Databases: MySQL, MariaDB, SQLite, PostgreSQL and CUBRID
 *   but planned to accommodate with any others database like NoSQL: Cassandra,MongoDB, or simple XML, JSON etc...
 *   due to following DataMapper Pattern
 *
 * @package DataMap
 * @version 1.2
 * @link http://github.com/surikat/DataMap/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
namespace Wild\DataMap;
class Bases implements \ArrayAccess{
	private $map;
	private $mapObjects= [];
	private $entityClassPrefix;
	private $entityClassDefault;
	private $primaryKeyDefault;
	private $uniqTextKeyDefault;
	private $debug;
	function __construct(array $map = [],$entityClassPrefix='EntityModel\\',$entityClassDefault='stdClass',$primaryKeyDefault='id',$uniqTextKeyDefault='uniq',$debug=false){
		$this->map = $map;
		$this->entityClassPrefix = (array)$entityClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->primaryKeyDefault = $primaryKeyDefault;
		$this->uniqTextKeyDefault = $uniqTextKeyDefault;
		$this->debug = $debug;
	}
	function debug($d=true){
		$this->debug = (bool)$d;
		foreach($this->mapObjects as $o)
			$o->debug($d);
	}
	function setEntityClassPrefix($entityClassPrefix='EntityModel\\'){
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
		if(!isset($this->mapObjects[$k])){
			$this->mapObjects[$k] = $this->loadDataSource($this->map[$k]);
			if($this->debug)
				$this->mapObjects[$k]->debug($this->debug);
		}
		return $this->mapObjects[$k];
	}
	function offsetSet($k,$v){
		$this->map[$k] = (array)$v;
		$this->mapObjects[$k] = null;
	}
	function offsetExists($k){
		return isset($this->map[$k]);
	}
	function offsetUnset($k){
		if(isset($this->map[$k]))
			unset($this->map[$k]);
		if(isset($this->mapObjects[$k]))
			unset($this->mapObjects[$k]);
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