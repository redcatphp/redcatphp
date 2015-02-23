<?php
namespace Surikat\I18n{
	use Surikat\DependencyInjection\MutatorMagic;
	use Surikat\Config\Config;
	use Surikat\I18n\GettextEmulator;
	class Lang {
		use MutatorMagic;
		
		private $locale;
		private $domain;
		private $originLocale;
		private $realLocale;
		private $localesRoot;
		private $gettext;
		private $altLocales;
		private $GettextEmulator;
		private static $systemLocales;
		
		private static $registry = [];
		private static $current;
		
		protected $countryAuto = true;
		protected static $defaultLocale = 'en_US';
		protected static $defaultDomain = 'messages';
		protected static $defaultLocalesRoot;
		protected static $suffixLocales = '.utf8';
		
		static function n__($singular,$plural,$number,$lang=null,$domain=null){
			if(isset($lang)||isset($domain)){
				if(!isset($lang))
					$lang = self::current()->locale;
				if(!isset($domain))
					$domain = self::current()->domain;
				$o = self::factory($lang,$domain);
			}
			else
				$o = self::current();
			return $o->ngettext($singular, $plural, $number);
		}
		static function __($msgid,$lang=null,$domain=null){
			if(isset($lang)||isset($domain)){
				if(!isset($lang))
					$lang = self::current()->locale;
				if(!isset($domain))
					$domain = self::current()->domain;
				$o = self::factory($lang,$domain);
			}
			else
				$o = self::current();
			return $o->gettext($msgid);
		}
		static function get(){
			return self::currentLangCode();
		}
		static function currentLangCode(){
			return self::current()->getLangCode();
		}
		static function currentLocale(){
			return self::current()->getLocale();
		}
		static function current(){
			if(!isset(self::$current))
				self::set();
			return self::$current;
		}
		static function factory($locale,$domain=null){
			if(!func_num_args())
				return self::current();
			if(!isset($locale))
				$locale = static::$defaultLocale;
			if(!isset($domain))
				$domain = static::$defaultDomain;
			if(!isset(self::$registry[$locale])){
				self::$registry[$locale] = new self($locale,$domain);
			}
			return self::$registry[$locale];
		}
		private static $initialized;
		static function initialize(){
			if(self::$initialized)
				return;
			self::$initialized = true;
			static::$defaultLocalesRoot = SURIKAT_PATH.'langs';
			$tz = Config::langs('timezone');
			if(!$tz)
				$tz = @date_default_timezone_get();
			date_default_timezone_set($tz);
			exec('locale -a',self::$systemLocales);
		}
		static function set($locale=null,$domain=null){
			self::$current = self::factory($locale,$domain);
			self::$current->bind();
		}
		private function __construct($locale=null,$domain=null){
			$this->localesRoot = static::$defaultLocalesRoot;
			$this->originLocale = $locale;
			$this->locale = $locale;
			$this->domain = $domain;
			if($this->Dev_Level->I18N)
				$this->realDomain = $this->getLastMoFile();
			else
				$this->realDomain = $this->domain;
			$this->realLocale = $this->locale.static::$suffixLocales;
			$this->altLocales = GettextEmulator::get_list_of_locales($this->realLocale);
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
							$this->realLocale = $this->locale.static::$suffixLocales;
							$this->EMULATEGETTEXT = 0;
							break;
						}
					}
				}
			}
			$this->bind();
		}
		function GettextEmulator(){
			if(!isset($this->GettextEmulator))
				$this->GettextEmulator = new GettextEmulator($this->realLocale);
			return $this->GettextEmulator;
		}
		
		private static $memoryStack = [];
		function unbind(){
			array_pop(self::$memoryStack);
			$last = end(self::$memoryStack);
			if(!$last){
				$last = [
					static::$defaultLocale,
					static::$defaultLocale.static::$suffixLocales,
					static::$defaultDomain,
					static::$defaultLocalesRoot
				];
			}
			putenv('LANG='.$last[0]);
			putenv('LANGUAGE='.$last[0]);
			putenv('LC_ALL='.$last[0]);
			$this->setlocale(LC_ALL,$last[1]);
			$this->bindtextdomain($last[2],$last[3]);
			$this->textdomain($last[2]);
			$this->bind_textdomain_codeset($last[2], "UTF-8");
		}
		function bind(){
			self::$memoryStack[] = [$this->locale,$this->realLocale,$this->realDomain,$this->localesRoot];
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
		
		
		protected $text_domains = [];
		protected $default_domain = 'messages';
		protected $LC_CATEGORIES = ['LC_CTYPE', 'LC_NUMERIC', 'LC_TIME', 'LC_COLLATE', 'LC_MONETARY', 'LC_MESSAGES', 'LC_ALL'];
		protected $EMULATEGETTEXT = 1;
		function __call($f,$args){
			switch($f){
				case 'setlocale':
				case 'bindtextdomain':
				case 'textdomain':
				case 'bind_textdomain_codeset':
					if($this->EMULATEGETTEXT){
						$r = call_user_func_array([$this->GettextEmulator(),$f],$args);
					}
					else{
						$r = call_user_func_array($f,$args);
					}					
				break;
				case 'gettext':
				case 'ngettext':
				case 'dgettext':
				case 'dngettext':
				case 'dcgettext':
				case 'dcngettext':
				case 'pgettext':
				case 'dpgettext':
				case 'dcpgettext':
				case 'npgettext':
				case 'dnpgettext':
				case 'dcnpgettext':
					$this->bind();
					if($this->EMULATEGETTEXT){
						$r = call_user_func_array([$this->GettextEmulator(),$f],$args);
					}
					else{
						$r = call_user_func_array($f,$args);
					}					
					$this->unbind();
				break;
				default:
					throw new \Exception('Call to undefined Method '.$f);
				break;
			}
			return $r;
		}
	}
	Lang::initialize();
}
namespace{
	use Surikat\I18n\Lang;
	function __($msgid,$lang=null,$domain=null){
		return Lang::__($msgid,$lang,$domain);
	}
	function n__($singular,$plural,$number,$lang=null,$domain=null){
		return Lang::n__($singular, $plural, $number,$lang,$domain);
	}
}