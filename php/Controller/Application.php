<?php namespace Surikat\Controller;
use Surikat\Config\Dev;
use Surikat\I18n;
use Surikat\Tool\HTTP;
use Surikat\View\FILE;
use Surikat\View\TML;
use Surikat\Route\Dispatcher;
use Surikat\Route\Finder_ByTml;

class Application{
	function run($path){
		$dispatcher = new Dispatcher();
		
		$this->URI = new Finder_ByTml();
		
		$dispatcher
			->append('/service/',['Service\\Service','method'])
			->append($this->URI,$this)
		;
		if(! $dispatcher->run($path) ){
			//404
		}
		//$this->hookTml('plugin/');
	}
	function __invoke($tml){
		$this->indexExec($tml.'.tml');
	}
	
	protected $URI;
	protected $xDom = 'x-dom/';
	protected $prefixTmlCompile = '';
	function getUri(){
		return $this->URI;
	}
	function preHooks(){
		$path = func_num_args()?func_get_arg(0):$this->URI->getPath();
	}
	function postHooks(){}
	function i18nBySubdomain(&$templatePath=null){
		if(!$templatePath)
			$templatePath = $this->URI[0];
		if(($lang=$this->URI->getSubdomain())&&strlen($lang)==2){
			$this->URI->setLang($lang);	
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
		$this->prefixTmlCompile = '.'.$lang.'/';
		i18n::setLocale($lang);
		return $lang;
	}
	function indexExec($tml){
		$this->exec($tml,[],[
			'dirCompile'=>SURIKAT_TMP.'viewCompile/'.$this->prefixTmlCompile,
			'dirCache'=>SURIKAT_TMP.'viewCache/'.$this->prefixTmlCompile,
		]);
	}
	function hookTml($s){
		$path = ltrim($this->URI->getPath(),'/');
		$pathFS = func_num_args()>1?func_get_arg(1):$s;
		if(strpos($path,$s)===0){
			$path = substr($path,strlen($s)).'.tml';
			$this->exec($path,[],[
				'dirCwd'=>SURIKAT_PATH.$pathFS,
				'dirAdd'=>SURIKAT_SPATH.$pathFS,
				'dirCompile'=>SURIKAT_TMP.'viewCompile/.'.$pathFS,
				'dirCache'=>SURIKAT_TMP.'viewCache/.'.$pathFS,
			]);
			exit;
		}
	}
	function exec($file,$vars=[],$options=[]){
		try{
			$this->_FILE->setPath($file);
			$this->_FILE->setOptions($options);
			$this->_FILE->display($vars);
		}
		catch(\Surikat\View\Exception $e){
			$this->postHooks();
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			$this->_FILE->setPath($c.'.tml');
			$this->_FILE->display();
		}
		catch(\Surikat\View\Exception $e){
			HTTP::code($e->getMessage());
		}
		exit;
	}
	function document($TML){
		$this->registerPresenter($TML);
		if($this->xDom)
			$this->xDom($TML);
		if(!Dev::has(Dev::VIEW))
			$this->autoMIN($TML);
	}
	function xDom($TML){
		$head = $TML->find('head',0);
		if(!$head&&$TML->find('body',0)){
			$head = $TML;
			$head->append('<script type="text/javascript" src="/js/js.js"></script>');
			$head->append('<script type="text/javascript">$js().dev=true;$js().min=false;$css().min=false;</script>');
		}
		$href = is_bool($this->xDom)?'':$this->xDom;
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
	function autoMIN($TML){
		foreach($TML('link[href]') as $l)
			if(strpos($l,'://')===false)
				$l->href = (strpos($l->href,'/')!==false?dirname($l->href).'/':'').pathinfo($l->href,PATHINFO_FILENAME).'.min.'.pathinfo($l->href,PATHINFO_EXTENSION);
		foreach($TML('script[src]') as $s)
			if(strpos($s->src,'://')===false&&substr($s->src,-8)!='.pack.js')
				$s->src = (strpos($s->src,'/')!==false?dirname($s->src).'/':'').pathinfo($s->src,PATHINFO_FILENAME).'.min.'.pathinfo($l->src,PATHINFO_EXTENSION);
	}
	function setCDN($TML,$url=true){
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
	function registerPresenter($TML){
		if(!isset($TML->childNodes[0])||$TML->childNodes[0]->namespace!='Presenter')
			$TML->prepend('<Presenter:Basic uri="static" />');
	}	
	protected $_FILE;
	function __construct(){
		$this->_FILE = new FILE();
		$this->_FILE->registerCompile([$this,'document']);
	}
}