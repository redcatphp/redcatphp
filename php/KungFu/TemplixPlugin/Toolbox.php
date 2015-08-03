<?php
namespace KungFu\TemplixPlugin;
class Toolbox{	
	protected $baseHref;
	protected $suffixHref;
	protected $server;
	function __construct($server=null){
		if(!$server)
			$server = &$_SERVER;
		$this->server = $server;
	}
	
	function is($Tml,$href='css/is.'){
		$head = $Tml->find('head',0);
		if(!$head){
			if($Tml->find('body',0)){
				$head = $Tml;
				$head->append('<script type="text/javascript" src="/js/js.js"></script>');
				$head->append('<script type="text/javascript">$js().dev=true;$js().min=false;$css().min=false;</script>');
			}
			else{
				return;
			}
		}
		$s = [];
		$Tml->recursive(function($el)use($Tml,$head,$href,&$s){
			$is = $el->attr('is')?$el->attr('is'):(preg_match('/(?:[a-z][a-z]+)-(?:[a-z][a-z]+)/is',$el->nodeName)?$el->nodeName:false);
			if($is&&!in_array($is,$s)&&!$head->children('link[href="'.$href.strtolower($is).'.css"]',0)){
				if(	is_file(SURIKAT_CWD.$href.strtolower($is).'.css')
					||is_file(SURIKAT_CWD.$href.strtolower($is).'.scss')
					||is_file(SURIKAT.$href.strtolower($is).'.css')
					||is_file(SURIKAT.$href.strtolower($is).'.scss')
				){
					$s[] = $is;
				}
			}
		});
		foreach($s as $is)
			$head->append('<link href="'.$href.strtolower($is).'.css" rel="stylesheet" type="text/css">');
	}
	function autoMIN($Tml){
		if(!$Tml->templix&&$Tml->templix->devCss){
			foreach($Tml('link[href][rel=stylesheet],link[href][type="text/css"]') as $l)
				if(strpos($l,'://')===false)
					$l->href = (strpos($l->href,'/')!==false?dirname($l->href).'/':'').pathinfo($l->href,PATHINFO_FILENAME).'.min.'.pathinfo($l->href,PATHINFO_EXTENSION);
		}
		if(!$Tml->templix&&$Tml->templix->devJs){
			foreach($Tml('script[src]') as $s)
				if(strpos($s->src,'://')===false&&substr($s->src,-8)!='.pack.js')
					$s->src = (strpos($s->src,'/')!==false?dirname($s->src).'/':'').pathinfo($s->src,PATHINFO_FILENAME).'.min.'.pathinfo($l->src,PATHINFO_EXTENSION);
		}
	}
	//static function getHttpResponseCode($theURL) {
		//$headers = get_headers($theURL);
		//return (int)substr($headers[0], 9, 3);
	//}
	function setCDN($Tml,$url=true){
		if($url===true){
			$prefix = 'cdn';
			$url = 'http'.(@$_SERVER["HTTPS"]=="on"?'s':'').'://'.(strpos($_SERVER['SERVER_NAME'],$prefix.'.')===0?'':$prefix.'.').$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']&&(int)$_SERVER['SERVER_PORT']!=80?':'.$_SERVER['SERVER_PORT']:'').'/';
		}
		if(substr($url,-1)!='/')
			$url .= '/';
		$Tml('script[src],img[src],link[href]')->each(function($el)use($url,$Tml){
			if(
				($el->nodeName=='link'&&$el->type=='text/css'&&$Tml->templix&&$Tml->templix->devCss)
				|| ($el->nodeName=='link'&&$el->type=='image/x-icon'&&$Tml->templix&&$Tml->templix->devImg)
				|| ($el->nodeName=='img'&&$Tml->templix&&$Tml->templix->devImg)
				|| ($el->nodeName=='script'&&$Tml->templix&&$Tml->templix->devJs)
			)
				return;
			$k = $el->src?'src':'href';
			if($el->$k&&strpos('://',$el->$k)===false)
				$el->$k = $url.ltrim($el->$k,'/');
		});
	}

	function setBaseHref($href){
		$this->baseHref = $href;
	}
	function getProtocolHref(){
		return 'http'.(@$this->server["HTTPS"]=="on"?'s':'').'://';
	}
	function getServerHref(){
		return $this->server['SERVER_NAME'];
	}
	function getPortHref(){
		$ssl = @$this->server['HTTPS']==='on';
		return @$this->server['SERVER_PORT']&&((!$ssl&&(int)$this->server['SERVER_PORT']!=80)||($ssl&&(int)$this->server['SERVER_PORT']!=443))?':'.$this->server['SERVER_PORT']:'';
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
			if(isset($this->server['SURIKAT_URI'])){
				$this->suffixHref = ltrim($this->server['SURIKAT_URI'],'/');				
			}
			else{
				$docRoot = $this->server['DOCUMENT_ROOT'].'/';
				//$docRoot = dirname($this->server['SCRIPT_FILENAME']).'/';
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
	function getLocation(){
		return $this->getBaseHref().ltrim($this->server['REQUEST_URI'],'/');
	}
}