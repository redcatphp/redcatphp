<?php
namespace Wild\Plugin\Templix;
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
		if($Tml->templix&&!$Tml->templix->devCss){
			foreach($Tml('link[href][rel=stylesheet],link[href][type="text/css"]') as $l)
				if(strpos($l,'://')===false)
					$l->href = (strpos($l->href,'/')!==false?dirname($l->href).'/':'').pathinfo($l->href,PATHINFO_FILENAME).'.min.'.pathinfo($l->href,PATHINFO_EXTENSION);
		}
		if($Tml->templix&&!$Tml->templix->devJs){
			foreach($Tml('script[src]') as $s)
				if(strpos($s->src,'://')===false&&substr($s->src,-8)!='.pack.js')
					$s->src = (strpos($s->src,'/')!==false?dirname($s->src).'/':'').pathinfo($s->src,PATHINFO_FILENAME).'.min.'.pathinfo($l->src,PATHINFO_EXTENSION);
		}
	}
	function setCDN($Tml,$url){
		$url = rtrim($url,'/').'/';
		$Tml('script[src],img[src],link[href]')->each(function($el)use($url,$Tml){
			if($el->attr('no-cdn')||($el->nodeName=='link'&&$el->rel&&$el->rel!='stylesheet'))
				return;
			$k = $el->src?'src':'href';
			if($el->$k&&strpos($el->$k,'://')===false)
				$el->$k = $url.ltrim($el->$k,'/');
		});
		$Tml('base')->attr('data-cdn',$url);
	}
}