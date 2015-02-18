<?php namespace Surikat\DateTime;
abstract class Dates {
	static function dtp_to_datetime(&$dtp){
		if(is_array($dtp))
			foreach(array_keys($dtp) as $k)
				self::dtp_to_datetime($dtp[$k]);
		else
			$dtp = mb_substr($dtp,6,10).'-'.mb_substr($dtp,3,5).'-'.mb_substr($dtp,0,2).' '.mb_substr($dtp,11,16).':00';
		return $dtp;
	}
	static function dp_to_date_fr(&$dp){
		if(is_array($dp))
			foreach(array_keys($dp) as $k)
				self::dp_to_date_fr($dp[$k]);
		else{
			$x = explode('/',$dp);
			if(count($x)==3)
				$dp = @$x[2].'-'.@$x[1].'-'.@$x[0];
		}
		return $dp;
	}
	static function dp_to_date(&$dp){
		if(is_array($dp))
			foreach(array_keys($dp) as $k)
				self::dp_to_date($dp[$k]);
		else{
			$x = explode('/',$dp);
			if(count($x)==3)
				$dp = @$x[2].'-'.@$x[0].'-'.@$x[1];
		}
		return $dp;
	}
	static function validate_datetime($datetime,$required=false){
		if(is_array($datetime)){
			$ok = !$required;
			foreach(array_keys($datetime) as $k)
				if(($required||!empty($datetime[$k]))&&!($ok=self::validate_datetime($datetime[$k],$required)))
					return false;
			return $ok;
		}
		else
			return preg_match("#(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})#",$datetime);		
	}
	static function validate_time(&$time,$required=false){
		if(is_array($time)){
			$ok = !$required;
			foreach(array_keys($time) as $k)
				if(($required||!empty($time[$k]))&&!($ok=self::validate_time($time[$k],$required)))
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
	static function validate_dateORdatetime($date,$required=false){
		if(is_array($date)){
			$ok = !$required;
			foreach(array_keys($date) as $k)
				if(($required||!empty($date[$k]))&&!($ok=self::validate_dateORdatetime($date[$k],$required)))
					return false;
			return $ok;
		}
		else
			return preg_match('/\\A(?:^((\\d{2}(([02468][048])|([13579][26]))[\\-\\/\\s]?((((0?[13578])|(1[02]))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(3[01])))|(((0?[469])|(11))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(30)))|(0?2[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])))))|(\\d{2}(([02468][1235679])|([13579][01345789]))[\\-\\/\\s]?((((0?[13578])|(1[02]))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(3[01])))|(((0?[469])|(11))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(30)))|(0?2[\\-\\/\\s]?((0?[1-9])|(1[0-9])|(2[0-8]))))))(\\s(((0?[0-9])|(1[0-9])|(2[0-3]))\\:([0-5][0-9])((\\s)|(\\:([0-5][0-9])))?))?$)\\z/', $date);
	}
	
	static function validate_date($date,$required=false){
		if(is_array($date)){
			$ok = !$required;
			foreach(array_keys($date) as $k)
				if(($required||!empty($date[$k]))&&!($ok=self::validate_date($date[$k],$required)))
					return false;
			return $ok;
		}
		else{
			preg_match( '#^(?P<year>\d{2}|\d{4})([- /.])(?P<month>\d{1,2})\2(?P<day>\d{1,2})$#', $date, $matches );
			return $date=='0000-00-00'|| (preg_match( '#^(?P<year>\d{2}|\d{4})([- /.])(?P<month>\d{1,2})\2(?P<day>\d{1,2})$#', $date, $matches )
				   && checkdate($matches['month'],$matches['day'],$matches['year']));
		}
	}
	/* static function validate_date($date) {
		return preg_match("#(\d{4})-(\d{2})-(\d{2})#",$date);
	} */
	static function from_datetime($datetime) { //conversion d'un datetime mysql en timestamp
		list($date, $time) = explode(' ', $datetime);
		list($year, $month, $day) = explode('-', $date);
		list($hour, $minute, $second) = explode(':', $time);
		$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
		return $timestamp;
	}
	static function to_datetime($timestamp){ //conversion d'un timestamp en datetime mysql
		return date('Y-m-d H:i:s',$timestamp);
	}
	static function to_date_fr($timestamp,$time=false) { //conversion d'un timestamp en date française
		$jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
		$mois = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
		return $jours[date('N',$timestamp)-1].date(' d ',$timestamp).$mois[date('n',$timestamp)-1].date(' Y',$timestamp).($time?' à '.date('H:m:s',$timestamp):'');
	}
}
