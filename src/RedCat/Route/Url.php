<?php
namespace RedCat\Route;
class Url {
	protected $baseHref;
	protected $suffixHref;
	protected $server;
	function __construct($server=null){
		if(!$server)
			$server = &$_SERVER;
		$this->server = $server;
	}
	function setBaseHref($href){
		$this->baseHref = $href;
	}
	function getServerHttps(){
		return isset($this->server['HTTPS'])?$this->server['HTTPS']:null;
	}
	function getServerPort(){
		return isset($this->server['SERVER_PORT'])?$this->server['SERVER_PORT']:null;
	}
	function getProtocolHref(){
		return 'http'.($this->getServerHttps()=="on"?'s':'').'://';
	}
	function getServerHref(){
		return $this->server['SERVER_NAME'];
	}
	function getPortHref(){
		$ssl = $this->getServerHttps()=="on";
		return $this->getServerPort()&&((!$ssl&&(int)$this->getServerPort()!=80)||($ssl&&(int)$this->getServerPort()!=443))?':'.$this->getServerPort():'';
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
			if(isset($this->server['REDCAT_URI'])){
				$this->suffixHref = ltrim($this->server['REDCAT_URI'],'/');				
			}
			else{
				$docRoot = $this->server['DOCUMENT_ROOT'].'/';
				//$docRoot = dirname($this->server['SCRIPT_FILENAME']).'/';
				if(defined('REDCAT_PUBLIC'))
					$cwd = REDCAT_PUBLIC;
				else
					$cwd = getcwd();
				if($docRoot!=$cwd&&strpos($cwd,$docRoot)===0)
					$this->suffixHref = substr($cwd,strlen($docRoot));
			}
		}
		return $this->suffixHref;
	}
	function getUriPath(){
		return parse_url(ltrim($this->server['REQUEST_URI'],'/'), \PHP_URL_PATH);
	}
	function getUri(){
		return ltrim($this->server['REQUEST_URI'],'/');
	}
	function getLocation(){
		return $this->getBaseHref().$this->getUri();
	}
	function getDomain(){
		$x = explode('.',$this->getServerHref());
		$e = array_pop($x);
		return end($x).'.'.$e;
	}
	function getCanonical($domains=null,$httpSubstitution=false,$isStatic=false){
		if(empty($domains))
			$domains = $this->getDomain();
		if(!is_array($domains))
			$domains = (array)$domains;
		reset($domains);
		$mainDomain = current($domains);
		$serverHref = $this->getServerHref();
		$canonical = $this->getProtocolHref();
		if(in_array($serverHref,$domains))
			$canonical .= $serverHref;
		else
			$canonical .= $mainDomain;
		$canonical .= $this->getPortHref().'/'.$this->getSuffixHref();
		if(is_string($httpSubstitution)){
			$canonical .= $httpSubstitution;
		}
		elseif($httpSubstitution&&http_response_code()!==200){
			$canonical .= http_response_code();
		}
		else{
			if($isStatic)
				$uri = $this->getUriPath();
			else
				$uri = $this->getUri();
			$uri = substr($uri,strlen($this->getSuffixHref()));
			$canonical .= $uri;
		}
		return $canonical;
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