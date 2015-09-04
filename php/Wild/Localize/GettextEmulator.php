<?php
namespace Wild\Localize;
use Wild\Localize\FileReader;
use Wild\Localize\domain;
use Wild\Localize\gettext_reader;
if (!defined('LC_MESSAGES'))
	define('LC_MESSAGES',5);
class GettextEmulator{
	protected $text_domains = [];
	protected $default_domain = 'messages';
	protected $LC_CATEGORIES = ['LC_CTYPE', 'LC_NUMERIC', 'LC_TIME', 'LC_COLLATE', 'LC_MONETARY', 'LC_MESSAGES', 'LC_ALL'];
	protected $CURRENTLOCALE = '';
	function __construct($locale){
		$this->locale = $locale;
	}
	function setlocale($category, $locale) {
		if(func_num_args()>2){
			$locale = func_get_args();
			array_shift($locale);
		}
		if ($locale===0){
			if ($this->CURRENTLOCALE != '')
				return $this->CURRENTLOCALE;
			else
				return $this->setlocale($category, $this->CURRENTLOCALE);
		}
		else{
			if(function_exists('setlocale')){
				if(is_array($locale)){
					foreach($locale as $lc){
						$ret = setlocale($category,$lc);
						if($ret)
							break;
					}
				}
				else{
					$ret = setlocale($category, $locale);
				}
				if(($locale==''&&!$ret) || ($locale!='' && $ret!=$locale))
					$this->CURRENTLOCALE = $locale==''?getenv('LANG'):$locale;
				else
					$this->CURRENTLOCALE = $ret;
			}
			if (array_key_exists($this->default_domain, $this->text_domains))
				unset($this->text_domains[$this->default_domain]->l10n);
			return $this->CURRENTLOCALE;
		}
	}
	static function get_list_of_locales($locale) {
		$locale_names = [];
		$lang = NULL;
		$country = NULL;
		$charset = NULL;
		$modifier = NULL;
		if ($locale) {
			if (preg_match("/^(?P<lang>[a-z]{2,3})(?:_(?P<country>[A-Z]{2}))?(?:\.(?P<charset>[-A-Za-z0-9_]+))?(?:@(?P<modifier>[-A-Za-z0-9_]+))?$/",$locale, $matches)){
				if(isset($matches["lang"]))
					$lang = $matches["lang"];
				if(isset($matches["country"]))
					$country = $matches["country"];
				if(isset($matches["charset"]))
					$charset = $matches["charset"];
				if(isset($matches["modifier"]))
					$modifier = $matches["modifier"];
				if ($modifier) {
					if ($country) {
						if ($charset)
							array_push($locale_names, "${lang}_$country.$charset@$modifier");
						array_push($locale_names, "${lang}_$country@$modifier");
					}
					elseif($charset)
						array_push($locale_names, "${lang}.$charset@$modifier");
					array_push($locale_names, "$lang@$modifier");
				}
				if ($country) {
					if($charset)
						array_push($locale_names, "${lang}_$country.$charset");
					array_push($locale_names, "${lang}_$country");
				}
				elseif($charset)
					array_push($locale_names, "${lang}.$charset");
				array_push($locale_names, $lang);
			}
			if (!in_array($locale, $locale_names))
				array_push($locale_names, $locale);
		}
		return $locale_names;
	}
	protected function getReader($domain=null, $category=5, $enable_cache=false) {
		if (!isset($domain))
			$domain = $this->default_domain;
		if (!isset($this->text_domains[$domain]->l10n)) {
			$locale = $this->setlocale(LC_MESSAGES, 0);
			$bound_path = isset($this->text_domains[$domain]->path) ?
				$this->text_domains[$domain]->path : './';
			$subpath = $this->LC_CATEGORIES[$category] ."/$domain.mo";
			$locale_names = self::get_list_of_locales($this->locale);
			$input = null;
			foreach($locale_names as $locale){
				$full_path = $bound_path . $locale . '/' . $subpath;
				if(file_exists($full_path)){
					$input = new FileReader($full_path);
					break;
				}
			}
			if (!isset($this->text_domains[$domain]))
				$this->text_domains[$domain] = new domain(); // Initialize an empty domain object.
			$this->text_domains[$domain]->l10n = new gettext_reader($input,$enable_cache);
		}
		return $this->text_domains[$domain]->l10n;
	}
	function getCodeset($domain=null){
		if (!isset($domain))
			$domain = $this->default_domain;
		return (isset($this->text_domains[$domain]->codeset))? $this->text_domains[$domain]->codeset : ini_get('mbstring.internal_encoding');
	}
	function encode($text){
		$source_encoding = mb_detect_encoding($text);
		$target_encoding = $this->getCodeset();
		if ($source_encoding != $target_encoding)
			return mb_convert_encoding($text, $target_encoding, $source_encoding);
		else
			return $text;
	}
	function bindtextdomain($domain, $path){
		if ($path[strlen($path)-1] != '/')
			$path .= '/';
		if (!array_key_exists($domain, $this->text_domains))
			$this->text_domains[$domain] = new domain();
		$this->text_domains[$domain]->path = $path;
	}
	function bind_textdomain_codeset($domain, $codeset){
		$this->text_domains[$domain]->codeset = $codeset;
	}
	function textdomain($domain){
		$this->default_domain = $domain;
	}
	function gettext($msgid){
		return $this->encode($this->getReader()->translate($msgid));
	}
	function ngettext($singular, $plural, $number){
		return $this->encode($this->getReader()->ngettext($singular, $plural, $number));
	}
	function dgettext($domain, $msgid){
		return $this->encode($this->getReader($domain)->translate($msgid));
	}
	function dngettext($domain, $singular, $plural, $number){
		return $this->encode($this->getReader($domain)->ngettext($singular, $plural, $number));
	}
	function dcgettext($domain, $msgid, $category){
		return $this->encode($this->getReader($domain)->translate($msgid));
	}
	function dcngettext($domain, $singular, $plural, $number, $category){
		return $this->encode($this->getReader($domain)->ngettext($singular, $plural, $number));
	}
	function pgettext($context, $msgid){
		return $this->encode($this->getReader()->pgettext($context, $msgid));
	}
	function dpgettext($domain, $context, $msgid){
		return $this->encode($this->getReader($domain)->pgettext($context, $msgid));
	}
	function dcpgettext($domain, $context, $msgid, $category){
		return $this->encode($this->getReader($domain)->pgettext($context, $msgid));
	}
	function npgettext($context, $singular, $plural){
		return $this->encode($this->getReader()->npgettext($context, $singular, $plural));
	}
	function dnpgettext($domain, $context, $singular, $plural){
		return $this->encode($this->getReader($domain)->npgettext($context, $singular, $plural));
	}
	function dcnpgettext($domain, $context, $singular, $plural, $category){
		return $this->encode($this->getReader($domain)->npgettext($context, $singular, $plural));
	}
}