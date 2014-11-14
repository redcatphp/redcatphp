<?php namespace surikat;
use surikat\control\i18n\i18n;
use surikat\control\HTTP;
use surikat\view\FILE;
use surikat\view\TML;
class view {
	protected static $URI;
	static $xDom = 'x-dom/';
	static function getUri(){
		return static::$URI;
	}
	static function preHooks(){
		$path = func_num_args()?func_get_arg(0):static::$URI->getPath();
		static::serviceHook($path);
	}
	static function postHooks(){}
	static function i18nBySubdomain(&$templatePath=null){
		if(!$templatePath)
			$templatePath = static::$URI[0];
		if(($lang=static::$URI->getSubdomain())&&strlen($lang)==2){
			static::$URI->setLang($lang);	
			if(file_exists($langFile='langs/'.$lang.'.ini')){
				$langMap = parse_ini_file($langFile);
				if(isset($langMap[$templatePath]))
					$templatePath = $langMap[$templatePath];
				elseif(($k=array_search($templatePath,$langMap))!==false){
					header('Location: /'.$k,301);
					exit;
				}
			}			
		}
		else
			$lang = 'en';
		i18n::setLocale($lang);
		return $lang;
	}
	static function index(){
		static::preHooks(func_num_args()?func_get_arg(0):static::$URI->getPath());
		static::exec(static::$URI->param(0).'.tml');
	}
	static function hookTml($s){
		$path = func_num_args()>1?func_get_arg(1):ltrim(static::$URI->getPath(),'/');
		if(strpos($path,$s)===0){
			$path = substr($path,strlen($s)).'.tml';
			static::exec($path,[],[
				'dirCwd'=>control::$CWD.$s,
				'dirCompile'=>control::$TMP.'view_compile/.'.$s,
				'dirCache'=>control::$TMP.'view_cache/.'.$s,
			]);
			exit;
		}
	}
	static function serviceHook(){
		$path = func_num_args()?func_get_arg(0):static::$URI->getPath();
		if(strpos($path,'/service/')===0&&service::method(str_replace('/','_',substr($path,9))))
			exit;
	}
	static function exec($file,$vars=[],$options=[]){
		try{
			FILE::display($file,$vars,$options);
		}
		catch(\surikat\view\Exception $e){
			static::postHooks();
			static::error($e->getMessage());
		}
	}
	static function error($c){
		try{
			FILE::display($c.'.tml');
		}
		catch(\surikat\view\Exception $e){
			HTTP::code($e->getMessage());
		}
		exit;
	}
	static function document($TML){
		static::registerPresent($TML);
		if(static::$xDom)
			static::xDom($TML);
		if(!control::devHas(control::dev_view))
			static::autoMIN($TML);
	}
	static function xDom($TML){
		$head = $TML->children('head',0);
		if(!$head){
			$head = $TML;
			$head->append('<script type="text/javascript" src="/js/js.js"></script>');
			$head->append('<script type="text/javascript">$js().dev=true;$js().min=false;$css().min=false;</script>');
		}
		$href = is_bool(static::$xDom)?'':static::$xDom;
		$s = [];
		$TML->recursive(function($el)use($TML,$head,$href,&$s){
			if(
				($is=$el->attr('is')?$el->attr('is'):(preg_match('/(?:[a-z][a-z]+)-(?:[a-z][a-z]+)/is',$el->nodeName)?$el->nodeName:false))
				&&!in_array($is,$s)
				&&!$head->children('link[href="'.$href.strtolower($is).'.css"]',0)
				&&(
					is_file(control::$CWD.($h=$href.strtolower($is).'.css'))
					||is_file(control::$SURIKAT.$h)
					||is_file(control::$CWD.($h=$href.strtolower($is).'.scss'))
					||is_file(control::$SURIKAT.$h)
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
				($el->nodeName=='link'&&$el->type=='text/css'&&control::devHas(control::dev_css))
				|| ($el->nodeName=='link'&&$el->type=='image/x-icon'&&control::devHas(control::dev_img))
				|| ($el->nodeName=='img'&&control::devHas(control::dev_img))
				|| ($el->nodeName=='script'&&control::devHas(control::dev_js))
			)
				return;
			$k = $el->src?'src':'href';
			if($el->$k&&strpos('://',$el->$k)===false)
				$el->$k = $url.ltrim($el->$k,'/');
		});
	}
	static function registerPresent($TML){
		if(!isset($TML->childNodes[0])||$TML->childNodes[0]->namespace!='present')
			$TML->prepend(new TML('<present: uri="static" />',$TML));
	}
	static function initialize(){
		if(control::devHas(control::dev_view))
			FILE::$FORCECOMPILE = 1;
		FILE::$COMPILE[] = ['view','document'];
		static::$URI = uri::factory(0,isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'');
	}
}
view::initialize();