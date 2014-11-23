<?php namespace surikat;
use surikat\i18n;
use surikat\control\HTTP;
use surikat\view\FILE;
use surikat\view\TML;
class view {
	use \factory;
	protected static $__factory = '\view';
	protected $URI;
	protected $xDom = 'x-dom/';
	protected $prefixTmlCompile = '';
	function getUri(){
		return $this->URI;
	}
	function preHooks(){
		$path = func_num_args()?func_get_arg(0):$this->URI->getPath();
		$this->serviceHook($path);
		$this->hookTml('plugin/');
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
	function index(){
		$this->preHooks(func_num_args()?func_get_arg(0):$this->URI->getPath());
		$this->indexExec($this->URI->param(0).'.tml');
	}
	function indexExec($tml){
		$this->exec($tml,[],[
			'dirCompile'=>control::$TMP.'view_compile/'.$this->prefixTmlCompile,
			'dirCache'=>control::$TMP.'view_cache/'.$this->prefixTmlCompile,
		]);
	}
	function hookTml($s){
		$path = ltrim($this->URI->getPath(),'/');
		$pathFS = func_num_args()>1?func_get_arg(1):$s;
		if(strpos($path,$s)===0){
			$path = substr($path,strlen($s)).'.tml';
			$this->exec($path,[],[
				'dirCwd'=>control::$CWD.$pathFS,
				'dirAdd'=>control::$SURIKAT.$pathFS,
				'dirCompile'=>control::$TMP.'view_compile/.'.$pathFS,
				'dirCache'=>control::$TMP.'view_cache/.'.$pathFS,
			]);
			exit;
		}
	}
	function serviceHook(){
		$path = func_num_args()?func_get_arg(0):$this->URI->getPath();
		if(strpos($path,'/service/')===0&&service::method(str_replace('/','_',substr($path,9))))
			exit;
	}
	function exec($file,$vars=[],$options=[]){
		try{
			FILE::display($file,$vars,$options);
		}
		catch(\surikat\view\Exception $e){
			$this->postHooks();
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			FILE::display($c.'.tml');
		}
		catch(\surikat\view\Exception $e){
			HTTP::code($e->getMessage());
		}
		exit;
	}
	function document($TML){
		$this->registerPresent($TML);
		if($this->xDom)
			$this->xDom($TML);
		if(!dev::has(dev::VIEW))
			$this->autoMIN($TML);
	}
	function xDom($TML){
		$head = $TML->children('head',0);
		if(!$head&&$TML->children('body',0)){
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
				($el->nodeName=='link'&&$el->type=='text/css'&&dev::has(dev::CSS))
				|| ($el->nodeName=='link'&&$el->type=='image/x-icon'&&dev::has(dev::IMG))
				|| ($el->nodeName=='img'&&dev::has(dev::IMG))
				|| ($el->nodeName=='script'&&dev::has(dev::JS))
			)
				return;
			$k = $el->src?'src':'href';
			if($el->$k&&strpos('://',$el->$k)===false)
				$el->$k = $url.ltrim($el->$k,'/');
		});
	}
	function registerPresent($TML){
		if(!isset($TML->childNodes[0])||$TML->childNodes[0]->namespace!='present')
			$TML->prepend(new TML('<present: uri="static" />',$TML));
	}	
	protected $_FILE;
	function __construct(){
		$this->_FILE = new FILE();
		FILE::$COMPILE[] = [$this,'document'];
		$this->URI = uri::getInstance();
		$this->URI->setPath($_SERVER['PATH_INFO']?$_SERVER['PATH_INFO']:'');
	}
}