<?php
namespace Unit;
class MvcModule extends MvcGroup{
	function __construct($namespace, $template = null, Di $di){
		parent::__construct('Module\\'.$namespace, $template, $di);
    }
}