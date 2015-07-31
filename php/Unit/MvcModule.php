<?php
namespace Unit;
class MvcModule extends MvcGroup{
	function __construct($namespace, $templateEngine = null, Di $di){
		parent::__construct('Module\\'.$namespace, $templateEngine, $di);
    }
}