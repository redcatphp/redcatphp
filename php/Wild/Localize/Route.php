<?php
namespace Wild\Localize;
class Route{
	function byAcceptLanguage($_map=[],$default='en',$url=null,$http_accept_language = ''){
		if(!isset($url))
			$url = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'';
		if(isset($_COOKIE['language'])||trim($url,'/'))
			return;
		$map = [];
		foreach($_map as $k=>$v){
			if(is_integer($k))
				$map[$v] = $this->getSubdomainHref($v);
			else
				$map[$k] = $v;
		}
		if($default){
			if(is_array($default)){
				$defaultUrl = $default[1];
				$default = $default[0];
				$map[$default] = $defaultUrl;
			}
			else{
				$map[$default] = $this->getSubdomainHref();
			}
		}
		$language = $default;
		$redirect = AcceptLanguage::detect(function($lang)use(&$map,&$language){
			$k = implode('_',$lang);
			if(isset($map[$k])){
				$language = $k;
				return $map[$k];
			}
		},$default,$http_accept_language);
		$current = $this->getBaseHref();
		if($current!=$redirect){
			setcookie('language',$language);
			header('Location: '.$redirect,false,302);
			exit;
		}
	}
	
	protected $baseHref;
	protected $suffixHref;
	function setBaseHref($href){
		$this->baseHref = $href;
	}
	function getProtocolHref(){
		return 'http'.(@$_SERVER["HTTPS"]=="on"?'s':'').'://';
	}
	function getServerHref(){
		return $_SERVER['SERVER_NAME'];
	}
	function getPortHref(){
		$ssl = @$_SERVER["HTTPS"]=="on";
		return @$_SERVER['SERVER_PORT']&&((!$ssl&&(int)$_SERVER['SERVER_PORT']!=80)||($ssl&&(int)$_SERVER['SERVER_PORT']!=443))?':'.$_SERVER['SERVER_PORT']:'';
	}
	function getBaseHref(){
		if(!isset($this->baseHref)){
			$this->setBaseHref($this->getProtocolHref().$this->getServerHref().$this->getPortHref().'/');
		}
		return $this->baseHref.$this->getSuffixHref();
	}
	function setSuffixHref($href){
		$this->suffixHref = $href;
	}
	function getSuffixHref(){
		if(!isset($this->suffixHref)){
			if(isset($_SERVER['SURIKAT_URI'])){
				$this->suffixHref = ltrim($_SERVER['SURIKAT_URI'],'/');				
			}
			else{
				$docRoot = $_SERVER['DOCUMENT_ROOT'].'/';
				//$docRoot = dirname($_SERVER['SCRIPT_FILENAME']).'/';
				if(defined('SURIKAT_CWD'))
					$cwd = SURIKAT_CWD;
				else
					$cwd = getcwd();
				if($docRoot!=$cwd&&strpos($cwd,$docRoot)===0)
					$this->suffixHref = substr($cwd,strlen($docRoot));
			}
		}
		return $this->suffixHref;
	}
	function getSubdomainHref($sub=''){
		$lg = $this->getSubdomainLang();
		$server = $this->getServerHref();
		if($lg)
			$server = substr($server,strlen($lg)+1);
		if($sub)
			$sub .= '.';
		return $this->getProtocolHref().$sub.$server.$this->getPortHref().'/'.$this->getSuffixHref();
	}
	function getSubdomainLang($domain=null){
		if(!isset($domain))
			$domain = $this->getServerHref();
		$urlParts = explode('.', $domain);
		if(count($urlParts)>2&&strlen($urlParts[0])==2)
			return $urlParts[0];
		else
			return null;
	}
}