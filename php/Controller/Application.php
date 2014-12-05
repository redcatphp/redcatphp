<?php namespace Surikat\Controller;
use Surikat\Config\Dev;
use Surikat\I18n\Lang;
use Surikat\Tool\HTTP;
use Surikat\Tool\ArrayObject;
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
		if($convention)
			$this->convention();
	}
	function convention(){
		$this->Dispatcher
			->append('/service/',['Service\\Service','method'])
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
			//404
		}
	}
	
	protected $prefixTmlCompile = '';
	function __invoke($params,$uri,$Router){
		$path = $params[0];
		$this->Router = $Router;
		//var_dump(func_get_args());
		if($this->i18nBySubdomain)
			$path = $this->i18nBySubdomain($path);
		$this->View->set('URI',$Router);
		$this->exec($path.'.tml',[],[
			'dirCompile'=>SURIKAT_TMP.'view/compile/'.$this->prefixTmlCompile,
			'dirCache'=>SURIKAT_TMP.'view/cache/'.$this->prefixTmlCompile,
		]);
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
	protected function hookTml($s){
		$path = ltrim($this->URI->getPath(),'/');
		$pathFS = func_num_args()>1?func_get_arg(1):$s;
		if(strpos($path,$s)===0){
			$path = substr($path,strlen($s)).'.tml';
			$this->exec($path,[],[
				'dirCwd'=>[
					SURIKAT_PATH.$pathFS,
					SURIKAT_SPATH.$pathFS,
				],
				'dirCompile'=>SURIKAT_TMP.'view/compile/.'.$pathFS,
				'dirCache'=>SURIKAT_TMP.'view/cache/.'.$pathFS,
			]);
			exit;
		}
	}
	function exec($file,$vars=[],$options=[]){
		try{
			$this->View->setPath($file);
			$this->View->setOptions($options);
			$this->View->display($vars);
		}
		catch(\Surikat\View\Exception $e){
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			$this->View->setPath($c.'.tml');
			$this->View->display();
		}
		catch(\Surikat\View\Exception $e){
			HTTP::code($e->getMessage());
		}
		exit;
	}
}