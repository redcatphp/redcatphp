<?php namespace Unit;
use Unit\RouteMatch\Regex;
use Unit\RouteMatch\Prefix;
class Router {
	private $routes = [];	
	private $view;
    private $controller;
    function __construct(View $view, $controller = null){
        $this->view = $view;
        $this->controller = $controller;
    }
    function getView(){
        return $this->view;
    }
    function getController(){
        return $this->controller;
    }
    
	function append($pattern,$callback,$index=0){
		return $this->route($pattern,$callback,$index);
	}
	function prepend($pattern,$callback,$index=0){
		return $this->route($pattern,$callback,$index,true);
	}
	function route($route,$callback,$index=0,$prepend=false){
		if(is_string($route)){
			if(strpos($route,'/^')===0&&strrpos($route,'$/')-strlen($route)===-2){
				$route = new Regex($route);
			}
			else{
				$route = new Prefix($route);
			}
		}
		$route = [$route,$callback];
		if(!isset($this->routes[$index]))
			$this->routes[$index] = [];
		if($prepend)
			array_unshift($this->routes[$index],$route);
		else
			array_push($this->routes[$index],$route);
		return $this;
	}
	function __invoke(){
		return $this->run(func_get_arg(0));
	}
	function run($uri){
		$uri = ltrim($uri,'/');
		ksort($this->routes);
		foreach($this->routes as $group){
			foreach($group as $router){
				list($route,$callback) = $router;
				$params = call_user_func($route,$uri);
				if($params===true){
					return true;
				}
				elseif($params!==null&&$params!==false){
					while(is_callable($callback)){
						$callback =	call_user_func($callback,$params,$uri,$route);
					}
					return true;
				}
			}
		}
		return false;
	}
	function runFromGlobals(){
		if(isset($_SERVER['SURIKAT_CWD'])){
			$s = strlen($_SERVER['SURIKAT_CWD'])-1;
			$p = strpos($_SERVER['REQUEST_URI'],'?');
			if($p===false)
				$path = substr($_SERVER['REQUEST_URI'],$s);
			else
				$path = substr($_SERVER['REQUEST_URI'],$s,$p-$s);
			$path = urldecode($path);
			$this->run($path);
		}
		else{
			$path = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'';
		}
	}
}