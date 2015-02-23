<?php namespace Surikat\Dispatcher;
use ReflectionClass;
use Surikat\DependencyInjection\MutatorMagic;
class Dispatcher {
	use MutatorMagic;
	protected $routes = [];
	static function runner($uri){
		$dispatcher = new static();
		return $dispatcher->run($uri);
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
				$route = ['new','Surikat\Route\Regex',$route];
			}
			else{
				$route = ['new','Surikat\Route\Prefix',$route];
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
				self::objectify($route);
				$params = call_user_func_array($route,[&$uri]);
				if($params===true){
					return true;
				}
				elseif($params!==null&&$params!==false){
					self::objectify($callback);
					while(is_callable($callback)){
						$callback =	call_user_func($callback,$params,$uri,$route);
					}
					return true;
				}
			}
		}
		return false;
	}
	private static $reflectionRegistry = [];
	private static function reflectionRegistry($c){
		if(!isset(self::$reflectionRegistry[$c]))
			self::$reflectionRegistry[$c] = new ReflectionClass($c);
		return self::$reflectionRegistry[$c];
	}
	private static function objectify(&$a){
		$c = null;
		if(is_array($a)&&isset($a[0])){
			if($a[0]=='new'){
				array_shift($a);
				$c = array_shift($a);
			}
			elseif(is_string($a[0])&&strpos($a[0],'new::')===0){
				$c = substr(array_shift($a),5);
			}
		}
		if($c){
			if(empty($a))
				$a = new $c();
			else
				$a = self::reflectionRegistry($c)->newInstanceArgs($a);
		}
	}
}