<?php
/*
 * Router - A mirco-framework for manage entry point of applications
 *
 * @package Router
 * @version 1.2
 * @link http://github.com/surikat/Router/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
namespace Wild\Route;
use Wild\Wire\Di;
class Router implements \ArrayAccess{
	private $routes = [];
	private $route;
	private $routeParams;
	private $di;
	private $index = 0;
	function __construct(Di $di = null){
		$this->di = $di;
	}
	function map($map,$index=null,$prepend=false){
		foreach($map as list($match,$route)){
			$this->route($match,$route,$index,$prepend);
		}
		return $this;
	}
	function append($match,$route,$index=null){
		return $this->route($match,$route,$index);
	}
	function prepend($match,$route,$index=null){
		return $this->route($match,$route,$index,true);
	}
	function find($uri,$server=null){
		$uri = ltrim($uri,'/');
		ksort($this->routes);
		foreach($this->routes as $group){
			foreach($group as list($match,$route)){
				$routeParams = call_user_func($this->objectify($match),$uri,$server);
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
		while(is_callable($route=$this->objectify($route))){
			$route = call_user_func($route,$this->routeParams);
		}
	}
	function route($match,$route,$index=null,$prepend=false,$subindex=null){
		if(is_null($index))
			$index = $this->index;
		$pair = [$this->matchType($match),$route];
		if(!isset($this->routes[$index]))
			$this->routes[$index] = [];
		if(!is_null($subindex))
			$this->routes[$index][$subindex] = $pair;
		elseif($prepend)
			array_unshift($this->routes[$index],$pair);
		else
			$this->routes[$index][] = $pair;
		return $this;
	}
	private function matchType($match){
		if(is_string($match)){
			if(strpos($match,'/^')===0&&strrpos($match,'$/')-strlen($match)===-2){
				return ['new:Wild\Route\Match\Regex',$match];
			}
			else{
				return ['new:Wild\Route\Match\Prefix',$match];
			}
		}
		return $match;
	}
	function setIndex($index=0){
		$this->index = $index;
	}
	function objectify($a){
		if($this->di)
			return $this->di->objectify($a);
		if(is_object($a))
			return $a;
		if(is_array($a)){
			if(is_array($a[0])){
				$a[0] = $this->objectify($a[0]);
				return $a;
			}
			else{
				$args = $a;
				$s = array_shift($args);
			}
		}
		else{
			$args = [];
			$s = $a;
		}
		if(is_string($s)&&strpos($s,'new:')===0)
			$a = (new \ReflectionClass(substr($s,4)))->newInstanceArgs($args);
		return $a;
	}
	function offsetSet($k,$v){
		list($match,$route) = $v;
		$this->route($match,$route,$this->index,false,$k);
	}
	function offsetGet($k){
		if(!isset($this->routes[$this->index][$k]))
			$this->routes[$this->index][$k] = [];
		return $this->routes[$this->index][$k];
	}
	function offsetExists($k){
		return isset($this->routes[$this->index][$k]);
	}
	function offsetUnset($k){
		if(isset($this->routes[$this->index][$k]))
			unset($this->routes[$this->index][$k]);
	}
}