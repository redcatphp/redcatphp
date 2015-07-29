<?php namespace Unit;
class RouteMvc {
	private $model;
	private $view;
    private $controller;
    private $template;
    private $di;
    function __construct($model='Unit\Model', $view='Unit\View', $controller = null, $template = null, Di $di){
        $this->model = $model;
        $this->view = $view;
        $this->controller = $controller;
        $this->template = $template;
        $this->di = $di;
		if(is_string($this->model)){
			$this->model = $this->di->create($this->model);
		}
		if(is_string($this->template)){
			$this->template = $this->di->create($this->template);
		}
		if(is_string($this->view)){
			$this->view = $this->di->create($this->view,[$this->model,$this->template]);
		}
		if(is_string($this->controller)){
			$this->controller = $this->di->create($this->controller,[$this->model]);
		}
    }
    function __invoke(){
		if($this->controller&&is_callable($this->controller)){
			call_user_func_array($this->controller,func_get_args());
		}
		if($this->model&&is_callable($this->model)){
			call_user_func_array($this->model,func_get_args());
		}
		return call_user_func_array($this->view,func_get_args());
	}
	function __get($k){
		return $this->model->$k;
	}
	function __set($k, $v){
		$this->model->$k = $v;
	}
	function __isset($k){
		return isset($this->model->$k);
	}
	function __unset($k){
		unset($this->model->$k);
	}
	function offsetGet($k){
		return $this->model[$k];
	}
	function offsetSet($k, $v){
		$this->model[$k] = $v;
	}
	function offsetExists($k){
		return isset($this->model[$k]);
	}
	function offsetUnset($k){
		unset($this->model[$k]);
	}
}