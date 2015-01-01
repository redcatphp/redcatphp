<?php namespace Surikat\I18n;
use Surikat\Core\Dev;
use Surikat\Core\Config;
require_once __DIR__.'/php-gettext.php';
class Lang {
	private $locales_root;
	private $domain;
	private $locale;
	private $language;
	private $country;
	protected static $lang = 'en';
	protected static $countryCode;
	static function gettext(){
		return call_user_func_array('__',func_get_args());
	}
	static function get(){
		return static::$lang;
	}
	static function set($lg=null,$ct=null){
		if(!isset($lg))
			$lg = static::$lang;
		if(!isset($ct))
			$ct = static::$countryCode;
		static::$lang = $lg;
		static::$countryCode = $ct;
		$o = new static($lg,$ct);
		$lang = $o->locale;
		//$lang .= '.utf8';
		putenv("LANG=$lang");
		putenv("LANGUAGE=$lang");
		putenv("LC_ALL=$lang");
		T_setlocale(LC_ALL,$lang);
		T_bindtextdomain($o->domain,$o->locales_root);
		T_textdomain($o->domain);
		T_bind_textdomain_codeset($o->domain, "UTF-8");
		$tz = Config::langs('timezone');
		if(!$tz)
			$tz = @date_default_timezone_get();
		date_default_timezone_set($tz);
	}
	function __construct($lg=null,$country=null){
		$this->locales_root = SURIKAT_PATH.'langs';
		$this->language = $lg;
		$this->country = $country;
		$this->locale = strtolower($this->language);
		if(isset($this->country))
			$this->locale .= '_'.strtoupper($this->country);
		if(Dev::has(Dev::I18N))
			$this->domain = $this->getLastMoFile();
		else
			$this->domain = 'messages';
	}
	function getLastMoFile(){
		$mo = glob($this->locales_root.'/'.$this->locale.'/LC_MESSAGES/messages.*.mo');
		return !empty($mo)?substr(basename(end($mo)),0,-3):'messages';
	}
}