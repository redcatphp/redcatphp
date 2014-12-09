<?php namespace Surikat\Route;
class Dispatcher {
	protected $routes = [];
	function append($pattern, $callback){
		return $this->route($pattern, $callback);
	}
	function prepend($pattern, $callback){
		return $this->route($pattern, $callback, true);
	}
	function route($router, $callback, $prepend = false){
		if(!$router instanceof Route)
			$router = new Router($router);
		$router = [$router,$callback];
		if($prepend)
			array_unshift($this->routes,$router);
		else
			array_push($this->routes,$router);
		return $this;
	}
	function run($uri){
		foreach ($this->routes as $a){
			list($router,$callback) = $a;
			if($params = $router->match($uri)){
				call_user_func_array($callback,[
					$params,
					$uri,
					$router
				]);
				return true;
			}
		}
		return false;
	}
}