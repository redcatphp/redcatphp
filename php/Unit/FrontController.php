<?php
namespace Unit;
class FrontController {
	private $router;
	protected $di;
	function __construct(Router $router,Di $di){
		$this->router = $router;
		$this->di = $di;
	}
	function map($map,$index=0,$prepend=false){
		return $this->router->map($map,$index,$prepend);
	}
	function append($match,$route,$index=0){
		return $this->router->append($match,$route,$index);
	}
	function prepend($match,$route,$index=0){
		return $this->router->prepend($match,$route,$index);
	}
	function run($uri,$domain=null){
		if($this->router->find($uri,$domain)){
			$this->router->display();
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
		return $this->run($path,$_SERVER['SERVER_NAME']);
	}
	function __invoke($uri,$domain=null){
		return $this->run($uri,$domain);
	}
}