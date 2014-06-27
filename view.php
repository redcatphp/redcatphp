<?php namespace surikat;
use surikat\control\HTTP;
use surikat\view\FILE;
use surikat\view\TML;
class view {
	static $uriParams = array();
	static $PATH;
	static $xDom = 'x-dom/';
	static function preHooks(){
		$path = func_num_args()?func_get_arg(0):static::$PATH;
		static::serviceHook($path);
	}
	static function postHooks(){}
	static function index(){
		static::preHooks(func_num_args()?func_get_arg(0):static::$PATH);
		static::exec(static::param(0).'.tml');
	}
	static function serviceHook(){
		$path = func_num_args()?func_get_arg(0):static::$PATH;
		if(strpos($path,'/service/')===0&&service::method(str_replace('/','_',substr($path,9))))
			exit;
	}
	static function exec($file){
		try{
			FILE::display($file);
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
		$head = $TML->find('head',0);
		$href = is_bool(static::$xDom)?'':static::$xDom;
		$s = array();
		$TML->recursive(function($el)use($TML,$head,$href,&$s){
			if(
				($is=$el->attr('is')?$el->attr('is'):(preg_match('/(?:[a-z][a-z]+)-(?:[a-z][a-z]+)/is',$el->nodeName)?$el->nodeName:false))
				&&!in_array($is,$s)
				&&!$head->find('link[href="'.$href.strtolower($is).'.css"]',0)
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
	static function registerPresent($TML){
		if(!isset($TML->childNodes[0])||$TML->childNodes[0]->namespace!='present')
			$TML->prepend(new TML('<present: uri="static" />',$TML));
	}
	static function initialize(){
		static::$PATH = @$_SERVER['PATH_INFO'];
		if(control::devHas(control::dev_view))
			FILE::$FORCECOMPILE = 1;
		FILE::$COMPILE[] = array('view','document');
		static::$uriParams = static::getUriParams(ltrim(@$_SERVER['PATH_INFO'],'/'));
	}
	static $separators = array(
		'Eq'=>':',
		'And'=>'|',
		'Or'=>'&',
	);
	static $separatorWord = '-';
	static $forbiddenChrParam = array(
		'?','%',',','!','^','¨','#','~',"'",'"',"\r","\n","\t"," ",
		'{','(','_','$','@',')',']','}','=','+','$','£','*','µ','§','/',
		';','.'
	);
	static function filterParam($s){
		$s = trim($s);
		$s = strip_tags($s);
		$forbid = array_merge(array_values(static::$separators),static::$forbiddenChrParam);
		$s = str_replace($forbid,static::$separatorWord,$s);
		$s = trim($s,static::$separatorWord);
		if(strpos($s,static::$separatorWord.static::$separatorWord)!==false){
			$ns = '';
			$l = strlen($s)-1;
			for($i=0;$i<=$l;$i++)
				if(!($i&&$s[$i]==static::$separatorWord&&$s[$i-1]==static::$separatorWord))
					$ns .= $s[$i];
			$s = $ns;
		}
		return $s;
	}
	static function getUriParams($path){
		$uriParams = array();
		$min = array();
		if(($pos=strpos($path,self::$separators['Eq']))!==false)
			$min[] = $pos;
		if(($pos=strpos($path,self::$separators['And']))!==false)
			$min[] = $pos;
		if(!empty($min)){
			$sepDir = min($min);
			$uriParams[0] = substr($path,0,$sepDir);
			$path = substr($path,$sepDir);
			$x = explode(self::$separators['And'],$path);
			foreach($x as $v){
				$x2 = explode(self::$separators['Or'],$v);
				if($k=$i=strpos($v,self::$separators['Eq'])){
					$k = substr($v,0,$i);
					$v = substr($v,$i+1);
				}
				$v = strpos($v,self::$separators['Or'])?explode(self::$separators['Or'],$v):$v;
				if($k)
					$uriParams[$k] = $v;
				elseif(!empty($v))
					$uriParams[] = $v;
			}
		}
		else
			$uriParams[0] = $path;
		return $uriParams;
	}
	static function param($k=null){
		return $k===null?static::$uriParams:(isset(static::$uriParams[$k])?static::$uriParams[$k]:null);
	}
	static function encode($v){
		return str_replace('%2F','/',urlencode(urldecode(trim($v))));
	}
}
view::initialize();
