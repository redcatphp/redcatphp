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
		if(isset($obj->_table)){
			$table = $obj->_table;
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
		$obj->_table = $type;
		return $this->putRow($type,$obj,null,$primaryKey,$uniqTextKey);
	}
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return false;
		$row = $this->read($type,$id,$primaryKey,$uniqTextKey);
		$row->_table = $type;
		return $row;
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		$obj->_table = $type;
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
			if(substr($k,0,1)=='_'){
				if(substr($k,1,4)=='m2m_'){ //ManyToMany
					$k = substr($k,5);
					$inter = [$type,$k];
					sort($inter);
					$inter = implode('_',$inter);
					$interc = $this->findEntityClass($inter);
					foreach($v as $val){
						$t = $this->findEntityTable($val,$k);
						$pk = $this[$t]->getPrimaryKey();
						$interm = new $interc();
						$interm->{$type.'_'.$primaryKey} = &$obj->$primaryKey;
						$interm->{$k.'_'.$pk} = &$val->$pk;
						$postPut[$t][] = $val;
						$postPut[$inter][] = $interm;
						$addFK = [$inter,$t,$k.'_'.$pk,$pk,$xclusive];
						if(!in_array($addFK,$fk))
							$fk[] = $addFK;
					}
					$addFK = [$inter,$type,$type.'_'.$primaryKey,$primaryKey,$xclusive];
					if(!in_array($addFK,$fk))
						$fk[] = $addFK;
				}
				continue;
			}
			if(is_object($v)){ //ManyToOne
				$t = $this->findEntityTable($v,$k);
				$pk = $this[$t]->getPrimaryKey();
				if(isset($v->$pk))
					$this[$t][$v->$pk] = $v;
				else
					$this[$t][] = $v;
				$properties[$k.'_'.$primaryKey] = $obj->{$k.'_'.$primaryKey} = $v->$pk;
				$addFK = [$type,$t,$k.'_'.$primaryKey,$pk,$xclusive];
				if(!in_array($addFK,$fk))
					$fk[] = $addFK;
			}
			elseif(is_array($v)){ //OneToMany
				foreach($v as $val){
					$t = $this->findEntityTable($val,$k);
					$pk = $this[$t]->getPrimaryKey();
					$val->{$type.'_'.$pk} = &$obj->$primaryKey;
					$postPut[$t][] = $val;
					$addFK = [$t,$type,$type.'_'.$pk,$primaryKey,$xclusive];
					if(!in_array($addFK,$fk))
						$fk[] = $addFK;
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
}