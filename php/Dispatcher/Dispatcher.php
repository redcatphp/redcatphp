<?php namespace Surikat\Dispatcher;
use Surikat\Route\Prefix;
use Surikat\Route\Regex;
use Surikat\Route\ByTml;
class Dispatcher {
	protected $routes = [];
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
	function run($uri){
		$uri = ltrim($uri,'/');
		ksort($this->routes);
		foreach($this->routes as $group){
			foreach($group as $router){
				list($route,$callback) = $router;
				if(null !== $params = call_user_func_array($route,[&$uri])){
					while(is_callable($callback)){
						$callback =	call_user_func($callback,$params,$uri,$route);
					}
					return true;
				}
			}
		}
		return false;
	}
}