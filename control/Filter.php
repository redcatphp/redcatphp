<?php namespace surikat\control; 
use surikat\control\PasswordHash;
class Filter{
	static function trim($v){ return trim($v); }
	static function rmpunctuation($v){
		return preg_replace("/(?![.=$'€%-])\p{P}/u", '', $v);
	}
	static function sanitize_string($v){
		return filter_var($v, FILTER_SANITIZE_STRING);
	}
	static function urlencode($v){
		return filter_var($v, FILTER_SANITIZE_ENCODED);
	}
	static function htmlencode($v){
		return filter_var($v, FILTER_SANITIZE_SPECIAL_CHARS);
	}
	static function sanitize_email($v){
		return filter_var($v, FILTER_SANITIZE_EMAIL);
	}
	static function sanitize_numbers($v){
		return filter_var($v, FILTER_SANITIZE_NUMBER_INT);
	}
	static function hash($v){
		return PasswordHash::make($v);
	}
	static function dpToDate($v){
		return self::dp_to_date($v);
	}
	static function strip_tags($v,$allow=null){
		return strip_tags($v,$allow);
	}
	static function strip_tags_content($v, $tags = '',$invert=FALSE){
		preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
		$tags = array_unique($tags[1]);
		if(is_array($tags)&&(count($tags)>0)) return preg_replace($invert==FALSE?'@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si':'@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si','',$v);
		elseif($invert==FALSE) return preg_replace('@<(\w+)\b.*?>.*?</\1>@si','',$v);
	}
	static function strip_basic_tags_content($v, $tags = '',$invert=FALSE){
		return $this->strip_tags_content($v,self::BASIC_TAGS,$invert);
	}
	// static function allowAllTags($v){
		// memo all attributes: accept accept-charset accesskey action align alt async autocomplete autofocus autoplay bgcolor border buffered challenge charset checked cite class code codebase color cols colspan content contenteditable contextmenu controls coords data data-* datetime default defer dir dirname disabled download draggable dropzone enctype for form headers height hidden high href hreflang http-equiv icon id ismap itemprop keytype kind label lang language list loop low manifest max maxlength media method min multiple name novalidate open optimum pattern ping placeholder poster preload pubdate radiogroup readonly rel required reversed rows rowspan sandbox spellcheck scope scoped seamless selected shape size sizes span src srcdoc srclang start step style summary tabindex target title type usemap value width wrap
		// $all_tags = "<!--> <!DOCTYPE> <a> <abbr> <acronym> <address> <applet> <area> <article> <aside> <audio> <b> <base> <basefont> <bdi> <bdo> <big> <blockquote> <body> <br> <button> <canvas> <caption> <center> <cite> <code> <col> <colgroup> <command> <datalist> <dd> <del> <details> <dfn> <dialog> <dir> <div> <dl> <dt> <em> <embed> <fieldset> <figcaption> <figure> <font> <footer> <form> <frame> <frameset> <head> <header> <h1> - <h6> <hr> <html> <i> <iframe> <img> <input> <ins> <kbd> <keygen> <label> <legend> <li> <link> <map> <mark> <menu> <meta> <meter> <nav> <noframes> <noscript> <object> <ol> <optgroup> <option> <output> <p> <param> <pre> <progress> <q> <rp> <rt> <ruby> <s> <samp> <script> <section> <select> <small> <source> <span> <strike> <strong> <style> <sub> <summary> <sup> <table> <tbody> <td> <textarea> <tfoot> <th> <thead> <time> <title> <tr> <track> <tt> <u> <ul> <var> <video> <wbr>";
		// $all_tags = explode(' ',$all_tags);
		// $all_tags = implode('',$all_tags);
		// return strip_tags($v, $all_tags);
	// }
	static function basic_tags($v){
		//all tags: !-- !DOCTYPE a abbr acronym address applet area article aside audio b base basefont bdi bdo big blockquote body br button canvas caption center cite code col colgroup command datalist dd del details dfn dialog dir div dl dt em embed fieldset figcaption figure font footer form frame frameset head header h1 - h6 hr html i iframe img input ins kbd keygen label legend li link map mark menu meta meter nav noframes noscript object ol optgroup option output p param pre progress q rp rt ruby s samp script section select small source span strike strong style sub summary sup table tbody td textarea tfoot th thead time title tr track tt u ul var video wbr
		return strip_tags($v,self::BASIC_TAGS);
	}
	static function multi_bin($v){
		if(is_array($v)){
			$binary = 0;
			foreach($v as $bin)
				$binary |= (int)$bin;
			return $binary;
		}
		return (int)$v;
	}
	static function dtp_to_datetime(&$dtp){
		if(is_array($dtp))
			foreach(array_keys($dtp) as $k)
				self::dtp_to_datetime($dtp[$k]);
		else
			$dtp = mb_substr($dtp,6,10).'-'.mb_substr($dtp,3,5).'-'.mb_substr($dtp,0,2).' '.mb_substr($dtp,11,16).':00';
		return $dtp;
	}
	static function dp_to_date(&$dp){
		if(is_array($dp))
			foreach(array_keys($dp) as $k)
				self::dp_to_date($dp[$k]);
		else{
			$x = explode('/',$dp);
			if(count($x)==3){
				$dp = @$x[2].'-'.@$x[0].'-'.@$x[1];
			}
		}
		return $dp;
	}
	static function validate_datetime($datetime) {
		if(is_array($datetime)){
			$ok = false;
			foreach(array_keys($datetime) as $k)
				if(!($ok=self::validate_datetime($datetime[$k])))
					return false;
			return $ok;
		}
		else
			return preg_match("#(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})#",$datetime);
		
	}
	static function validate_time(&$time) {
		if(is_array($time)){
			$ok = false;
			foreach(array_keys($time) as $k)
				if(!($ok=self::validate_time($time[$k])))
					return false;
			return $ok;
		}
		else{
			if(mb_strlen($time)==5)
				$time .= ':00';
			$xp = explode(':',$time);
			$hour = (int)@$xp[0];
			$minute = (int)@$xp[1];
			$second = (int)@$xp[2];
			return $hour>-1&&$hour<24&&$minute>-1&&$minute<60&&$second>-1&&$second<60;
		}
	}
	static function validate_dateORdatetime($date){
		if(is_array($date)){
			$ok = false;
			foreach(array_keys($date) as $k)
				if(!($ok=self::validate_dateORdatetime($date[$k])))
					return false;
			return $ok;
		}
		else
			return preg_match('/\\A(?:^((\\d{2}(([02468][048])|([13579][26]))[\\-\\/\\s]?((((0?[13578])|(1[02]))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(3[01])))|(((0?[469])|(11))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(30)))|(0?2[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])))))|(\\d{2}(([02468][1235679])|([13579][01345789]))[\\-\\/\\s]?((((0?[13578])|(1[02]))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(3[01])))|(((0?[469])|(11))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(30)))|(0?2[\\-\\/\\s]?((0?[1-9])|(1[0-9])|(2[0-8]))))))(\\s(((0?[0-9])|(1[0-9])|(2[0-3]))\\:([0-5][0-9])((\\s)|(\\:([0-5][0-9])))?))?$)\\z/', $date);
	}
	static function validate_date($date){
		if(is_array($date)){
			$ok = false;
			foreach(array_keys($date) as $k)
				if(!($ok=self::validate_date($date[$k])))
					return false;
			return $ok;
		}
		else
			return $date=='0000-00-00'|| (preg_match( '#^(?P<year>\d{2}|\d{4})([- /.])(?P<month>\d{1,2})\2(?P<day>\d{1,2})$#', $date, $matches )
				   && checkdate($matches['month'],$matches['day'],$matches['year']));
	}
	/*
	static function validate_date($date) {
		return preg_match("#(\d{4})-(\d{2})-(\d{2})#",$date);
	}
	*/
	static function from_datetime($datetime) {
		list($date, $time) = explode(' ', $datetime);
		list($year, $month, $day) = explode('-', $date);
		list($hour, $minute, $second) = explode(':', $time);
		$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
		return $timestamp;
	}
	static function to_datetime($timestamp) {
		return date('Y-m-d H:i:s',$timestamp);
	}
	static function to_date_fr($timestamp,$time=false) {
		$jours = array('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche');
		$mois = array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');
		return $jours[date('N',$timestamp)-1].date(' d ',$timestamp).$mois[date('n',$timestamp)-1].date(' Y',$timestamp).($time?' à '.date('H:m:s',$timestamp):'');
	}
}
?>
