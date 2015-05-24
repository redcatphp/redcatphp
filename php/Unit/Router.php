<?php namespace Unit;
class Router {
	private $routes = [];
	private $route;
	private $routeParams;
	private $di;
	function __construct(DiContainer $di){
		$this->di = $di;
	}
	function append($pattern,$callback,$index=0){
		return $this->route($pattern,$callback,$index);
	}
	function prepend($pattern,$callback,$index=0){
		return $this->route($pattern,$callback,$index,true);
	}
	function find($uri){
		$uri = ltrim($uri,'/');
		ksort($this->routes);
		foreach($this->routes as $group){
			foreach($group as list($match,$route)){
				$routeParams = call_user_func($this->di->objectify($match),$uri);
				if($routeParams!==null){
					$this->route = $route;
					$this->routeParams = $routeParams;
					return true;
				}
			}
		}
	}
	function display(){
		$callback = $this->route;
		while(is_callable($callback=$this->di->objectify($callback))){
			$callback =	call_user_func($callback,$this->routeParams);
		}
	}
	function run($uri){
		if($this->find($uri)){
			$this->display();
			return true;
		}
	}
	function runFromGlobals(){
		if(isset($_SERVER['SURIKAT_URI'])){
			$s = strlen($_SERVER['SURIKAT_URI'])-1;
			$p = strpos($_SERVER['REQUEST_URI'],'?');
			if($p===false)
				$path = substr($_SERVER['REQUEST_URI'],$s);
			else
				$path = substr($_SERVER['REQUEST_URI'],$s,$p-$s);
			$path = urldecode($path);
		}
		else{
			$path = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'';
		}
		$this->run($path);
	}
	function __invoke(){
		return $this->run(func_get_arg(0));
	}
	private function route($route,$callback,$index=0,$prepend=false){
		$pair = [$this->routeType($route),$callback];
		if(!isset($this->routes[$index]))
			$this->routes[$index] = [];
		if($prepend)
			array_unshift($this->routes[$index],$pair);
		else
			$this->routes[$index][] = $pair;
		return $this;
	}
	private function routeType($route){
		if(is_string($route)){
			if(strpos($route,'/^')===0&&strrpos($route,'$/')-strlen($route)===-2){
				return ['new:Unit\RouteMatch\Regex',$route];
			}
			else{
				return ['new:Unit\RouteMatch\Prefix',$route];
			}
		}
		return $route;
	}
}