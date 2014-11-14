<?php namespace surikat\control\i18n;
use surikat\control;
require_once __DIR__.'/php-gettext.php';
class i18n {
	private $locales_root;
	private $domain;
	private $locale;
	private $language;
	private function set($lg='en'){
		$this->language = $lg;
		$this->locales_root = control::$CWD.'langs';
		$this->domain = 'messages';
		$this->locale = strtolower($this->language).'_'.strtoupper($this->language);
	}
	private function update_cache(){
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
	private function handle(){
		$lang = $this->locale;
		putenv("LANGUAGE=$lang");
		putenv("LC_ALL=$lang");
		T_setlocale(LC_ALL,$lang);
		T_bindtextdomain($this->domain,$this->locales_root);
		T_textdomain($this->domain);
		T_bind_textdomain_codeset($this->domain, "UTF-8");
		date_default_timezone_set('Europe/Paris');
		//setlocale(LC_TIME, $lang);
	}
	private static $singleton;
	static function singleton(){
		if(!isset(self::$singleton))
			self::$singleton = new i18n();
		return self::$singleton;
	}
	function __call($func,$args){
		if(strpos($func,'_')!==0)
			return call_user_func_array([$this,$func],$args);
	}
	static function __callStatic($func,$args){
		if(strpos($func,'_')!==0)
			return call_user_func_array([self::singleton(),$func],$args);
	}
}