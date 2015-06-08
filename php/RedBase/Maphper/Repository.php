<?php
namespace RedBase\Maphper;
class Repository{
	protected $factory;
	private $registry = [];
	function __construct($factory){
		$this->factory = $factory;
	}
	function create($table, $primary='id'){
		return new Maphper(call_user_func($this->factory,$table,$primary));
	}
	function get($table,$primary='id'){
		if(!isset($this->registry[$table][$primary]))
			$this->registry[$table][$primary] = $this->create($table,$primary);
		return $this->registry[$table][$primary];
	}
	
}