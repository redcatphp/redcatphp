<?php
namespace Unit;
class View {
	protected $model;
	protected $template;
	protected $di;
    function __construct($model = null, $template = null, Di $di){
        $this->model = $model;
        $this->template = $template;
        $this->di = $di;
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