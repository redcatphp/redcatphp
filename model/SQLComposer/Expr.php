<?php namespace surikat\model\SQLComposer;
/**
 * A container to denote an expression to be directly embedded
 */
class Expr {
	public $value, $params, $mysqli_types;
	public function __construct($val, array $params=array(), $mysqli_types="") {
		$this->value = $val;
		$this->params = $params;
		$this->mysqli_types = $mysqli_types;
	}
}