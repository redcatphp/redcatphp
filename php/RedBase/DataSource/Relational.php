<?php
namespace RedBase\DataSource;
use RedBase\AbstractDataSource;
use RedBase\Globality;
use RedBase\DataSource\Relational\Table;
class Relational extends AbstractDataSource{
	private $dsn;
	private $pdo;
	private $query;
	private $type;
	function __construct(Globality $globality,$entityClassPrefix='Model\\',$entityClassDefault='stdClass',$primaryKey='id',$uniqTextKey='uniq',array $config=[])
	{
		parent::__construct($globality,$entityClassPrefix,$entityClassDefault,$primaryKey,$uniqTextKey,$config);
		
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
		
		$frozen = isset($config[4])?$config[4]:(isset($config['frozen'])?$config['frozen']:null);
		$createDb = isset($config[5])?$config[5]:(isset($config['createDb'])?$config['createDb']:null);

		$tablePrefix = isset($config['tablePrefix'])?$config['tablePrefix']:null;
		
		$c = __CLASS__.'\\'.ucfirst($this->type).'\\PDO';
		$this->pdo = new $c($this->dsn,$user,$password,$options,$createDb);
		
		$c = __CLASS__.'\\'.ucfirst($this->type).'\\Query';
		$this->query = new $c($this->pdo,$primaryKey,$uniqTextKey,$frozen,$this,$tablePrefix);
	}
	function createRow($type,$obj,$primaryKey='id',$uniqTextKey='uniq'){
		$properties = [];
		$postInsert = [];
		foreach($obj as $k=>$v){
			if(is_object($v)||is_array($v)){
				$pk = $this[$k]->getPrimaryKey();
				if(is_object($v)){
					if(isset($v->{$pk}))
						$this[$k][$v->{$pk}] = $v;
					else
						$this[$k][] = $v;
					$obj[$k.'_'.$primaryKey] = &$v->{$pk};
				}
				elseif(is_array($v)){
					foreach($v as $val){
						$val->{$type.'_'.$pk} = &$obj->{$primaryKey};
						$postInsert[$k][] = $val;
					}
				}
				else{
					throw new \InvalidArgumentException('createRow doesn\'t accepts ressources, type: "'.get_resource_type($v).'"');
				}
			}
			else{
				$properties[$k] = $v;
			}
		}
		$r = $this->query->createRow($type,$properties,$primaryKey,$uniqTextKey);
		foreach($postInsert as $k=>$v){
			$this[$k][] = $v;
		}
		return $r;
	}
	function readId($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		return $this->query->readId($type,$id,$primaryKey,$uniqTextKey);
	}
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		return $this->query->readRow($type,$id,$primaryKey,$uniqTextKey);
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		$properties = [];
		$postUpdate = [];
		foreach($obj as $k=>$v){
			if(is_object($v)||is_array($v)){
				
			}
			else{
				$properties[$k] = $v;
			}
		}
		foreach($postUpdate as $k=>$v){
			$this[$k][$v->{$this[$k]->getPrimaryKey()}] = $v;
		}
		return $this->query->updateRow($type,$properties,$id,$primaryKey,$uniqTextKey);
	}
	function deleteRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		return $this->query->deleteRow($type,$id,$primaryKey,$uniqTextKey);
	}
	function getPDO(){
		return $this->pdo;
	}
	private function buildDsnFromArray($config){
		$type = $config['type'].':';
		$host = isset($config['host'])&&$config['host']?'host='.$config['host']:'';
		$file = isset($config['file'])&&$config['file']?$config['file']:'';
		$port = isset($config['port'])&&$config['port']?';port='.$config['port']:null;
		$name = isset($config['name'])&&$config['name']?';dbname='.$config['name']:null;
		return $type.$host.$file.$port.$name;
	}
	function debug($enable=true){
		return $this->getPDO()->log($enable);
	}
	function loadTable($k,$primaryKey,$uniqTextKey){
		return new Table($k,$primaryKey,$uniqTextKey,$this);
	}
	function tableExists($table,$prefix=false){
		return $this->query->tableExists($table,$prefix);
	}
}