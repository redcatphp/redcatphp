<?php
namespace Unit;
use Wild\Kinetic\Di;
class View {
	protected $model;
	protected $templateEngine;
	protected $di;
    function __construct($model = null, $templateEngine = null, Di $di=null){
        $this->model = $model;
        $this->templateEngine = $templateEngine;
        $this->di = $di;
    }
    function getModel(){
		return $this->model;
	}
	function getTemplateEngine(){
		return $this->templateEngine;
	}
	function __invoke($template=null){
        return call_user_func_array($this->templateEngine,func_get_args());
    }
}