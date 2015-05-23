<?php
namespace Unit;
class Controller {
	private $model;
    function __construct($model = null){
        $this->model = $model;
    }
    function getModel(){
		return $this->model;
	}
	function action($action,$args=[]){
		if($action===__FUNCTION__||!(method_exists($this,$action)&&(new \ReflectionMethod($this, $action))->isPublic()))
			throw new \BadMethodCallException('Call to undefined or inaccessible method '.get_class($this).'::'.$action.'()');
		return call_user_func_array([$this,$action],$args);
	}
}