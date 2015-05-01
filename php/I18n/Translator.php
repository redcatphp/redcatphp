<?php namespace I18n;
use DependencyInjection\MutatorFacadeTrait;
class Translator {
	use MutatorFacadeTrait;
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
	protected $countryAuto = true;
	protected $defaultLocale = 'en_US';
	protected $defaultLocalesRoot;
	protected $suffixLocales = '.utf8';
	protected $text_domains = [];
	protected $default_domain = 'messages';
	protected $LC_CATEGORIES = ['LC_CTYPE', 'LC_NUMERIC', 'LC_TIME', 'LC_COLLATE', 'LC_MONETARY', 'LC_MESSAGES', 'LC_ALL'];
	protected $EMULATEGETTEXT = 1;
	static function initialize(){
		exec('locale -a',self::$systemLocales);
	}
	function __construct($locale=null,$domain=null){
		if($locale)
			$this->set($locale,$domain);
	}
	function _set($locale=null,$domain=null){
		if(!isset($domain))
			$domain = self::$defaultDomain;
		$this->defaultLocalesRoot = SURIKAT_PATH.'langs';
		$tz = $this->Config('langs')->timezone;
		if(!$tz)
			$tz = @date_default_timezone_get();
		date_default_timezone_set($tz);
		$this->localesRoot = $this->defaultLocalesRoot;
		$this->originLocale = $locale;
		$this->locale = $locale;
		$this->domain = $domain;
		if($this->Dev_Level->I18N)
			$this->realDomain = $this->getLastMoFile();
		else
			$this->realDomain = $this->domain;
		$this->realLocale = $this->locale.$this->suffixLocales;
		$this->altLocales = $this->__GettextEmulator($this->realLocale)->get_list_of_locales($this->realLocale);
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
				foreach(self::$systemLocales as $lc){
					if(strpos($lc,$this->locale.'_')===0){
						if(!is_dir($this->localesRoot.'/'.$lc)){
							$cwd = getcwd();
							chdir($this->localesRoot);
							symlink($this->locale,$this->localesRoot.'/'.$lc);
							chdir($cwd);
						}
						if(false!==$p=strpos($lc,'.'))
							$lc = substr($lc,0,$p);
						if(false!==$p=strpos($lc,'@'))
							$lc = substr($lc,0,$p);
						$this->locale = $lc;
						$this->realLocale = $this->locale.$this->suffixLocales;
						$this->EMULATEGETTEXT = 0;
						break;
					}
				}
			}
		}
		$this->bind();
	}
	function _n__($singular,$plural,$number){
		return $this->ngettext($singular, $plural, $number);
	}
	function ___($msgid){
		return $this->gettext($msgid);
	}
	function _unbind(){
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
	function _bind(){
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
	function _getLocale(){
		return $this->locale;
	}
	function _getLangCode(){
		if(false!==$p=strpos($this->locale,'_')){
			return substr($this->locale,0,$p);
		}
		return $this->locale;	
	}
	function _getLastMoFile(){
		$mo = glob($this->localesRoot.'/'.$this->locale.'/LC_MESSAGES/'.$this->domain.'.*.mo');
		return !empty($mo)?substr(basename(end($mo)),0,-3):$this->domain;
	}
	
	function _setlocale(){
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		return call_user_func_array($f,func_get_args());
	}
	function _bindtextdomain(){
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		return call_user_func_array($f,func_get_args());
	}
	function _textdomain(){
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		return call_user_func_array($f,func_get_args());
	}
	function _bind_textdomain_codeset(){
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		return call_user_func_array($f,func_get_args());
	}
	
	function _gettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _ngettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _dgettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _dngettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _dcgettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _dcngettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _pgettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _dpgettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _dcpgettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _npgettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _dnpgettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
	function _dcnpgettext(){
		$this->bind();
		$f = substr(__FUNCTION__,1);
		if($this->EMULATEGETTEXT)
			$f = [$this->__GettextEmulator($this->realLocale),$f];
		$r = call_user_func_array($f,func_get_args());
		$this->unbind();
		return $r;
	}
}
Translator::initialize();