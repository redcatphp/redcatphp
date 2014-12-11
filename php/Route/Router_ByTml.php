<?php namespace Surikat\Route;
use ArrayAccess;
use Surikat\Core\Domain;
use Surikat\I18n\Lang;
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
		$this->prefixTmlCompile .= '.'.$lang.'/';
		Lang::set($lang);
		$this->Controller->getView()->onCompile('\\Surikat\\View\\Toolbox::Internationalization');
		return $templatePath;
	}
}