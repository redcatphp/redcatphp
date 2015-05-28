<?php namespace KungFu\Cms\RouteMatch;
class ByTmlL10n extends ByTml {
	private $langDefault;
	private $lang;
	function __construct($dir=null,$dirFS=null,$langDefault='en'){
		parent::__construct($dir,$dirFS);
		$this->langDefault = $langDefault;
	}
	function __invoke($path,$domain){
		$templatePath = $path;
		if($lang=$this->extractLang($domain)){
			if(file_exists($langFile='langs/'.$lang.'.ini')){
				$langMap = parse_ini_file($langFile);
				if(isset($langMap[$path]))
					$templatePath = $langMap[$path];
				elseif(($k=array_search($path,$langMap))!==false){
					header('Location: /'.$k,false,301);
					exit;
				}
			}
			return [$lang,$langMap,$uri];
		}
	}
	function extractLang($domain){
		$urlParts = explode('.', $domain);
		if(count($urlParts)>2&&strlen($urlParts[0])==2)
			return $urlParts[0];
		else
			return null;
	}
}