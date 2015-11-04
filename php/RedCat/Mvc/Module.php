<?php
/*
 * Route - Modular Router for Mvc with prefix
 *
 * @package Mvc
 * @version 1.3
 * @link http://github.com/redcatphp/Mvc/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://redcatphp.com
 */
namespace RedCat\Mvc;
use RedCat\Wire\Di;
class Module extends Group{
	function __construct($namespace, $templateEngine = null, $prefix = 'Module\\', Di $di = null){
		parent::__construct($prefix.$namespace, $templateEngine, $di);
    }
}