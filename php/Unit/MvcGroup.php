<?php namespace Unit;
class MvcGroup extends MvcRoute{
	function __construct($namespace, $template = null, Di $di){
		$model = $namespace.'\\Model';
		$view = $namespace.'\\View';
		$controller = $namespace.'\\Controller';
		parent::__construct($model, $view, $controller, $template, $di);
    }
}