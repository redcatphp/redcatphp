<?php namespace Surikat\HTTP;
class Domain {
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
			if(isset($_SERVER['CWD'])){
				$this->suffixHref = ltrim($_SERVER['CWD'],'/');				
			}
			else{
				$docRoot = $_SERVER['DOCUMENT_ROOT'].'/';
				//$docRoot = dirname($_SERVER['SCRIPT_FILENAME']).'/';
				if($docRoot!=SURIKAT_PATH&&strpos(SURIKAT_PATH,$docRoot)===0)
					$this->suffixHref = substr(SURIKAT_PATH,strlen($docRoot));
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
	function getLocation(){
		return $this->getBaseHref().ltrim($_SERVER['REQUEST_URI'],'/');
	}
}