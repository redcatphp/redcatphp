<?php
/*
 * Route - Modular Router for Mvc with prefix
 *
 * @package Mvc
 * @version 1.2
 * @link http://github.com/surikat/Mvc/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
namespace Wild\Mvc;
use Wild\Kinetic\Di;
class Module extends Group{
	function __construct($namespace, $templateEngine = null, $prefix = 'Module\\', Di $di = null){
		parent::__construct($prefix.$namespace, $templateEngine, $di);
    }
}