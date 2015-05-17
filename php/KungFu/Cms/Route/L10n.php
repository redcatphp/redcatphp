<?php namespace KungFu\Cms\Route;
use Unit\Url;
use Unit\Dispatcher;
class L10n extends Faceted {
	protected $lang;
	protected $langMap;
	protected $Url;
	function __construct(Dispatcher $Dispatcher=null,Url $Url=null){
		if(!$Url)
			$Url = new Url();
		$this->Url = $Url;
	}
	function __invoke($uri){
		parent::__invoke($uri);
		$path = urldecode($this->uriParams[0]);
		$templatePath = $path;
		$this->langMap = false;
		if($lang=$this->Url->getSubdomainLang()){
			if(file_exists($langFile='langs/'.$lang.'.ini')){
				$this->langMap = parse_ini_file($langFile);
				if(isset($this->langMap[$path]))
					$templatePath = $this->langMap[$path];
				elseif(($k=array_search($path,$this->langMap))!==false){
					header('Location: /'.$k,false,301);
					exit;
				}
			}
		}
		else{
			$config = ((object)include(is_file($f=SURIKAT_CWD.'config/langs.php')?$f:SURIKAT.'config/langs.php'));
			$lang = $config->default;
		}
		$this->setLang($lang);
		$this->uriParams[0] = $templatePath;
		return $this->uriParams;
	}
	function setLang($lang){
		$this->lang = $lang;
	}
	function getLang(){
		return $this->lang;
	}
	function getLangMap(){
		return $this->langMap;
	}
}