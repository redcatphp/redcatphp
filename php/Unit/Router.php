<?php namespace Unit;
class Router {
	private $routes = [];
	private $route;
	private $routeParams;
	private $di;
	function __construct(DiContainer $di){
		$this->di = $di;
	}
	function map($map,$index=0,$prepend=false){
		foreach($map as list($match,$route)){
			$this->route($match,$route,$index,$prepend);
		}
		return $this;
	}
	function append($match,$route,$index=0){
		return $this->route($match,$route,$index);
	}
	function prepend($match,$route,$index=0){
		return $this->route($match,$route,$index,true);
	}
	function find($uri,$server=null){
		$uri = ltrim($uri,'/');
		ksort($this->routes);
		foreach($this->routes as $group){
			foreach($group as list($match,$route)){
				$routeParams = call_user_func($this->di->objectify($match),$uri,$server);
				if($routeParams!==null){
					$this->route = $route;
					$this->routeParams = $routeParams;
					return true;
				}
			}
		}
	}
	function display(){
		$route = $this->route;
		while(is_callable($route=$this->di->objectify($route))){
			$route = call_user_func($route,$this->routeParams);
		}
	}
	private function route($match,$route,$index=0,$prepend=false){
		$pair = [$this->matchType($match),$route];
		if(!isset($this->routes[$index]))
			$this->routes[$index] = [];
		if($prepend)
			array_unshift($this->routes[$index],$pair);
		else
			$this->routes[$index][] = $pair;
		return $this;
	}
	private function matchType($match){
		if(is_string($match)){
			if(strpos($match,'/^')===0&&strrpos($match,'$/')-strlen($match)===-2){
				return ['new:Unit\RouteMatch\Regex',$match];
			}
			else{
				return ['new:Unit\RouteMatch\Prefix',$match];
			}
		}
		return $match;
	}
}