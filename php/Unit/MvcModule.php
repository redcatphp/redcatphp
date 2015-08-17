<?php
namespace Unit;
use Wild\Kinetic\Di;
class MvcModule extends MvcGroup{
	function __construct($namespace, $templateEngine = null, Di $di = null){
		parent::__construct('Module\\'.$namespace, $templateEngine, $di);
    }
}