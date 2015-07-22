<?php
namespace RedBase;
use RedBase\Helper\CaseConvert;
abstract class DataSource implements \ArrayAccess{
	protected $redbase;
	protected $type;
	protected $entityClassPrefix;
	protected $entityClassDefault;
	protected $primaryKey;
	protected $uniqTextKey;
	protected $tableMap = [];
	protected $entityFactory;
	protected static $phpReservedKeywords = ['__halt_compiler','abstract','and','array','as','break','callable','case','catch','class','clone','const','continue','declare','default','die','do','echo','else','elseif','empty','enddeclare','endfor','endforeach','endif','endswitch','endwhile','eval','exit','extends','final','for','foreach','function','global','goto','if','implements','include','include_once','instanceof','insteadof','interface','isset','list','namespace','new','or','print','private','protected','public','require','require_once','return','static','switch','throw','trait','try','unset','use','var','while','xor','__class__','__dir__','__file__','__function__','__line__','__method__','__namespace__','__trait__'];
	function __construct(RedBase $redbase,$type,$entityClassPrefix='Model\\',$entityClassDefault='stdClass',$primaryKey='id',$uniqTextKey='uniq',array $config=[]){
		$this->redbase = $redbase;
		$this->type = $type;
		$this->entityClassPrefix = (array)$entityClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->primaryKey = $primaryKey;
		$this->uniqTextKey = $uniqTextKey;
		$this->construct($config);
	}
	function getUniqTextKey(){
		return $this->uniqTextKey;
	}
	function getPrimaryKey(){
		return $this->primaryKey;
	}
	function findEntityClass($name=null){
		if($name){
			$name = CaseConvert::ucw($name);
			foreach($this->entityClassPrefix as $prefix){
				$c = $prefix;
				if(substr($prefix,-1)==='\\'&&in_array(strtolower($name),self::$phpReservedKeywords))
					$c .= '_';
				$c .= $name;
				if(class_exists($c))
					return $c;
			}
		}
		return $this->entityClassDefault;
	}
	function findEntityTable($obj,$default=null){
		$table = $default;
		if(isset($obj->_type)){
			$table = $obj->_type;
		}
		else{
			$c = get_class($obj);
			if($c!=$this->entityClassDefault){
				$c = CaseConvert::lcw($c);
				foreach($this->entityClassPrefix as $prefix){
					if($prefix&&strpos($c,$prefix)===0){
						$c = substr($c,strlen($prefix));
						if(substr($c,0,1)==='_'&&in_array(strtolower(substr($c,1)),self::$phpReservedKeywords))
							$c = substr($c,1);
						$table = $c;
						break;
					}
				}
			}
		}
		return $table;
	}
	function arrayToEntity(array $array,$default=null){
		if(isset($array['_type']))
			$type = $array['_type'];
		elseif($default)
			$type = $default;
		else
			$type = $this->entityClassDefault;
		$obj = $this->entityFactory($type);
		foreach($array as $k=>$v){
			$obj->$k = $v;
		}
		return $obj;
	}
	function offsetGet($k){
		if(!isset($this->tableMap[$k]))
			$this->tableMap[$k] = $this->loadTable($k,$this->primaryKey,$this->uniqTextKey);
		return $this->tableMap[$k];
	}
	function offsetSet($k,$v){
		if(!is_object($v))
			$v = $this->loadTable($v,$this->primaryKey,$this->uniqTextKey);
		$this->tableMap[$k] = $v;
	}
	function offsetExists($k){
		return isset($this->tableMap[$k]);
	}
	function offsetUnset($k){
		if(isset($this->tableMap[$k]))
			unset($this->tableMap[$k]);
	}
	function loadTable($k,$primaryKey,$uniqTextKey){
		$c = 'RedBase\DataTable\\'.ucfirst($this->type);
		return new $c($k,$primaryKey,$uniqTextKey,$this);
	}
	function construct(array $config=[]){}
	function createRow($type,$obj,$primaryKey='id',$uniqTextKey='uniq'){
		$obj->_type = $type;
		return $this->putRow($type,$obj,null,$primaryKey,$uniqTextKey);
	}
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return false;
		$row = $this->read($type,$id,$primaryKey,$uniqTextKey);
		$row->_type = $type;
		return $row;
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		$obj->_type = $type;
		return $this->putRow($type,$obj,$id,$primaryKey,$uniqTextKey);
	}
	function deleteRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return false;
		return $this->delete($type,$id,$primaryKey,$uniqTextKey);
	}
	
	function putRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		$properties = [];
		$postPut = [];
		$fk = [];
		foreach($obj as $k=>$v){
			$xclusive = substr($k,-3)=='_x_';
			if($xclusive)
				$k = substr($k,0,-3);
			$relation = false;
			if(substr($k,0,1)=='_'){
				if(substr($k,1,4)=='one_'){
					$k = substr($k,5);
					$relation = 'one';
				}
				elseif(substr($k,1,5)=='many_'){
					$k = substr($k,6);
					$relation = 'many';
				}
				elseif(substr($k,1,10)=='many2many_'){
					$k = substr($k,11);
					$relation = 'many2many';
				}
				else{
					continue;
				}
			}
			elseif(is_object($v)){
				$relation = 'one';
			}
			elseif(is_array($v)){
				$relation = 'many';
			}
			if($relation){
				switch($relation){
					case 'one':
						if(is_array($v))
							$v = $this->arrayToEntity($v,$k);
						$t = $this->findEntityTable($v,$k);
						$pk = $this[$t]->getPrimaryKey();
						if(isset($v->$pk))
							$this[$t][$v->$pk] = $v;
						else
							$this[$t][] = $v;
						$rc = $k.'_'.$pk;
						$properties[$rc] = $obj->$rc = $v->$pk;
						$addFK = [$type,$t,$rc,$pk,$xclusive];
						if(!in_array($addFK,$fk))
							$fk[] = $addFK;
					break;
					case 'many':
						foreach($v as $val){
							if(is_array($val))
								$val = $this->arrayToEntity($val,$k);
							$t = $this->findEntityTable($val,$k);
							$rc = $type.'_'.$primaryKey;
							$val->$rc = &$obj->$primaryKey;
							$postPut[$t][] = $val;
							$addFK = [$t,$type,$rc,$primaryKey,$xclusive];
							if(!in_array($addFK,$fk))
								$fk[] = $addFK;
						}						
					break;
					case 'many2many':
						$inter = [$type,$k];
						sort($inter);
						$inter = implode('_',$inter);
						$rc = $type.'_'.$primaryKey;
						foreach($v as $val){
							if(is_array($val))
								$val = $this->arrayToEntity($val,$k);
							$t = $this->findEntityTable($val,$k);
							$pk = $this[$t]->getPrimaryKey();
							$rc2 = $k.'_'.$pk;
							$interm = $this->entityFactory($inter);
							$interm->$rc = &$obj->$primaryKey;
							$interm->$rc2 = &$val->$pk;
							$postPut[$t][] = $val;
							$postPut[$inter][] = $interm;
							$addFK = [$inter,$t,$rc2,$pk,$xclusive];
							if(!in_array($addFK,$fk))
								$fk[] = $addFK;
						}
						$addFK = [$inter,$type,$rc,$primaryKey,$xclusive];
						if(!in_array($addFK,$fk))
							$fk[] = $addFK;
					break;
				}
			}
			else{
				$properties[$k] = $v;
			}
		}
		if(isset($id)){
			$r = $this->update($type,$properties,$id,$primaryKey,$uniqTextKey);
		}
		else{
			$r = $this->create($type,$properties,$primaryKey,$uniqTextKey);
		}
		$obj->{$primaryKey} = $r;
		foreach($postPut as $k=>$v){
			foreach($v as $val){
				$this[$k][] = $val;
			}
		}
		if(method_exists($this,'addFK')){
			foreach($fk as list($type,$targetType,$property,$targetProperty,$isDep)){
				$this->addFK($type,$targetType,$property,$targetProperty,$isDep);
			}
		}
		return $r;
	}
	
	function entityFactory($name){
		if($this->entityFactory){
			$row = call_user_func($this->entityFactory,$name);
		}
		else{
			$c = $this->findEntityClass($name);
			$row = new $c;
		}
		$row->_type = $name;
		return $row;
	}
	
	function setEntityFactory($factory){
		$this->entityFactory = $factory;
	}
	
	//abstract function many2one($obj,$type){}
	//abstract function one2many($obj,$type){}
	//abstract function many2many($obj,$type){}
}