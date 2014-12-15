<?php namespace Surikat\Route;
use ArrayAccess;
use Surikat\Core\Domain;
use I18n\Lang;
use Surikat\View\Toolbox as View_Toolbox;
class Router_ByTml extends Router_SuperURI{
	protected $match;
	protected $dir = 'tml';
	protected $dirHook;
	function __construct($dir=null,$Controller=null){
		if(isset($dir)){
			$this->dir = $dir;
			$this->dirHook = trim($dir,'/');
		}
		$this->setController($Controller);
	}
	function match($url){
		if($this->dirHook&&strpos($url,'/'.$this->dirHook.'/')!==0)
			return;
		$params = parent::match($url);
		if($this->dirHook)
			$params[0] = substr($params[0],strlen($this->dirHook));
		if($this->i18nBySubdomain)
			$params[0] = $this->i18nBySubdomain($params[0]);
		$file = $this->dir.'/'.$params[0].'.tml';
		if(	is_file(SURIKAT_PATH.$file)
			||is_file(SURIKAT_SPATH.$file))
			return $params;
	}
	function getDirHook(){
		return $this->dirHook;
	}
	
	protected $i18nBySubdomain = false;
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
		$this->Controller->addPrefixTmlCompile('.'.$lang.'/');
		
		Lang::set($lang);
		$view = $this->Controller->getView();
		
		$view->onCompile(function($TML)use($lang,$path,$langMap){
			View_Toolbox::i18nGettext($TML);
			View_Toolbox::i18nRel($TML,$lang,$path,$langMap);
		});
		
		return $templatePath;
	}
}