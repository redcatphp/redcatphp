<?php
namespace RedCat\Localize;
/*
 * Translator - gettext wrapper
 *
 * @package Localize
 * @version 1.4
 * @link http://github.com/redcatphp/Localize/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://redcatphp.com
 */
class Translator {
	protected static $systemLocales;
	protected static $bindStack = [];
	protected static $defaultDomain = 'messages';
	
	private $locale;
	private $domain;
	private $originLocale;
	private $realLocale;
	private $localesRoot;
	private $gettext;
	private $altLocales;
	private $realDomain;
	
	protected $countryAuto = true;
	protected $defaultLocale = 'en_US';
	protected $defaultLocalesRoot;
	protected $suffixLocales = '.utf8';
	protected $text_domains = [];
	protected $default_domain = 'messages';
	protected $LC_CATEGORIES = ['LC_CTYPE', 'LC_NUMERIC', 'LC_TIME', 'LC_COLLATE', 'LC_MONETARY', 'LC_MESSAGES', 'LC_ALL'];
	protected $EMULATEGETTEXT = 1;
	protected $GettextEmulators = [];
	private static $instance;
	
	public $dev;
	
	static function initialize(){
		exec('locale -a',self::$systemLocales);
	}
	static function getInstance(){
		if(!isset(self::$instance)){
			if(class_exists('RedCat\Wire\Di'))
				self::$instance = \RedCat\Wire\Di::getInstance()->create(__CLASS__);
			else
				self::$instance = new self;
		}
		return self::$instance;
	}
	function __construct($locale=null,$domain=null,$timezone=null,$dev=true){
		$this->dev = $dev;
		if(isset($locale)){
			$this->set($locale,$domain,$timezone);
		}
	}
	function set($locale=null,$domain=null,$timezone=null){
		if(!isset($domain))
			$domain = self::$defaultDomain;
		if(!$timezone)
			$timezone = @date_default_timezone_get();
		date_default_timezone_set($timezone);
		$this->defaultLocalesRoot = 'langs';
		$this->localesRoot = $this->defaultLocalesRoot;
		$this->originLocale = $locale;
		$this->locale = $locale?:$this->defaultLocale;
		$this->domain = $domain;
		if($this->dev)
			$this->realDomain = $this->getLastMoFile();
		else
			$this->realDomain = $this->domain;
		$this->realLocale = $this->locale.$this->suffixLocales;
		$this->altLocales = $this->GettextEmulator($this->realLocale)->get_list_of_locales($this->realLocale);
		if(function_exists('setlocale')){
			foreach($this->altLocales as $lc){
				if(in_array($lc,self::$systemLocales)){
					$this->EMULATEGETTEXT = 0;
					break;
				}
			}
			if(	$this->EMULATEGETTEXT
				&&$this->countryAuto
				&&strpos($this->locale,'_')===false
				&&is_dir($this->localesRoot.'/'.$this->locale)
			){
				$found = false;
				$lookFor = $lc.'_'.strtoupper($lc);
				if(in_array($lookFor,self::$systemLocales)){
					$found = $lookFor;
				}
				elseif($this->suffixLocales&&in_array($lookFor.$this->suffixLocales,self::$systemLocales)){				
					$found = $lookFor.$this->suffixLocales;
				}
				else{
					foreach(self::$systemLocales as $lc){
						if(strpos($lc,$this->locale.'_')===0){
							$found = $lc;
							break;
						}
					}
				}
				if($found){				
					$lc = $found;	
					if(!is_dir($this->localesRoot.'/'.$lc)){
						$cwd = getcwd();
						chdir($this->localesRoot);
						symlink($this->locale,$lc);
						chdir($cwd);
					}
					if(false!==$p=strpos($lc,'.'))
						$lc = substr($lc,0,$p);
					if(false!==$p=strpos($lc,'@'))
						$lc = substr($lc,0,$p);
					$this->locale = $lc;
					$this->realLocale = $this->locale.$this->suffixLocales;
					$this->EMULATEGETTEXT = 0;
				}
			}
		}
		$this->bind();
	}
	function n__($singular,$plural,$number){
		return $this->ngettext($singular, $plural, $number);
	}
	function __($msgid){
		return $this->gettext($msgid);
	}
	function unbind(){
		array_pop(self::$bindStack);
		$last = end(self::$bindStack);
		if(!$last){
			$last = [
				$this->defaultLocale,
				$this->defaultLocale.$this->suffixLocales,
				self::$defaultDomain,
				$this->defaultLocalesRoot
			];
		}
		if(!isset($last[1]))
			return;
		putenv('LANG='.$last[0]);
		putenv('LANGUAGE='.$last[0]);
		putenv('LC_ALL='.$last[0]);
		$this->setlocale(LC_ALL,$last[1]);
		$this->bindtextdomain($last[2],$last[3]);
		$this->textdomain($last[2]);
		$this->bind_textdomain_codeset($last[2], "UTF-8");
	}
	function bind(){
		self::$bindStack[] = [$this->locale,$this->realLocale,$this->realDomain,$this->localesRoot];
		if(!isset($this->locale))
			return;
		//var_dump($this->realLocale,$this->localesRoot);
		$lang = $this->getLangCode();
		putenv('LANG='.$this->locale);
		putenv('LANGUAGE='.$this->locale);
		putenv('LC_ALL='.$this->locale);
		$this->setlocale(LC_ALL,$this->realLocale);
		$this->bindtextdomain($this->realDomain,$this->localesRoot);
		$this->textdomain($this->realDomain);
		$this->bind_textdomain_codeset($this->realDomain, "UTF-8");
	}
	function getLocale(){
		return $this->locale;
	}
	function getLangCode(){
		if(false!==$p=strpos($this->locale,'_')){
			return substr($this->locale,0,$p);
		}
		return $this->locale;
	}
	function getLastMoFile(){
		$mo = glob($this->localesRoot.'/'.$this->locale.'/LC_MESSAGES/'.$this->domain.'.*.mo');
		return !empty($mo)?substr(basename(end($mo)),0,-3):$this->domain;
	}
	
	function setlocale(){
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		return call_user_func_array($f,func_get_args());
	}
	function bindtextdomain(){
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		return call_user_func_array($f,func_get_args());
	}
	function textdomain(){
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		return call_user_func_array($f,func_get_args());
	}
	function bind_textdomain_codeset(){
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		return call_user_func_array($f,func_get_args());
	}
	
	function gettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function ngettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function dgettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function dngettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function dcgettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function dcngettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function pgettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function dpgettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function dcpgettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function npgettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function dnpgettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function dcnpgettext(){
		$this->bind();
		$f = __FUNCTION__;
		if($this->EMULATEGETTEXT)
			$f = [$this->GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function GettextEmulator($lc=null){
		if(!isset($this->GettextEmulators[$lc]))
			$this->GettextEmulators[$lc] = new GettextEmulator($lc);
		return $this->GettextEmulators[$lc];
	}
}
Translator::initialize();