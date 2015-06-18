<?php
namespace RedBase;
abstract class DataSource implements DataSourceInterface{
	protected $globality;
	protected $entityClassPrefix;
	protected $entityClassDefault;
	protected $primaryKey;
	protected $tableMap = [];
	abstract function createRow($obj);
	abstract function readRow($id);
	abstract function updateRow($obj,$id=null);
	abstract function deleteRow($id);
	function __construct(Globality $globality,$entityClassPrefix=null,$entityClassDefault='stdClass',$primaryKey='id',array $config){
		$this->globality = $globality;
		$this->entityClassPrefix = (array)$entityClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->primaryKey = $primaryKey;
	}
	function getPrimaryKey(){
		return $this->primaryKey;
	}
	function findEntityClass($name){
		foreach($this->entityClassPrefix as $prefix){
			$c = $prefix;
			if(substr($prefix,-1)==='\\'&&in_array(strtolower($name),['__halt_compiler','abstract','and','array','as','break','callable','case','catch','class','clone','const','continue','declare','default','die','do','echo','else','elseif','empty','enddeclare','endfor','endforeach','endif','endswitch','endwhile','eval','exit','extends','final','for','foreach','function','global','goto','if','implements','include','include_once','instanceof','insteadof','interface','isset','list','namespace','new','or','print','private','protected','public','require','require_once','return','static','switch','throw','trait','try','unset','use','var','while','xor','__class__','__dir__','__file__','__function__','__line__','__method__','__namespace__','__trait__',]))
				$c .= '_';
			$c .= $name;
			if(class_exists($c))
				return $c;
		}
		return $this->entityClassDefault;
	}
	function offsetGet($k){
		if(!isset($this->tableMap[$k]))
			$this->tableMap[$k] = $this->loadTable($k,$this->primaryKey);
		return $this->tableMap[$k];
	}
	function offsetSet($k,$v){
		if(!is_object($v))
			$v = $this->loadTable($v);
		$this->tableMap[$k] = $v;
	}
	function offsetExists($k){
		return isset($this->tableMap[$k]);
	}
	function offsetUnset($k){
		if(isset($this->tableMap[$k]))
			unset($this->tableMap[$k]);
	}
	private function loadTable($k,$primaryKey){
		return new Table($k,$primaryKey,$this);
	}
}