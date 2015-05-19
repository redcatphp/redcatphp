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
	function addConstructParam($param){
		$this->constructParams[] = new DiInstance($param);
	}
	function addSubstitution($param){
		$this->substitutions[] = new DiInstance($param);
	}
}