<?php namespace Surikat;
use control;
use Control\Config;
require_once __DIR__.'/I18n/php-gettext.inc.php';
class I18n {
	private $locales_root;
	private $domain;
	private $locale;
	private $language;
	static function gettext(){
		return call_user_func_array('__',func_get_args());
	}
	private function __construct($lg){
		$this->setLang($lg);
	}
	private function setLang($lg='en'){
		$this->language = $lg;
		$this->locales_root = SURIKAT_PATH.'langs';
		$this->locale = strtolower($this->language).'_'.strtoupper($this->language);
		if(Dev::has(Dev::I18N))
			$this->domain = $this->getLastMoFile();
		else
			$this->domain = 'messages';
	}
	private function setLocale(){
		$lang = $this->locale;
		putenv("LANG=$lang");
		putenv("LANGUAGE=$lang");
		putenv("LC_ALL=$lang");
		T_textdomain('en_EN');
		T_setlocale(LC_ALL,$lang);
		T_bindtextdomain($this->domain,$this->locales_root);
		T_textdomain($this->domain);
		T_bind_textdomain_codeset($this->domain, "UTF-8");
		$tz = Config::langs('timezone');
		if(!$tz)
			$tz = @date_default_timezone_get();
		date_default_timezone_set($tz);
	}
	private function getLastMoFile(){
		$mo = glob($this->locales_root.'/'.$this->locale.'/LC_MESSAGES/messages.*.mo');
		return !empty($mo)?substr(basename(end($mo)),0,-3):'messages';
	}
	private static $o = [];
	static function o($lg='en'){
		if(!isset(self::$o[$lg]))
			self::$o[$lg] = new i18n($lg);
		return self::$o[$lg];
	}
	function __call($func,$args){
		if(strpos($func,'_')!==0)
			return call_user_func_array([$this,$func],$args);
	}
	static function __callStatic($func,$args){
		if(strpos($func,'_')!==0)
			return call_user_func_array([self::o(array_shift($args)),$func],$args);
	}
}