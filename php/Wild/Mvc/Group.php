<?php
/*
 * Route - Modular Router for Mvc
 *
 * @package Mvc
 * @version 1.0
 * @link http://github.com/surikat/Mvc/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
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