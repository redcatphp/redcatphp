<?php 
namespace RedBase\Maphper;
class Maphper implements \Countable, \ArrayAccess, \Iterator {
	const FIND_EXACT 	= 	0x1;
	const FIND_LIKE 	= 	0x2;
	const FIND_STARTS 	= 	0x4;
	const FIND_ENDS 	= 	0x8;
	const FIND_BIT 		= 	0x10;
	const FIND_GREATER 	= 	0x20;
	const FIND_LESS 	=	0x40;
	const FIND_EXPRESSION = 0x80;
	const FIND_AND 		= 0x100;
	const FIND_OR 		= 0x200;
	const FIND_NOT 		= 0x400;
	const FIND_BETWEEN	= 0x800;
	const FIND_NOCASE	= 0x1000;
	
	private $dataSource;
	private $relations = [];	
	private $settings = ['filter' => [], 'sort' => null, 'limit' => null, 'offset' => null, 'resultClass' => '\\stdClass'];	
	private $array = [];
	private $iterator = 0;
	private $repository;

	public function __construct(DataSource $dataSource, array $settings = null, array $relations = [], Repository $repository=null) {
		$this->dataSource = $dataSource;
		$this->settings($settings);
		$this->relations = $relations;
		if(!$repository){
			if($dataSource instanceof DataSource\Database)
				$repository = new RepositoryPDO($this->dataSource->adapter()->getPDO());
		}
		$this->repository = $repository;
	}
	public function settings(array $settings = null){
		if ($settings) $this->settings = array_replace($this->settings, $settings);
		return $this->settings;
	}
	
	public function addRelationOneToMany($relatedMapper,$foreignKeyOne=null,$foreignKeyMany=null,$primaryOne=null,$primaryMany=null){
		if(!$primaryOne)
			$primaryOne = $this->repository->getPrimaryKey();
		if(!$relatedMapper instanceof Maphper)
			$relatedMapper = $this->repository->get($relatedMapper,$primaryOne);
		$this->addRelationOne($relatedMapper,$foreignKeyOne,$primaryOne);
		$relatedMapper->addRelationMany($this,$foreignKeyMany,$primaryMany);
	}
	public function addRelationManyToOne($relatedMapper,$foreignKeyMany=null,$foreignKeyOne=null,$primaryMany=null,$primaryOne=null){
		if(!$primaryMany)
			$primaryMany = $this->repository->getPrimaryKey();
		if(!$relatedMapper instanceof Maphper)
			$relatedMapper = $this->repository->get($relatedMapper,$primaryMany);
		$this->addRelationMany($relatedMapper,$foreignKeyMany,$primaryMany);
		$relatedMapper->addRelationOne($this,$foreignKeyOne,$primaryOne);
	}
	public function addRelationManyToMany($relatedMapper, $intermediateMap=null, $primaryRel=null, $primaryInter=null, $foreignKeyRel=null, $foreignKeyInter=null){
		if(!$primaryRel)
			$primaryRel = $this->repository->getPrimaryKey();
		if(!$primaryInter)
			$primaryInter = $this->repository->getPrimaryKey();
		if(!$relatedMapper instanceof Maphper)
			$relatedMapper = $this->repository->get($relatedMapper,$primaryRel);
		if($intermediateMap && !$intermediateMap instanceof Maphper)
			$intermediateMap = $this->repository->get($intermediateMap,$primaryInter);
		if(!$intermediateMap){
			$a = [$relatedMapper->getName(),$this->getName()];
			sort($a);
			$intermediateMap = $this->repository->get(implode('_',$a),$primaryInter);
		}
		$intermediateMap->addRelationOneToMany($this);
		$intermediateMap->addRelationOneToMany($relatedMapper);
		return $this->addRelationManyMany($relatedMapper, $intermediateMap, $primaryRel, $foreignKeyRel, $foreignKeyInter);
	}
	
