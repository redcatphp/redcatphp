<?php namespace Unit;
class RouteMvc {
	private $model;
	private $view;
    private $controller;
    private $di;
    function __construct($model, $view, $controller = null, DiContainer $di){
        $this->model = $model;
        $this->view = $view;
        $this->controller = $controller;
        $this->di = $di;
    }
    function __invoke($params){
		if(is_string($this->model)){
			$this->model = $this->di->create($this->model);
		}
		if(is_string($this->view)){
			$this->view = $this->di->create($this->view,[$this->model]);
		}
		if(is_string($this->controller)){
			$this->controller = $this->di->create($this->controller,[$this->model]);
		}
		if($this->controller){
			call_user_func($this->controller,$params);
		}
		call_user_func($this->view,$params);
	}
}