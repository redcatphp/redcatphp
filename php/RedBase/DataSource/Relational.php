<?php
namespace RedBase\DataSource;
use RedBase\AbstractDataSource;
use RedBase\Globality;
class Relational extends AbstractDataSource{
	private $dsn;
	private $pdo;
	private $query;
	private $type;
	function __construct(Globality $globality,$entityClassPrefix=null,
		$entityClassDefault='stdClass',$primaryKey='id',array $config)
	{
		parent::__construct($globality,$entityClassPrefix,$entityClassDefault,$primaryKey);
		$frozen = isset($config[4])?$config[4]:(isset($config['frozen'])?$config['frozen']:null);
		
		if(isset($config[0]))
			$this->dsn = $config[0];
		else
			$this->dsn = isset($config['dsn'])?$config['dsn']:$this->buildDsnFromArray($config);
		
		$this->type = strtolower(substr($this->dsn,0,strpos($this->dsn,':')));
		
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

		$c = __CLASS__.'\\'.ucfirst($this->type).'\\PDO';
		$this->pdo = new $c($this->dsn,$user,$password,$options);
		
		$c = __CLASS__.'\\'.ucfirst($this->type).'\\Query';
		$this->query = new $c($this->pdo,$primaryKey,$frozen);
	}
	function createRow($obj){
		return $this->query->createRow($obj);
	}
	function readRow($id){
		return $this->query->readRow($id);
	}
	function updateRow($obj,$id=null){
		return $this->query->updateRow($obj,$id);
	}
	function deleteRow($id){
		return $this->query->deleteRow($id);
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