	public function addRelationOne($relatedMapper,$foreignKey=null,$primary=null){
		if(!$primary)
			$primary = $this->repository->getPrimaryKey();
		if($relatedMapper instanceof Maphper){
			$name = $relatedMapper->getName();
		}
		else{
			$name = $relatedMapper;
			$relatedMapper = $this->repository->get($name,$primary);
		}
		if(!$foreignKey)
			$foreignKey = $name.'_id';
		$this->addRelation($name, new Relation\One($relatedMapper, $foreignKey, $primary));
	}
	
	public function addRelationMany($relatedMapper,$foreignKey=null,$primary=null){
		if(!$primary)
			$primary = $this->repository->getPrimaryKey();
		if($relatedMapper instanceof Maphper){
			$name = $relatedMapper->getName();
		}
		else{
			$name = $relatedMapper;
			$relatedMapper = $this->repository->get($name,$primary);
		}
		if(!$foreignKey)
			$foreignKey = $this->getName().'_id';
		$this->addRelation($name, new Relation\Many($relatedMapper, $primary, $foreignKey));
	}
	public function addRelationManyMany($relatedMapper, $intermediateMap=null, $primaryRel=null, $primaryInter=null, $foreignKeyRel=null, $foreignKeyInter=null){
		if(!$primaryRel)
			$primaryRel = $this->repository->getPrimaryKey();
		if(!$primaryInter)
			$primaryInter = $this->repository->getPrimaryKey();
		if(!$relatedMapper instanceof Maphper)
			$relatedMapper = $this->repository->get($relatedMapper,$primaryRel);
		if($intermediateMap && !$intermediateMap instanceof Maphper)
			$intermediateMap = $this->repository->get($intermediateMap,$primaryInter);
		
		if(!$intermediateMap){
			$a = [$relatedMapper->getName(),$this->getName()];
			sort($a);
			$intermediateName = implode('_',$a);
			$intermediateMap = $this->repository->get($intermediateName,$primaryInter);
		}
		else{
			$intermediateName = $intermediateMap->getName();
		}
		if(!$foreignKeyRel)
			$foreignKeyRel = $this->getName().'_id';
		if(!$foreignKeyInter)
			$foreignKeyInter = $relatedMapper->getName().'_id';
		$primaryInter = $this->repository->getPrimaryKey();
		$relatedMapper->addRelation($intermediateName,new Relation\ManyMany($intermediateMap, $this, $primaryInter, $foreignKeyRel, $this->getName()));
		$this->addRelation($intermediateName,new Relation\ManyMany($intermediateMap, $relatedMapper, $primaryRel, $foreignKeyInter, $relatedMapper->getName()));
		//$relatedMapper->addRelation($this->getName(),new Relation\ManyMany($intermediateMap, $this, $primaryInter, $foreignKeyRel, $this->getName()));
		//$this->addRelation($relatedMapper->getName(),new Relation\ManyMany($intermediateMap, $relatedMapper, $primaryRel, $foreignKeyInter, $relatedMapper->getName()));
		return $intermediateMap;
	}
	
	public function addRelation($name, Relation $relation) {
		$this->relations[$name] = $relation;
	}

	public function getRelations() {
		return $this->relations;
	}
	
	public function count($group = null) {
		return $this->dataSource->findAggregate('count', $group == null ? $this->dataSource->getPrimaryKey() : $group, $group, $this->settings['filter']);
	}

	public function current() {
		return $this->wrap($this->array[$this->iterator]);
	}
		
	public function key() {
		$pk = $this->dataSource->getPrimaryKey();
		$pk = end($pk);
		return $this->array[$this->iterator]->$pk;
	}
	
	public function next() {
		++$this->iterator;
	}
	
	public function valid() {
		return isset($this->array[$this->iterator]);
	}	

