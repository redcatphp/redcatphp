<?php
namespace Unit;
class DiRule {
	public $shared = false;
	public $constructParams = [];
	public $substitutions = [];
	public $newInstances = [];
	public $instanceOf;
	public $call = [];
	public $inherit = true;
	public $shareInstances = [];
	function addConstructParam($param,$instance=false){
		if($instance)
			$param = new DiInstance($param);
		$this->constructParams[] = $param;
	}
	function addSubstitution($param,$instance=false){
		if($instance)
			$param = new DiInstance($param);
		$this->substitutions[] = $param;
	}
}