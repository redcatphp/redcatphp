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
	protected $recursiveStorage;
	function __construct(RedBase $redbase,$type,$entityClassPrefix='Model\\',$entityClassDefault='stdClass',$primaryKey='id',$uniqTextKey='uniq',array $config=[]){
		$this->redbase = $redbase;
		$this->type = $type;
		$this->entityClassPrefix = (array)$entityClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->primaryKey = $primaryKey;
		$this->uniqTextKey = $uniqTextKey;
		$this->construct($config);
		$this->recursiveStorage = new \SplObjectStorage();
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
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return;
		$obj = $this->entityFactory($type);
		$this->trigger($type,'beforeRead',$obj);
		$obj = $this->readQuery($type,$id,$primaryKey,$uniqTextKey,$obj);
		if($obj){
			$obj->_type = $type;
			$this->trigger($type,'afterRead',$obj);
		}
		return $obj;
	}
	function deleteRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return;
		if(is_object($id)){
			$obj = $id;
			if(isset($obj->$primaryKey))
				$id = $obj->$primaryKey;
			elseif(isset($obj->$uniqTextKey))
				$id = $obj->$uniqTextKey;
		}
		else{
			$obj = $this->entityFactory($type);
		}
		$this->trigger($type,'beforeDelete',$obj);
		$r = $this->deleteQuery($type,$id,$primaryKey,$uniqTextKey);
		if($r)
			$this->trigger($type,'afterDelete',$obj);
		return $r;
	}
	
	function putRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		$obj->_type = $type;
		$properties = [];
		$oneNew = [];
		$oneUp = [];
		$manyNew = [];
		$one2manyNew = [];
		$many2manyNew = [];
		$fk = [];
		
		if(isset($id)&&$uniqTextKey&&!self::canBeTreatedAsInt($id)){
			$obj->$uniqTextKey = $id;
		}
		
		if(isset($obj->$primaryKey)){
			$id = $obj->$primaryKey;
		}
		elseif($uniqTextKey&&isset($obj->$uniqTextKey)){
			$id = $this->readId($type,$obj->$uniqTextKey,$primaryKey,$uniqTextKey);
			$obj->$primaryKey = $id;
		}
		
		$update = isset($id);
		
		foreach($obj as $key=>$v){
			$k = $key;
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
							$oneUp[$t][$v->$pk] = $v;
						else
							$oneNew[$t][] = $v;
						$rc = $k.'_'.$pk;
						$properties[$rc] = $obj->$rc = $v->$pk;
						$addFK = [$type,$t,$rc,$pk,$xclusive];
						if(!in_array($addFK,$fk))
							$fk[] = $addFK;
						$obj->$key = $v;
					break;
					case 'many':
						foreach($v as &$val){
							if(is_array($val))
								$val = $this->arrayToEntity($val,$k);
							$t = $this->findEntityTable($val,$k);
							$rc = $type.'_'.$primaryKey;
							$val->$rc = &$obj->$primaryKey;
							$one2manyNew[$t][] = $val;
							$addFK = [$t,$type,$rc,$primaryKey,$xclusive];
							if(!in_array($addFK,$fk))
								$fk[] = $addFK;
						}
						$obj->$key = $v;
					break;
					case 'many2many':
						$inter = [$type,$k];
						sort($inter);
						$inter = implode('_',$inter);
						$rc = $type.'_'.$primaryKey;
						foreach($v as &$val){
							if(is_array($val))
								$val = $this->arrayToEntity($val,$k);
							$t = $this->findEntityTable($val,$k);
							$pk = $this[$t]->getPrimaryKey();
							$rc2 = $k.'_'.$pk;
							$interm = $this->entityFactory($inter);
							$interm->$rc = &$obj->$primaryKey;
							$interm->$rc2 = &$val->$pk;
							$manyNew[$t][] = $val;
							$many2manyNew[$t][$k][] = $interm;
							$addFK = [$inter,$t,$rc2,$pk,$xclusive];
							if(!in_array($addFK,$fk))
								$fk[] = $addFK;
						}
						$addFK = [$inter,$type,$rc,$primaryKey,$xclusive];
						if(!in_array($addFK,$fk))
							$fk[] = $addFK;
						$obj->$key = $v;
					break;
				}
			}
			else{
				$properties[$k] = $v;
			}
		}
		
		$this->trigger($type,'beforeRecursive',$obj,true,true);
		$this->trigger($type,'beforePut',$obj);
		
		foreach($oneNew as $t=>$ones){
			foreach($ones as $one){
				$this[$t][] = $one;
			}
		}
		foreach($oneUp as $t=>$ones){
			foreach($ones as $i=>$one){
				$this[$t][$i] = $one;
			}
		}
		
		if($update){
			$this->trigger($type,'beforeUpdate',$obj);
			$r = $this->updateQuery($type,$properties,$id,$primaryKey,$uniqTextKey);
			$this->trigger($type,'afterUpdate',$obj);
		}
		else{
			if(array_key_exists($primaryKey,$properties))
				unset($properties[$primaryKey]);
			$this->trigger($type,'beforeCreate',$obj);
			$r = $this->createQuery($type,$properties,$primaryKey,$uniqTextKey);
			$this->trigger($type,'afterCreate',$obj);
		}
		$obj->{$primaryKey} = $r;
		foreach($one2manyNew as $k=>$v){
			if($update){
				$except = [];
				foreach($v as $val){
					$t = $this->findEntityTable($val,$k);
					$pk = $this[$t]->getPrimaryKey();
					if(isset($val->$pk))
						$except[] = $val->$pk;
						
				}
				$this->one2manyDelete($obj,$k,$except);
			}
			foreach($v as $val){
				$this[$k][] = $val;
			}
		}
		foreach($manyNew as $k=>$v){
			foreach($v as $val){
				$this[$k][] = $val;
			}
		}
		foreach($many2manyNew as $t=>$v){
			foreach($v as $k=>$val){
				$via = [$type,$k];
				sort($via);
				$via = implode('_',$via);
				if($update){
					$except = [];
					$viaFk = $k.'_'.$this[$t]->getPrimaryKey();
					foreach($this->many2manyLink($obj,$t,$via,$viaFk) as $id=>$old){
						$pk = $this[$via]->getPrimaryKey();
						unset($old->$pk);
						if(false!==$i=array_search($old,$val)){
							$val[$i]->$pk = $id;
							$except[] = $id;
						}
					}
					$this->many2manyDelete($obj,$t,$via,$viaFk,$except);
				}
				foreach($val as $value)
					$this[$via][] = $value;
			}
		}

		if(method_exists($this,'addFK')){
			foreach($fk as list($type,$targetType,$property,$targetProperty,$isDep)){
				$this->addFK($type,$targetType,$property,$targetProperty,$isDep);
			}
		}
		
		$this->trigger($type,'afterPut',$obj);
		$this->trigger($type,'afterRecursive',$obj,true,false);
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
	
	function trigger($type, $event, $row, $recursive=false, $flow=null){
		return $this[$type]->trigger($event, $row, $recursive, $flow);
	}
	function triggerExec($events, $type, $event, $row, $recursive=false, $flow=null){
		if($recursive){
			if($this->recursiveStorage->contains($row))
				return;
			if($flow)
				$this->recursiveStorage->attach($row);
		}

		if($row instanceof Observer){
			foreach($events as $calls){
				foreach($calls as $call){
					if(is_string($call))
						call_user_func([$row,$call], $this);
					else
						call_user_func($call, $row, $this);
				}
			}
		}
		
		if($recursive){
			foreach($row as $v){
				if(is_array($v)){
					foreach($v as $k=>$val){
						if(is_object($val)){
							$type = $this->findEntityTable($val,$k);
							$this->trigger($type, $event, $val, true, $flow);
						}
					}
				}
				elseif(is_object($v)){
					$type = $this->findEntityTable($val,$k);
					$this->trigger($type, $event, $v, true, $flow);
				}
			}
			if($flow===false)
				$this->recursiveStorage->detach($row);
		}
	}
	
	function create($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
		}
		else{
			list($type,$obj) = func_get_args();
		}
		return $this[$type]->offsetSet(null,$obj);
	}
	function read($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
			$pk = $this[$type]->getPrimaryKey();
			$id = $obj->$pk;
		}
		else{
			list($type,$id) = func_get_args();
		}
		return $this[$type]->offsetGet($id);
	}
	function update($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
			$pk = $this[$type]->getPrimaryKey();
			$id = $obj->$pk;
		}
		elseif(func_num_args()<3){
			list($type,$obj) = func_get_args();
			$pk = $this[$type]->getPrimaryKey();
			$id = $obj->$pk;
		}
		else{
			list($type,$id,$obj) = func_get_args();
		}
		return $this[$type]->offsetSet($id,$obj);
	}
	function delete($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
			$id = $obj;
		}
		else{
			list($type,$id) = func_get_args();
		}
		return $this[$type]->offsetUnset($id);
	}
	
	static function canBeTreatedAsInt($value){
		return (bool)(strval($value)===strval(intval($value)));
	}
	
	static function snippet($text,$query,$tokens=15,$start='<b>',$end='</b>',$sep=' <b>...</b> '){
		if(!trim($text))
			return '';
		$words = implode('|', explode(' ', preg_quote($query)));
		$s = '\s\x00-/:-@\[-`{-~'; //character set for start/end of words
		preg_match_all('#(?<=['.$s.']).{1,'.$tokens.'}(('.$words.').{1,'.$tokens.'})+(?=['.$s.'])#uis', $text, $matches, PREG_SET_ORDER);
		$results = [];
		foreach($matches as $line)
			$results[] = $line[0];
		$result = implode($sep, $results);
		$result = preg_replace('#'.$words.'#iu', $start.'$0'.$end, $result);
		return $sep.$result.$sep;
	}
	
	function one2manyDelete($obj,$k,$except=[]){
		$pk = $this[$k]->getPrimaryKey();
		foreach($this->one2many($obj,$k,$except) as $o){
			if(!in_array($o->$pk,$except))
				$this->delete($o);
		}
	}
	function many2manyDelete($obj,$k,$via=null,$except=[]){
		$t = [$this->findEntityTable($obj),$k];
		sort($t);
		$t = implode('_',$t);
		$pk = $this[$t]->getPrimaryKey();
		foreach($this->many2manyLink($obj,$k,$via) as $o){
			if(!in_array($o->$pk,$except))
				$this->delete($o);
		}
	}
	
	//abstract function many2one($obj,$type){}
	//abstract function one2many($obj,$type){}
	//abstract function many2many($obj,$type){}
	//abstract function many2manyLink($obj,$type){}
}