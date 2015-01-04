<?php namespace Surikat\Route;
use ArrayAccess;
use Surikat\Core\Config;
use Surikat\Core\Domain;
use I18n\Lang;
use Surikat\View\Toolbox as View_Toolbox;
class I18n extends Faceted {
	protected $Dispatcher;
	function __construct($Dispatcher){
		$this->Dispatcher = $Dispatcher;
	}
	function __invoke(&$uri){
		parent::__invoke($uri);
		$this->uriParams[0] = $this->i18nBySubdomain($this->uriParams[0]);
		$uri = $this->buildPath();
	}
	protected function i18nBySubdomain($path){
		$templatePath = $path;
		$langMap = false;
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
			$lang = Config::langs('default');
		Lang::set($lang);
		$ctrl = $this->Dispatcher->getController();
		$ctrl->addPrefixTmlCompile('.'.$lang.'/');		
		$ctrl->getView()->onCompile(function($TML)use($lang,$path,$langMap){
			View_Toolbox::i18nGettext($TML);
			View_Toolbox::i18nRel($TML,$lang,$path,$langMap);
		});
		return $templatePath;
	}
}