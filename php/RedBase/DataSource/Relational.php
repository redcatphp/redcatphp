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
		$this->query = new $c($this->pdo,$primaryKey,$frozen,$this,$tablePrefix);
	}
	function createRow($type,$obj,$primaryKey='id'){
		$properies = [];
		$postInsert = [];
		foreach($obj as $k=>$v){
			if(!is_scalar($v)){
				$pk = $this[$k]->primaryKey;
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
				$properies[$k] = $v;
			}
		}
		$r = $this->query->createRow($type,$properies,$primaryKey);
		foreach($postInsert as $k=>$v){
			$this[$k][] = $v;
		}
		return $r;
	}
	function readRow($type,$id,$primaryKey='id'){
		return $this->query->readRow($type,$id,$primaryKey);
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id'){
		return $this->query->updateRow($type,$obj,$id,$primaryKey);
	}
	function deleteRow($type,$id,$primaryKey='id'){
		return $this->query->deleteRow($type,$id,$primaryKey);
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
}