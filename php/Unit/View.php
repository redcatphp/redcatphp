<?php
namespace Unit;
class View {
	protected $model;
	protected $template;
    function __construct($model = null, $template = null){
        $this->model = $model;
        $this->template = $template;
    }
    function getModel(){
		return $this->model;
	}
	function getTemplate(){
		return $this->template;
	}
	function __invoke($template=null){
        return call_user_func_array($this->template,func_get_args());
    }
}