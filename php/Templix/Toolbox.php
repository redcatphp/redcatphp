<?php namespace Templix;
use ObjexLoader\MutatorMagicTrait;
class Toolbox{
	use MutatorMagicTrait;
	function JsIs($Tml,$href='css/is.'){
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
			if(
				($is=$el->attr('is')?$el->attr('is'):(preg_match('/(?:[a-z][a-z]+)-(?:[a-z][a-z]+)/is',$el->nodeName)?$el->nodeName:false))
				&&!in_array($is,$s)
				&&!$head->children('link[href="'.$href.strtolower($is).'.css"]',0)
				&&Toolbox::getHttpResponseCode($this->FluxServer_Url->getBaseHref().$href.strtolower($is).'.css')===200
			)
				$s[] = $is;
		});
		foreach($s as $is)
			$head->append('<link href="'.$href.strtolower($is).'.css" rel="stylesheet" type="text/css">');
	}
	function autoMIN($Tml){
		if(!$this->Dev_Level->CSS){
			foreach($Tml('link[href][rel=stylesheet],link[href][type="text/css"]') as $l)
				if(strpos($l,'://')===false)
					$l->href = (strpos($l->href,'/')!==false?dirname($l->href).'/':'').pathinfo($l->href,PATHINFO_FILENAME).'.min.'.pathinfo($l->href,PATHINFO_EXTENSION);
		}
		if(!$this->Dev_Level->JS){
			foreach($Tml('script[src]') as $s)
				if(strpos($s->src,'://')===false&&substr($s->src,-8)!='.pack.js')
					$s->src = (strpos($s->src,'/')!==false?dirname($s->src).'/':'').pathinfo($s->src,PATHINFO_FILENAME).'.min.'.pathinfo($l->src,PATHINFO_EXTENSION);
		}
	}
	static function getHttpResponseCode($theURL) {
		$headers = get_headers($theURL);
		return (int)substr($headers[0], 9, 3);
	}
	function setCDN($Tml,$url=true){
		if($url===true){
			$prefix = 'cdn';
			$url = 'http'.(@$_SERVER["HTTPS"]=="on"?'s':'').'://'.(strpos($_SERVER['SERVER_NAME'],$prefix.'.')===0?'':$prefix.'.').$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']&&(int)$_SERVER['SERVER_PORT']!=80?':'.$_SERVER['SERVER_PORT']:'').'/';
		}
		if(substr($url,-1)!='/')
			$url .= '/';
		$Tml('script[src],img[src],link[href]')->each(function($el)use($url){
			if(
				($el->nodeName=='link'&&$el->type=='text/css'&&$this->Dev_Level->CSS)
				|| ($el->nodeName=='link'&&$el->type=='image/x-icon'&&$this->Dev_Level->IMG)
				|| ($el->nodeName=='img'&&$this->Dev_Level->IMG)
				|| ($el->nodeName=='script'&&$this->Dev_Level->JS)
			)
				return;
			$k = $el->src?'src':'href';
			if($el->$k&&strpos('://',$el->$k)===false)
				$el->$k = $url.ltrim($el->$k,'/');
		});
	}
	function i18nGettext($Tml,$cache=true){
		$Tml('html')->attr('lang',$this->I18n_Translator->getLangCode());
		$Tml('*[ni18n] TEXT:hasnt(PHP)')->data('i18n',false);
		$Tml('*[i18n] TEXT:hasnt(PHP)')->each(function($el)use($cache){
			$rw = "$el";
			$l = strlen($rw);
			$left = $l-strlen(ltrim($rw));
			$right = $l-strlen(rtrim($rw));
			if($left)
				$left = substr($rw,0,$left);
			else
				$left = '';
			if($right)
				$right = substr($rw,-1*$right);
			else
				$right = '';
			$rw = trim($rw);
			if(!$rw)
				return;
			if($el->data('i18n')!==false){
				if($cache){
					$rw = $this->I18n_Translator()->__($rw);
				}
				else{
					$rw = str_replace("'","\'",$rw);
					$rw = '<?php echo $this->I18n_Translator()->__(\''.$rw.'\');?>';
				}
				$el->write($left.$rw.$right);
			}
		});
		$Tml('*')->each(function($Tml){
			foreach($Tml->attributes as $k=>$v){
				if(strpos($k,'i18n-')===0){
					$Tml->removeAttr($k);
					$Tml->attr(substr($k,5),$this->I18n_Translator()->__($v));
				}
			}
		});
		$Tml('*[i18n]')->removeAttr('i18n');
	}
	function i18nRel($Tml,$lang,$path,$langMap=null){
		$head = $Tml->find('head',0);
		if(!$head)
			return;
		
		if(!isset($langMap)&&file_exists($langFile='langs/'.$lang.'.ini')){
			$langMap = parse_ini_file($langFile);
		}
		$xPath = $path;
		if($langMap&&isset($langMap[$path])){
			$xPath = $langMap[$path];
		}
		$head->append('<link rel="alternate" href="'.$this->FluxServer_Url->getSubdomainHref().$xPath.'" hreflang="x-default" />');
		foreach(glob('langs/*.ini') as $langFile){
			$lg = pathinfo($langFile,PATHINFO_FILENAME);
			$langMap = parse_ini_file($langFile);
			$lcPath = ($k=array_search($xPath,$langMap))?$k:$xPath;
			$head->append('<link rel="alternate" href="'.$this->FluxServer_Url->getSubdomainHref($lg).$lcPath.'" hreflang="'.$lg.'" />');
		}
	}
}