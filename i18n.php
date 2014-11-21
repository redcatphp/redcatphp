<?php namespace surikat;
use control;
use control\Config;
require_once control::$SURIKAT_X.'/i18n/php-gettext.inc.php';
class i18n {
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
		$this->locales_root = control::$CWD.'langs';
		$this->domain = 'messages';
		$this->locale = strtolower($this->language).'_'.strtoupper($this->language);
	}
	private function updateCache(){
		$filename = $this->locales_root.'/'.$this->locale.'/LC_MESSAGES/'.$this->domain.'.mo';
		if(!is_file($filename)) return;
		$mtime = filemtime($filename);
		$filename_new = $this->locales_root.'/'.$this->locale.'/LC_MESSAGES/'.$this->domain.'_'.$mtime.'.mo';
		if(!file_exists($filename_new)){
			$dir = scandir(dirname($filename));
			foreach($dir as $file){
				if(in_array($file, ['.','..', $this->domain.'.po', $this->domain.'.mo'])) continue;
				unlink(dirname($filename).DS.$file);
			}
			copy($filename,$filename_new);
		}
		$this->domain = $this->domain.'_'.$mtime;
	}
	private function setLocale(){
		$lang = $this->locale;
		putenv("LANGUAGE=$lang");
		putenv("LC_ALL=$lang");
		T_setlocale(LC_ALL,$lang);
		T_bindtextdomain($this->domain,$this->locales_root);
		T_textdomain($this->domain);
		T_bind_textdomain_codeset($this->domain, "UTF-8");
		$tz = Config::langs('timezone');
		if(!$tz)
			$tz = @date_default_timezone_get();
		date_default_timezone_set($tz);
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