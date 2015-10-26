<?php
namespace Wild\Plugin\Templix;
use Wild\Localize\Translator;
use Wild\Wire\Di;
class TemplixL10n extends Templix{
	protected $Translator;
	protected $autoWrapL10n = true;
	protected $langDefault;
	protected $cdnSubdomain;
	function __construct($file=null,$vars=null,
		$devTemplate=true,$devJs=true,$devCss=true,$devImg=false,
		Di $di,Translator $Translator=null, $server=null,
		$cdnSubdomain = null, $langDefault = null
	){
		parent::__construct($file,$vars,$devTemplate,$devJs,$devCss,$devImg,$di);
		$this->di = $di;
		if(!$server)
			$server = &$_SERVER;
		$this->Translator = $Translator;
		$this->server = $server;
		$this->langDefault = $langDefault;
		$this->cdnSubdomain = $cdnSubdomain;
	}
	function __invoke($file){
		list($lang,$langMap,$file) = (array)$file;
		
		$this['LANG'] = $lang;
		
		if(is_array($file)){
			list($hook,$file) = (array)$file;
			if(substr($hook,0,8)=='surikat/')
				$hook = substr($hook,8);
			$this->setDirCwd([$hook.'/','surikat/'.$hook.'/']);
		}
		
		$this->Translator->set($lang);
		$this->setDirCompileSuffix('.'.$lang.'/');
		$this->onCompile(function($TML)use($lang,$file,$langMap){
			if($this->langDefault!=$lang)
				$this->i18nGettext($TML);
			$this->i18nRel($TML,$lang,$file,$langMap);
			if($langMap){
				foreach($TML('a[href]') as $a){
					if(strpos($a->href,'://')===false&&strpos($a->href,'javascript:')!==0&&strpos($a->href,'#')!==0){
						if(($k=array_search($a->href,$langMap))!==false)
							$a->href = $k;
					}
				}
			}
			
		});
		if($this->cdnSubdomain){
			$cdn = $this->getSubdomainHref($this->cdnSubdomain);
			$this->onCompile(function($TML)use($cdn){
				$this->toolbox->setCDN($TML,$cdn);
			},true);
		}
		
		$this->onCompile(function($TML)use($cdn){
			$TML('*')->removeAttr('ni18n');
			$TML('*')->removeAttr('i18n');
		},true);
		
		return $this->query($file);
	}
	function i18nWrapCode($rw,$cache=true){
		if(!empty($rw)){
			if($cache){
				$rw = $this->Translator->__($rw);
			}
			else{
				$rw = str_replace("'","\'",$rw);
				$rw = '<?php echo __(\''.$rw.'\');?>';
			}
		}
		return $rw;
	}
	function i18nGettext($TML,$cache=true){
		
		//auto-wrap
		if($this->autoWrapL10n){
			$aggr = [];
			$TML('*[ni18n] *,script,style,code')->data('i18n',false);
			$inlineEls = ['br','i','b','u','em','strong','abbr','a'];			
			$inlineStr = implode(',',$inlineEls);
			$inlineElsCheck = $inlineEls;
			$inlineElsCheck[] = 'TEXT';
			$TML($inlineStr)->each(function($el)use(&$aggr,&$inlineElsCheck){
				if($el->data('i18n')===false)
					return;
				if(
					($el->previousSibling&&($el->previousSibling->nodeName=='TEXT'||in_array($el->previousSibling->nodeName,$inlineElsCheck)))
					||($el->nextSibling&&($el->nextSibling->nodeName=='TEXT'||in_array($el->nextSibling->nodeName,$inlineElsCheck)))
				){
					$id = '{{.-;-:-'.uniqid('translateAggr',true).'-:-;-.}}';
					$t = (string)$el;
					$t = preg_replace('/(?:\s\s+|\n|\t|\r)/', ' ', $t);
					$aggr[$id] = $t;
					$el('*')->data('i18n',false);
					
					$el->clear();
					$el->write($id);
					$el->selfClosed = false;
					$el->nodeName = 'TEXT';
					//$el->replaceWith($id);
				}
			});
			
			$aggr = array_reverse($aggr);
			$aggrK = array_keys($aggr);
			$aggrV = array_values($aggr);
			
			$TML->write((string)$TML);
		}
		
		
		if(!$cache){
			$TML->prepend('<?php include SURIKAT.\'php/Wild/Localize/__.php\'; ?>');
		}
		$TML('html')->attr('lang',$this->Translator->getLangCode());
		$TML('*[ni18n] *, script, style, code')->data('i18n',false);
		$TML('t, TEXT:hasnt(PHP)')->each(function($el)use($cache,&$aggrK,&$aggrV,&$TML){
			if($el->data('i18n')===false)
				return;
			
			if($el->nodeName=='t')
				$rw = $el->getInner();
			else
				$rw = (string)$el;
				
			if($this->autoWrapL10n){
				$rw = str_replace($aggrK,$aggrV,$rw);
			}
			
			$trw = trim($rw);
			if(!$trw)
				return;
			
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
			$rw = $trw;
			
			if(!$el->parent||$el->parent->nodeName!='pre')
				$rw = preg_replace('/(?:\s\s+|\n|\t|\r)/', ' ', $rw);
			$rw = $this->i18nWrapCode($rw,$cache);
			$el->write($left.$rw.$right);
			if($el->nodeName=='t'){
				$el('*')->data('i18n',false);
			}
		});
		$TML('*')->each(function($markup){
			if($markup->data('i18n')===false)
				return;
			foreach($markup->attributes as $k=>$v){
				if(
					$k=='title'
					||($k=='href'&&$markup->nodeName=='a')
					||($k=='value'&&$markup->nodeName=='input'&&$markup->type=='submit')
					||($k=='placeholder'&&$markup->nodeName=='input')
				){
					if(strpos($v,'<?')===false)
						$markup->attr($k,$this->i18nWrapCode($v));
				}
				elseif(strpos($k,'i18n-')===0){
					$markup->removeAttr($k);
					$markup->attr(substr($k,5),$this->i18nWrapCode($v));
				}
			}
		});
	}
	function i18nRel($TML,$lang,$path,$langMap=null){
		$head = $TML->find('head',0);
		if(!$head)
			return;
		
		
		if(!isset($langMap)&&file_exists($langFile='langs/'.$lang.'.ini')){
			$langMap = parse_ini_file($langFile);
		}
		$xPath = $path;
		if($langMap&&isset($langMap[$path])){
			$xPath = $langMap[$path];
		}
		$head->append('<link rel="alternate" href="'.$this->getSubdomainHref().$xPath.'" hreflang="x-default" />');
		foreach(glob('langs/*.ini') as $langFile){
			$lg = pathinfo($langFile,PATHINFO_FILENAME);
			$langMap = parse_ini_file($langFile);
			$lcPath = ($k=array_search($xPath,$langMap))?$k:$xPath;
			$head->append('<link rel="alternate" href="'.$this->getSubdomainHref($lg).$lcPath.'" hreflang="'.$lg.'" />');
		}
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