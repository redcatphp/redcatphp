<?php
namespace RedCat\Localize;
use Locale;
class AcceptLanguage{
	static function get($http_accept_language = ''){
		if(!$http_accept_language)
			$http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
		$languages = [];
		foreach (self::getLanguages($http_accept_language) as $q => $quality_group) {
			foreach ($quality_group as $lang) {
				$languages[] = $lang;
			}
		}
		return $languages;
	}
	static function detect(callable $strategy, $default, $http_accept_language = ''){
		if (!$http_accept_language)
			$http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
		foreach (self::get($http_accept_language) as $lang) {
			$result = $strategy($lang);
			if (!empty($result))
				return $result;
		}
		return $default;
	}
	static function getLanguages($http_accept_language, $resolution = 100){
		$tags = array_filter(array_map('self::parse', explode(',', $http_accept_language)));
		$grouped_tags = [];
		foreach ($tags as $tag) {
			list($q, $t) = $tag;
			$intq = (int)round($q * $resolution, 0, PHP_ROUND_HALF_UP);
			if (!isset($grouped_tags[$intq])) {
				$grouped_tags[$intq] = [];
			}
			$grouped_tags[$intq][] = $t;
		}
		krsort($grouped_tags, SORT_NUMERIC);
		return $grouped_tags;
	}
	static function parse($locale_str){
		$split = array_map('trim', explode(';', $locale_str, 2));
		if (!isset($split[0]) || strlen($split[0]) === 0)
			return [];
		if (strpos($split[0], '*') === 0) {
			$lang_tag = str_replace('*', 'xx', $split[0]);
			$is_wildcard = true;
		}
		else {
			$lang_tag = $split[0];
			$is_wildcard = false;
		}
		$lang_tag = str_replace('-*', '', $lang_tag);
		if (isset($split[1]) && strpos($split[1], 'q=') === 0) {
			$q = (float)substr($split[1], 2);
			if (!is_numeric($q) || $q <= 0 || 1 < $q)
				return [];
		}
		else{
			$q = 1.0;
		}
		if(class_exists('Locale'))
			$locale = Locale::parseLocale($lang_tag);
		else
			$locale = self::parseLocale($lang_tag);
		if($is_wildcard)
			$locale['language'] = '*';
		return [$q, $locale];
	}
	private static function sort_tags(array $a, array $b){
		if($a[0] === $b[0])
			return 0;
		return ($a[0] < $b[0]) ? -1 : 1;
	}
	static function parseLocale($lang_tag){
		$x = explode('-',$lang_tag);
		$a = [];
		foreach($x as $i=>$lg)
			$a['variant'.($i?$i:'0')] = $lg;
		return $a;
	}
}