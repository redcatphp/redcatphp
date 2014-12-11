<?php namespace Surikat\Controller;
use Surikat\Core\Dev;
use Surikat\Core\HTTP;
use Surikat\Core\ArrayObject;
use Surikat\View\TeMpLate;
use Surikat\View\TML;
use Surikat\View\Toolbox as ViewToolbox;
use Surikat\Route\Dispatcher;
use Surikat\Route\Router_ByTml;

class Application{
	protected $Dispatcher;
	protected $View;
	protected $Router;
	
	function __construct($convention=true){
		$this->Dispatcher = new Dispatcher();
		$this->View = new TeMpLate();
		$this->View->setController($this);
		if($convention)
			$this->convention();
	}
	function convention(){
		$this->Dispatcher
			->prepend(new Router_ByTml('plugin',$this),$this)
			->prepend('/service/',['Service\\Service','method'])
			->append(new \Route\Router_ByTml(null,$this),$this)
		;
		$this->View->onCompile(function($TML){
			ViewToolbox::registerPresenter($TML);
			ViewToolbox::JsIs($TML);
			if(!Dev::has(Dev::VIEW))
				ViewToolbox::autoMIN($TML);
		});
	}
	function run($path){
		if(! $this->Dispatcher->run($path) ){
			$this->error(404);
		}
	}
	
	function setDispatcher($Dispatcher){
		$this->Dispatcher = $Dispatcher;
	}
	function getDispatcher(){
		return $this->Dispatcher;
	}
	function setView($View){
		$this->View = $View;
	}
	function getView(){
		return $this->View;
	}
	function setRouter($Router){
		$this->Router = $Router;
	}
	function getRouter(){
		return $this->Router;
	}
	
	protected $prefixTmlCompile = '';
	function __invoke($params,$uri,$Router){
		$path = is_string($params)?$params:$params[0];
		$this->Router = $Router;
		if(method_exists($Router,'getDirHook')
			&&$hook = $Router->getDirHook()){
			$this->prefixTmlCompile .= '.'.$hook.'/';
			$this->View->setDirCwd([
				SURIKAT_PATH.$hook.'/',
				SURIKAT_SPATH.$hook.'/',
			]);
		}
		$this->View->set('URI',$Router);
		$this->display($path.'.tml');
	}
	function display($file){
		$this->View->setDirCompile(SURIKAT_TMP.'tml/compile/'.$this->prefixTmlCompile);
		$this->View->setDirCache(SURIKAT_TMP.'tml/cache/'.$this->prefixTmlCompile);
		try{
			$this->View->display($file);
		}
		catch(\Surikat\View\Exception $e){
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			$this->View->display($c.'.tml');
		}
		catch(\Surikat\View\Exception $e){
			HTTP::code($e->getMessage());
		}
		exit;
	}
}