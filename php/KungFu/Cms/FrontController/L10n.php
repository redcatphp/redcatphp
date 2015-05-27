<?php namespace KungFu\Cms\FrontController;
use Unit\Router;
use Unit\RouteMatch\Extension;
use KungFu\Cms\RouteMatch\ByTml;
use KungFu\Cms\Controller\L10n as Controller_L10n;
use KungFu\Cms\Controller\Templix;
class L10n extends Router{
	protected $Controller;
	function __construct($config=[]){
		$this->append(new ByTml('plugin'),$this);
		$this->append(new ByTml(),$this);
		$this->Controller = new Controller_L10n();
	}
	function __invoke(){
		return $this->Controller;
	}
}