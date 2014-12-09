<?php namespace Surikat\Controller;
use Surikat\Core\Dev;
use Surikat\I18n\Lang;
use Surikat\Core\HTTP;
use Surikat\Core\ArrayObject;
use Surikat\View\TeMpLate;
use Surikat\View\TML;
use Surikat\View\Toolbox as ViewToolbox;
use Surikat\Route\Dispatcher;
use Surikat\Route\Router_ByTml;
use Surikat\Route\Domain;

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
			->prepend(new Router_ByTml('plugin'),$this)
			->prepend('/service/',['Service\\Service','method'])
			->append(new Router_ByTml(),$this)
		;
		$this->View->onCompile(function($TML){
			ViewToolbox::registerPresenter($TML);
			ViewToolbox::xDom($TML);
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
		if($this->i18nBySubdomain)
			$path = $this->i18nBySubdomain($path);
		if(method_exists($Router,'getDirHook')){
			$hook = $Router->getDirHook();
			$this->View->setDirCompile(SURIKAT_TMP.$hook.'/compile/');
			$this->View->setDirCache(SURIKAT_TMP.$hook.'/cache/');
			$this->View->setDirCwd([
				SURIKAT_PATH.$hook.'/',
				SURIKAT_SPATH.$hook.'/',
			]);
		}
		$this->View->set('URI',$Router);
		$this->display($path.'.tml');
	}
	protected $i18nBySubdomain = false;
	protected function i18nBySubdomain($path){
		if($lang=Domain::getSubdomainLang()){
			if(file_exists($langFile='langs/'.$lang.'.ini')){
				$langMap = parse_ini_file($langFile);
				if(isset($langMap[$path]))
					$templatePath = $langMap[$path];
				elseif(($k=array_search($path,$langMap))!==false){
					header('Location: /'.$k,301);
					exit;
				}
			}
		}
		else
			$lang = 'en';
		$this->prefixTmlCompile = '.'.$lang.'/';
		Lang::setLocale($lang);
		return $path;
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