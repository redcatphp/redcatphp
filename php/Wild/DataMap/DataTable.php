<?php
namespace Wild\DataMap;
use Wild\DataMap\Helper\Pagination;
abstract class DataTable implements \ArrayAccess,\Iterator,\Countable{
	private static $defaultEvents = [
		'beforeRecursive',
		'beforePut',
		'beforeCreate',
		'beforeRead',
		'beforeUpdate',
		'beforeDelete',
		'afterPut',
		'afterCreate',
		'afterRead',
		'afterUpdate',
		'afterDelete',
		'afterRecursive',
	];
	private $events = [];	
	protected $name;
	protected $primaryKey;
	protected $uniqTextKey;
	protected $dataSource;
	protected $data = [];
	protected $useCache = true;
	protected $counterCall;
	
	function __construct($name,$primaryKey='id',$uniqTextKey='uniq',$dataSource){
		$this->name = $name;
		$this->primaryKey = $primaryKey;
		$this->uniqTextKey = $uniqTextKey;
		$this->dataSource = $dataSource;
		foreach(self::$defaultEvents as $event)
			$this->on($event);
	}
	function getPrimaryKey(){
		return $this->primaryKey;
	}
	function getUniqTextKey(){
		return $this->uniqTextKey;
	}
	function getDataSource(){
		return $this->dataSource;
	}
	function setUniqTextKey($uniqTextKey='uniq'){
		$this->uniqTextKey = $uniqTextKey;
	}
	function offsetExists($id){
		return (bool)$this->readId($id);
	}
	function offsetGet($id){
		if(!$this->useCache||!array_key_exists($id,$this->data))
			$row = $this->readRow($id);
		else
			$row = $this->data[$id];
		if($this->useCache)
			$this->data[$id] = $row;
		return $row;
	}
	function offsetSet($id,$obj){
		if(is_array($obj)){
			$tmp = $obj;
			$obj = $this->dataSource->entityFactory($this->name);
			foreach($tmp as $k=>$v)
				$obj->$k = $v;
			unset($tmp);
		}
		if(!$id){
			$id = $this->putRow($obj);
			$obj->{$this->primaryKey} = $id;
		}
		else{
			$this->putRow($obj,$id);
		}
		if($this->useCache)
			$this->data[$id] = $obj;
		return $obj;
	}
	function offsetUnset($id){
		return $this->deleteRow($id);
	}
	function rewind(){
		reset($this->data);
	}
	function current(){
		return current($this->data);
	}
	function key(){
		return key($this->data);
	}
	function next(){
		return next($this->data);
	}
	function valid(){
		return key($this->data)!==null;
	}
	function count(){
		if($this->counterCall)
			return call_user_func($this->counterCall,$this);
		else
			return count($this->data);
	}
	function paginate($page,$limit=2,$href='',$prefix='?page=',$maxCols=6){
		$pagination = new Pagination();
		$pagination->setLimit($limit);
		$pagination->setMaxCols($maxCols);
		$pagination->setHref($href);
		$pagination->setPrefix($prefix);
		$pagination->setCount($this->count());
		$pagination->setPage($page);
		if($pagination->resolve($page)){
			$this->limit($pagination->limit);
			$this->offset($pagination->offset);
			return $pagination;
		}
	}
	function setCache($enable){
		$this->useCache = (bool)$enable;
	}
	function resetCache(){
		$this->data = [];
	}
	function readId($id){
		return $this->dataSource->readId($this->name,$id,$this->primaryKey,$this->uniqTextKey);
	}
	function readRow($id){
		return $this->dataSource->readRow($this->name,$id,$this->primaryKey,$this->uniqTextKey);
	}
	function putRow($obj,$id=null){
		return $this->dataSource->putRow($this->name,$obj,$id,$this->primaryKey,$this->uniqTextKey);
	}
	function deleteRow($id){
		return $this->dataSource->deleteRow($this->name,$id,$this->primaryKey,$this->uniqTextKey);
	}
	function update($id){
		$this[$id] = $this[$id];
	}
	function getClone(){
		return clone $this;
	}
	
	function loadOne($obj){
		return $obj->{'_one_'.$this->name} = $this->one($obj)->getRow();
	}
	function loadMany($obj){
		return $obj->{'_many_'.$this->name} = $this->many($obj)->getAll();
	}
	function loadMany2many($obj,$via=null){
		return $obj->{'_many2many_'.$this->name} = $this->many2many($obj,$via)->getAll();
	}
	function one($obj){
		return $this->dataSource->many2one($obj,$this->name);
	}
	function many($obj){
		return $this->dataSource->one2many($obj,$this->name);
	}
	function many2many($obj,$via=null){
		return $this->dataSource->many2many($obj,$this->name,$via);
	}
	
	//abstract function getAll();
	//abstract function getRow();
	
	function on($event,$call=null,$index=0,$prepend=false){
		if($index===true){
			$prepend = true;
			$index = 0;
		}
		if(is_null($call))
			$call = $event;
		if(!isset($this->events[$event][$index]))
			$this->events[$event][$index] = [];
		if($prepend)
			array_unshift($this->events[$event][$index],$call);
		else
			$this->events[$event][$index][] = $call;
		return $this;
	}
	function off($event,$call=null,$index=0){
		if(func_num_args()===1){
			if(isset($this->events[$event]))
				unset($this->events[$event]);
		}
		elseif(func_num_args()===2){
			foreach($this->events[$event] as $index){
				if(false!==$i=array_search($call,$this->events[$event][$index],true)){
					unset($this->events[$event][$index][$i]);
				}
			}
		}
		elseif(isset($this->events[$event][$index])){
			if(!$call)
				unset($this->events[$event][$index]);
			elseif(false!==$i=array_search($call,$this->events[$event][$index],true))
				unset($this->events[$event][$index][$i]);
		}
		return $this;
	}
	function trigger($event, $row, $recursive=false, $flow=null){
		if(isset($this->events[$event]))
			$this->dataSource->triggerExec($this->events[$event], $this->name, $event, $row, $recursive, $flow);
		return $this;
	}
	static function setDefaultEvents(array $events){
		self::$defaultEvents = $events;
	}
	static function getDefaultEvents(){
		return self::$defaultEvents;
	}
	function setCounter($call){
		$this->counterCall = $call;
	}
}