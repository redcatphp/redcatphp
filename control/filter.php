<?php namespace surikat\control; 
use surikat\control\PasswordHash;
class filter{
	const BASIC_TAGS = 'br,p,a,strong,b,i,em,img,blockquote,code,dd,dl,hr,h1,h2,h3,h4,h5,h6,label,ul,li,span,sub,sup';
	const ALL_TAGS = '!--,!DOCTYPE,a,abbr,acronym,address,applet,area,article,aside,audio,b,base,basefont,bdi,bdo,big,blockquote,body,br,button,canvas,caption,center,cite,code,col,colgroup,command,datalist,dd,del,details,dfn,dialog,dir,div,dl,dt,em,embed,fieldset,figcaption,figure,font,footer,form,frame,frameset,head,header,h1>-<h6,hr,html,i,iframe,img,input,ins,kbd,keygen,label,legend,li,link,map,mark,menu,meta,meter,nav,noframes,noscript,object,ol,optgroup,option,output,p,param,pre,progress,q,rp,rt,ruby,s,samp,script,section,select,small,source,span,strike,strong,style,sub,summary,sup,table,tbody,td,textarea,tfoot,th,thead,time,title,tr,track,tt,u,ul,var,video,wbr';
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

	/*
	$str = filter::strip_tags_basic('<p id="first"><b src="new-text" class=myclass><img src="test" width="120" height="100" /><test data-toto="ok" foo="bar">Hello <y>World</y></test></b></p>',
		array(
			'img'=>'src,width,height',
			'test'=>'data-*',
		)
	);
	*/
	static $basic_tags_map = array(
		'img'=>'src,width,height',
	);
	static $basic_attrs = array(
		
	);
	static function strip_tags_basic($str,$map=null){
		$globals_attrs = array();
		$map = $map?array_merge($map,self::$basic_tags_map):self::$basic_tags_map;
		return self::strip_tags($str,explode(',',self::BASIC_TAGS),self::$basic_attrs,$map);
	}
	static function strip_tags($str,$tags,$globals_attrs=null,$map=null){
		$total = strlen($str);
		$state = 1;
		$nstr = '';
		if($tags&&is_string($tags))
			$tags = explode(',',$tags);
		if($globals_attrs&&is_string($globals_attrs))
			$globals_attrs = explode(',',$globals_attrs);
		if($map)
			$tags = $tags?array_merge($tags,array_keys($map)):array_keys($map);
		for($i=0;$i<$total;$i++){
			$c = $str{$i};
			if($c=='<'){
				$tag = '';
				while($c!='>'){
					$c = $str{$i};
					$tag .= $c;
					$i++;
					if($c=='='){
						$sep = '';
						while($sep!='"'&&$sep!="'"){
							$sep = $str{$i};
							if($sep!='"'&&$sep!="'"&&$sep!=' '){
								$sep = ' ';
								while($c!=$sep&&$c!='/'&&$c!='>'){
									$c = $str{$i};
									$tag .= $c;
									$i++;
								}
								break;
							}
							$i++;
						}
						if($sep!=' '){
							$tag .= $sep;
							while($c!=$sep){
								$c = $str{$i};
								$tag .= $c;
								$i++;
							}
							$i-=1;
						}
					}
				}
				$i-=1;
				$tag = substr($tag,1,-1);
				if(strpos($tag,'/')===0){
					if(in_array(substr($tag,1),$tags))
						$nstr .= "<$tag>";
				}
				else{
					$e = strrpos($tag,'/')===strlen($tag)-1?'/':'';
					if($e)
						$tag = substr($tag,0,-1);
					if(($pos=strpos($tag,' '))!==false){
						$attr = substr($tag,$pos+1);
						$tag = substr($tag,0,$pos);
					}
					else
						$attr = '';
					if(!in_array($tag,$tags))
						continue;
					$allowed = isset($map[$tag])?(is_string($map[$tag])?explode(',',(string)$map[$tag]):$map[$tag]):array();
					$x = explode(' ',$attr);
					$attr = '';
					foreach($x as $_x){
						@list($k,$v) = explode('=',$_x);
						$v = trim($v,'"');
						$v = trim($v,"'");
						if($v)
							$v = "=\"$v\"";
						$ok = false;
						if(($pos=strpos($k,'-'))!==false){
							$key = substr($k,0,$pos+1).'*';
							if(in_array($key,$allowed)||($globals_attrs&&in_array($key,$globals_attrs)))
								$ok = true;
						}
						if(in_array($k,$allowed)||($globals_attrs&&in_array($k,$globals_attrs)))
							$ok = true;
						if($ok)
							$attr .= ' '.$k.$v;
					}
					$nstr .= "<$tag$attr$e>";
				}
			}
			else
				$nstr .= $c;
		}
		return $nstr;
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
