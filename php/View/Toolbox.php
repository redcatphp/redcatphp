<?php namespace Surikat\View;
use Surikat\I18n\Lang;
class Toolbox{
	static function JsIs($TML,$href='css/is.'){
		$head = $TML->find('head',0);
		if(!$head&&$TML->find('body',0)){
			$head = $TML;
			$head->append('<script type="text/javascript" src="/js/js.js"></script>');
			$head->append('<script type="text/javascript">$js().dev=true;$js().min=false;$css().min=false;</script>');
		}
		$s = [];
		$TML->recursive(function($el)use($TML,$head,$href,&$s){
			if(
				($is=$el->attr('is')?$el->attr('is'):(preg_match('/(?:[a-z][a-z]+)-(?:[a-z][a-z]+)/is',$el->nodeName)?$el->nodeName:false))
				&&!in_array($is,$s)
				&&!$head->children('link[href="'.$href.strtolower($is).'.css"]',0)
				&&(
					is_file(SURIKAT_PATH.($h=$href.strtolower($is).'.css'))
					||is_file(SURIKAT_SPATH.$h)
					||is_file(SURIKAT_PATH.($h=$href.strtolower($is).'.scss'))
					||is_file(SURIKAT_SPATH.$h)
				)
			)
				$s[] = $is;
		});
		foreach($s as $is)
			$head->append('<link href="'.$href.strtolower($is).'.css" rel="stylesheet" type="text/css">');
	}
	static function autoMIN($TML){
		foreach($TML('link[href]') as $l)
			if(strpos($l,'://')===false)
				$l->href = (strpos($l->href,'/')!==false?dirname($l->href).'/':'').pathinfo($l->href,PATHINFO_FILENAME).'.min.'.pathinfo($l->href,PATHINFO_EXTENSION);
		foreach($TML('script[src]') as $s)
			if(strpos($s->src,'://')===false&&substr($s->src,-8)!='.pack.js')
				$s->src = (strpos($s->src,'/')!==false?dirname($s->src).'/':'').pathinfo($s->src,PATHINFO_FILENAME).'.min.'.pathinfo($l->src,PATHINFO_EXTENSION);
	}
	static function setCDN($TML,$url=true){
		if($url===true){
			$prefix = 'cdn';
			$url = 'http'.(@$_SERVER["HTTPS"]=="on"?'s':'').'://'.(strpos($_SERVER['SERVER_NAME'],$prefix.'.')===0?'':$prefix.'.').$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']&&(int)$_SERVER['SERVER_PORT']!=80?':'.$_SERVER['SERVER_PORT']:'').'/';
		}
		if(substr($url,-1)!='/')
			$url .= '/';
		$TML('script[src],img[src],link[href]')->each(function($el)use($url){
			if(
				($el->nodeName=='link'&&$el->type=='text/css'&&Dev::has(Dev::CSS))
				|| ($el->nodeName=='link'&&$el->type=='image/x-icon'&&Dev::has(Dev::IMG))
				|| ($el->nodeName=='img'&&Dev::has(Dev::IMG))
				|| ($el->nodeName=='script'&&Dev::has(Dev::JS))
			)
				return;
			$k = $el->src?'src':'href';
			if($el->$k&&strpos('://',$el->$k)===false)
				$el->$k = $url.ltrim($el->$k,'/');
		});
	}
	static function registerPresenter($TML){
		if(!isset($TML->childNodes[0])||$TML->childNodes[0]->namespace!='Presenter')
			$TML->prepend('<Presenter:Basic uri="static" />');
	}
	static function Internationalization($TML,$cache=true){
		$TML('html')->attr('lang',Lang::get());
		$TML('*[ni18n] TEXT:hasnt(PHP)')->data('i18n',false);
		$TML('*[i18n] TEXT:hasnt(PHP)')->each(function($el)use($cache){
			if($el->data('i18n',false)!==false){
				if($cache)
					$el->write(Lang::gettext(trim("$el")));
				else
					$el->write("<?php echo __('".str_replace("'","\'",trim("$el"))."'); ?>");
			}
		});
	}
}