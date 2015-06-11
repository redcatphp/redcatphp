<?php
namespace RedBase\Maphper;
class Repository implements \ArrayAccess{
	private $factory;
	private $primary;
	private $registry = [];
	function __construct($factory,$primary='id'){
		$this->factory = $factory;
		$this->primary = $primary;
	}
	function getPrimaryKey(){
		return $this->primary;
	}
	function linkOneToMany($one,$many,$foreignKeyOne=null,$foreignKeyMany=null,$primaryOne='id',$primaryMany='id'){
		if(!$one instanceof Maphper)
			$one = $this[$one];
		if(!$many instanceof Maphper)
			$many = $this[$many];
		$many->addRelationOne($one,$foreignKeyOne,$primaryOne);
		$one->addRelationMany($many,$foreignKeyMany,$primaryMany);
	}
	function linkManyToOne($many,$one,$foreignKeyMany=null,$foreignKeyOne=null,$primaryMany='id',$primaryOne='id'){
		$this->linkOneToMany($one,$many,$foreignKeyOne,$foreignKeyMany,$primaryOne,$primaryMany);
	}
	function linkManyToMany($many1,$many2,$intermediateMap=null,$primaryRel='id', $foreignKeyRel=null, $foreignKeyInter=null){
		if(!$many1 instanceof Maphper)
			$many1 = $this[$many1];
		return $many1->addRelationManyToMany($many2,$intermediateMap,$primaryRel, $foreignKeyRel, $foreignKeyInter);
	}
	private function create($table,$primary=null){
		if(!$primary)
			$primary = $this->primary;
		return new Maphper(call_user_func($this->factory,$table,$primary),null,[],$this);
	}
	function get($table,$primary=null){
		if(!$primary||$primary===$this->primary)
			return $this->offsetGet($table);
		$k = $table.':'.$primary;
		if(!isset($this->registry[$k]))
			$this->registry[$k] = $this->create($table,$primary);
		return $this->registry[$k];
			
	}
	function __isset($k){
		return $this->offsetExists($k);
	}
	function __get($k){
		return $this->offsetGet($k);
	}
	function __set($k,$v){
		$this->offsetSet($k,$v);
	}
	function __unset($k){
		$this->offsetUnset($k);
	}
	function offsetExists($k){
		return isset($this->registry[$k]);
	}
	function offsetGet($k){
		if(!isset($this->registry[$k]))
			$this->registry[$k] = $this->create($k);
		return $this->registry[$k];
	}
	function offsetSet($k,$v){
		$this->registry[$k] = $v;
	}
	function offsetUnset($k){
		if(isset($this->registry[$k]))
			unset($this->registry[$k]);
	}
}