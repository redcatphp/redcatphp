<?php namespace Surikat\Core;
abstract class Domain {
	private static $baseHref;
	private static $baseHrefSuffix;
	static function setBaseHref($href){
		self::$baseHref = $href;
	}
	static function getBaseHref(){
		if(!isset(self::$baseHref)){
			$ssl = @$_SERVER["HTTPS"]=="on";
			$port = @$_SERVER['SERVER_PORT']&&((!$ssl&&(int)$_SERVER['SERVER_PORT']!=80)||($ssl&&(int)$_SERVER['SERVER_PORT']!=443))?':'.$_SERVER['SERVER_PORT']:'';
			$href = 'http'.($ssl?'s':'').'://'.$_SERVER['SERVER_NAME'].$port.'/';
			self::setBaseHref($href);
		}
		return self::$baseHref.self::getBaseHrefSuffix();
	}
	static function setBaseHrefSuffix($href){
		self::$baseHrefSuffix = $href;
	}
	static function getBaseHrefSuffix(){
		if(!isset(self::$baseHrefSuffix)){
			if($_SERVER['DOCUMENT_ROOT'].'/'!=SURIKAT_PATH)
				self::$baseHrefSuffix = substr(SURIKAT_PATH,strlen($_SERVER['DOCUMENT_ROOT'])+1);
		}
		return self::$baseHrefSuffix;
	}
	static function getSubdomainLang($domain=null){
		if(!isset($domain))
			$domain = $_SERVER['HTTP_HOST'];
		$urlParts = explode('.', $domain);
		if(count($urlParts)>2&&strlen($urlParts[0])==2)
			return $urlParts[0];
		else
			return null;
	}
}