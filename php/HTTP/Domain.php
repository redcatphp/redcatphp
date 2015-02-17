<?php namespace Surikat\HTTP;
abstract class Domain {
	protected static $baseHref;
	protected static $suffixHref;
	static function setBaseHref($href){
		static::$baseHref = $href;
	}
	static function getProtocolHref(){
		return 'http'.(@$_SERVER["HTTPS"]=="on"?'s':'').'://';
	}
	static function getServerHref(){
		return $_SERVER['SERVER_NAME'];
	}
	static function getPortHref(){
		$ssl = @$_SERVER["HTTPS"]=="on";
		return @$_SERVER['SERVER_PORT']&&((!$ssl&&(int)$_SERVER['SERVER_PORT']!=80)||($ssl&&(int)$_SERVER['SERVER_PORT']!=443))?':'.$_SERVER['SERVER_PORT']:'';
	}
	static function getBaseHref(){
		if(!isset(static::$baseHref)){
			static::setBaseHref(static::getProtocolHref().static::getServerHref().static::getPortHref().'/');
		}
		return static::$baseHref.static::getSuffixHref();
	}
	static function setSuffixHref($href){
		static::$suffixHref = $href;
	}
	static function getSuffixHref(){
		if(!isset(static::$suffixHref)){
			if(isset($_SERVER['CWD'])){
				static::$suffixHref = ltrim($_SERVER['CWD'],'/');				
			}
			else{
				$docRoot = $_SERVER['DOCUMENT_ROOT'].'/';
				//$docRoot = dirname($_SERVER['SCRIPT_FILENAME']).'/';
				if($docRoot!=SURIKAT_PATH&&strpos(SURIKAT_PATH,$docRoot)===0)
					static::$suffixHref = substr(SURIKAT_PATH,strlen($docRoot));
			}
		}
		return static::$suffixHref;
	}
	static function getSubdomainHref($sub=''){
		$lg = static::getSubdomainLang();
		$server = static::getServerHref();
		if($lg)
			$server = substr($server,strlen($lg)+1);
		if($sub)
			$sub .= '.';
		return static::getProtocolHref().$sub.$server.static::getPortHref().'/'.static::getSuffixHref();
	}
	static function getSubdomainLang($domain=null){
		if(!isset($domain))
			$domain = static::getServerHref();
		$urlParts = explode('.', $domain);
		if(count($urlParts)>2&&strlen($urlParts[0])==2)
			return $urlParts[0];
		else
			return null;
	}
	static function getLocation(){
		return self::getBaseHref().ltrim($_SERVER['REQUEST_URI'],'/');
	}
}