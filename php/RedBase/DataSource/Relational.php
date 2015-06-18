<?php
namespace RedBase\DataSource;
use RedBase\DataSource;
use RedBase\Globality;
class Relational extends DataSource{
	private $dsn;
	private $pdo;
	private $query;
	function __construct(Globality $globality,$entityClassPrefix=null,
		$entityClassDefault='stdClass',$primaryKey='id',array $config)
	{
		parent::__construct($globality,$entityClassPrefix,$entityClassDefault,$primaryKey);
		$frozen = isset($config[4])?$config[4]:(isset($config['frozen'])?$config['frozen']:null);
		$this->pdo = $this->buildPdo($config);
		$type = substr($this->dsn,0,strpos($this->dsn,':'));
		$c = 'RedBase\\SqlQuery\\'.ucfirst($type);
		$this->query = new $c($this->pdo,$primaryKey,$frozen);
	}
	function createRow($obj){
		return $this->dataSource->createRow($obj);
	}
	function readRow($id){
		return $this->dataSource->readRow($id);
	}
	function updateRow($obj,$id=null){
		return $this->dataSource->updateRow($obj,$id);
	}
	function deleteRow($id){
		return $this->dataSource->deleteRow($id);
	}
	private function buildPdo($config){
		if(isset($config[0]))
			$this->dsn = $config[0];
		else
			$this->dsn = isset($config['dsn'])?$config['dsn']:$this->buildDsnFromArray($config);
		if(isset($config[1]))
			$user = $config[1];
		else
			$user = isset($config['user'])?$config['user']:null;
		if(isset($config[2]))
			$password = $config[2];
		else
			$password = isset($config['password'])?$config['password']:null;
		if(isset($config[3]))
			$options = $config[3];
		else
			$options = isset($config['options'])?$config['options']:[];
		return new \PDO($this->dsn,$user,$password,$options);
	}
	private function buildDsnFromArray($config){
		$type = $config['type'].':';
		$host = isset($config['host'])&&$config['host']?'host='.$config['host']:'';
		$file = isset($config['file'])&&$config['file']?$config['file']:'';
		$port = isset($config['port'])&&$config['port']?';port='.$config['port']:null;
		$name = isset($config['name'])&&$config['name']?';dbname='.$config['name']:null;
		return $type.$host.$file.$port.$name;
	}
	
}