<?php
namespace Unit;
class DiInstance {
	public $name;
	public function __construct($instance) {
		$this->name = $instance;
	}
}