<?php
namespace Wild\Mvc;
use Wild\Kinetic\Di;
class MvcGroup extends MvcRoute{
	function __construct($namespace, $templateEngine = null, Di $di){
		$model = $namespace.'\\Model';
		$view = $namespace.'\\View';
		$controller = $namespace.'\\Controller';
		parent::__construct($model, $view, $controller, $templateEngine, $di);
    }
}