	public function rewind() {
		$this->iterator = 0;
		if (empty($this->array)) $this->array = $this->dataSource->findByField($this->settings['filter'], ['order' => $this->settings['sort'], 'limit' => $this->settings['limit'], 'offset' => $this->settings['offset'] ]);
	}
	
	public function item($n) {
		$this->rewind();
		return isset($this->array[$n]) ? $this->wrap($this->array[$n]) : null;
	}
	
	private function processFilters($value) {
		//When saving to a mapper with filters, write the filters back into the object being stored
		foreach ($this->settings['filter'] as $key => $filterValue) {
			if (empty($value->$key) && !is_array($filterValue)) $value->$key = $filterValue;
		}
		return $value;
	}

	public function offsetSet($offset, $value) {
		if(is_array($value))
			$value = (object)$value;
		foreach ($this->relations as $name => $relation) {
			//If a relation has been overridden, run the overwrite
			if (isset($value->$name) &&	!($value->$name instanceof Relation\One)) $relation->overwrite($value, $value->$name);			
		}
		
		$value = $this->relationalSet($value);
		
		$value = $this->processFilters($value);
		$value = $this->wrap($value, true);
		$pk = $this->dataSource->getPrimaryKey();
		if ($offset !== null) $value->{$pk[0]} = $offset;
		$this->dataSource->save($value,$this->relations);
	}
	
	public function offsetExists($offset) {
		return (bool) $this->dataSource->findById($offset);
	}
	
	public function offsetUnset($id) {
		$this->dataSource->deleteById($id);
	}

	public function offsetGet($offset) {
		if (isset($offset)) {
			if (count($this->dataSource->getPrimaryKey()) > 1) return new MultiPk($this, $offset, $this->dataSource->getPrimaryKey());			
			return $this->wrap($this->dataSource->findById($offset));
		}
		else {
			$obj = $this->createNew();
			foreach ($this->dataSource->getPrimaryKey() as $k) $obj->$k = null;
			return $this->wrap($obj);
		}
	}

	public function createNew() {
		return (is_callable($this->settings['resultClass'])) ? call_user_func($this->settings['resultClass']) : new $this->settings['resultClass'];
	}
	
	private function wrap($object, $updateExisting = false) {		
		if (is_array($object)) {
			foreach ($object as &$o) $this->wrap($o);
			return $object;
		}
		else if (is_object($object)) {
			if (isset($object->__maphperRelationsAttached)) return $object;			
			$writeClosure = function($field, $value) {	$this->$field = $value;	};
			
			$new = $updateExisting ? $object : $this->createNew();
			$write = $writeClosure->bindTo($new, $new);
			foreach ($object as $key => $value) $write($key, $this->dataSource->processDates($value));			
			foreach ($this->relations as $name => $relation) $new->$name = $relation->getData($new);

			$new->__maphperRelationsAttached = $this;
			return $new;
		}
		return $object;
	}

	public function getErrors() {
		return $this->dataSource->getErrors();
	}
	
	public function __call($method, $args) {
		if (array_key_exists($method, $this->settings)) {
			$maphper = new Maphper($this->dataSource, $this->settings, $this->relations);
			if (is_array($maphper->settings[$method])) $maphper->settings[$method] = $args[0] + $maphper->settings[$method];
			else $maphper->settings[$method] = $args[0];
			return $maphper;
		}
		else throw new \Exception('Method Maphper::' . $method . ' does not exist');
	}
	
	public function findAggregate($function, $field, $group = null) {
		return $this->dataSource->findAggregate($function, $field, $group, $this->settings['filter']);
	}
	
	public function delete() {
		$this->array = [];
		$this->dataSource->deleteByField($this->settings['filter'], ['order' => $this->settings['sort'], 'limit' => $this->settings['limit'], 'offset' => $this->settings['offset']]);
	}
	
	private function relationalSet($value){
		foreach($value as $k=>$v){
			if(is_array($v)){
				
			}
		}
		return $value;
	}
	
	function getName(){
		return $this->dataSource->getName();
	}
}