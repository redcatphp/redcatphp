<?php
namespace Wild\Mvc;
use Wild\Kinetic\Di;
class Module extends Group{
	function __construct($namespace, $templateEngine = null, $prefix = 'Module\\', Di $di = null){
		parent::__construct($prefix.$namespace, $templateEngine, $di);
    }
}