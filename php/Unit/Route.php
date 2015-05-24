<?php namespace Unit;
class Route {
	private $view;
    private $controller;
    private $di;
    function __construct($view, $controller = null, DiContainer $di){
        $this->view = $view;
        $this->controller = $controller;
        $this->di = $di;
    }
    function __invoke($params){
		if(is_string($this->view)){
			$this->view = $this->di->create($this->view);
		}
		if(is_string($this->controller)){
			$this->controller = $this->di->create($this->controller);
		}
		if($this->controller){
			call_user_func($this->controller,$params);
		}
		call_user_func($this->view,$params);
	}
}