<?php namespace Surikat\I18n;
use Surikat\Core\Dev;
use Surikat\Core\Config;
require_once __DIR__.'/php-gettext.php';
class Lang {
	private $locales_root;
	private $domain;
	private $locale;
	private $language;
	protected static $lang = 'en';
	static function gettext(){
		return call_user_func_array('__',func_get_args());
	}
	static function get(){
		return static::$lang;
	}
	static function set($lg=null){
		if(!isset($lg))
			$lg = static::$lang;
		static::$lang = $lg;
		$o = new static($lg);
		$lang = $o->locale;
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
	function __construct($lg='en'){
		$this->locales_root = SURIKAT_PATH.'langs';
		$this->handle($lg);
	}
	function handle($lg){
		$this->language = $lg;
		$this->locale = strtolower($this->language).'_'.strtoupper($this->language);
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