<?php
namespace Wild\Mvc;
use Wild\Kinetic\Di;
class Group extends Route{
	function __construct($namespace, $templateEngine = null, Di $di){
		$model = $namespace.'\\Model';
		$view = $namespace.'\\View';
		$controller = $namespace.'\\Controller';
		parent::__construct($model, $view, $controller, $templateEngine, $di);
    }
}