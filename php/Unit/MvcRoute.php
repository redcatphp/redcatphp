<?php
namespace Unit;
class MvcRoute implements \ArrayAccess,\Iterator,\Countable{
	private $model;
	private $view;
    private $controller;
    private $templateEngine;
    private $di;
    function __construct($model='Unit\Model', $view='Unit\View', $controller = null, $templateEngine = null, Di $di = null){
        $this->model = $model;
        $this->view = $view;
        $this->controller = $controller;
        $this->templateEngine = $templateEngine;
        $this->di = $di;
		if(is_string($this->model)){
			if($this->di)
				$this->model = $this->di->create($this->model);
			else
				$this->model = new $this->model;
		}
		if(is_string($this->template)){
			if($this->di)
				$this->template = $this->di->create($this->template);
			else
				$this->template = new $this->template;
		}
		if(is_string($this->view)){
			if($this->di)
				$this->view = $this->di->create($this->view,[$this->model,$this->template]);
			else
				$this->view = new $this->view($this->model,$this->template);
		}
		if(is_string($this->controller)){
			if($this->di)
				$this->controller = $this->di->create($this->controller,[$this->model]);
			else
				$this->controller = new $this->controller($this->model);
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
	function rewind(){
		return reset($this->model);
	}
	function current(){
		return current($this->model);
	}
	function key(){
		return key($this->model);
	}
	function next(){
		return next($this->model);
	}
	function valid(){
		return key($this->model);
	}
	function count(){
		return count($this->model);
	}
}