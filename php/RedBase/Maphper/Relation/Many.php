<?php 
namespace RedBase\Maphper\Relation;
class Many implements \RedBase\Maphper\Relation {
	private $mapper;
	private $parentField;
	private $localField;
	private $criteria = [];
	
	private $writeCallback;
	private $isDependent;
	
	public function __construct(\RedBase\Maphper\Maphper $mapper, $parentField, $localField, array $critiera = [], $isDependent=true) {
		$this->mapper = $mapper;
		$this->parentField = $parentField;
		$this->localField = $localField;
		$this->criteria = $critiera;
		$this->isDependent = $isDependent;
	}
	
	function isDependent(){
		return $this->isDependent;
	}
	function parentField(){
		return $this->parentField;
	}
	function localField(){
		return $this->localField;
	}
	
	public function getData($parentObject){
		//return $this->mapper->filter([$this->localField => $parentObject->{$this->parentField}]);
		$writeCallback = $this->writeCallback;
		return function($id)use($writeCallback){
			foreach($writeCallback as $c)
				$c($id);
		};
	}
	
	public function overwrite($parentObject, &$data) {
		$mapper = $this->mapper;
		foreach($data as $row){
			$localField = $this->localField;
			$this->writeCallback[] = function($id)use($localField,$mapper,$row){
				$row->{$localField} = $id;
				$mapper[] = $row;
			};
		}
	}
}