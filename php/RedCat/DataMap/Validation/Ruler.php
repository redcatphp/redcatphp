<?php
namespace RedCat\DataMap\Validation;
class Ruler {
	// static function __callStatic($func,array $args=array()){return true;} //court-circuit
	static function unique($v,$db){
		return $this->model->id||!$db->getRow($this->model->tableName,' WHERE '.$this->col.'=?',[$v]);
	}
	static function required($v){
		if(is_null($value))
			return false;
		elseif(is_string($value)&&trim($value)==='')
			return false;
		return true;
	}
	static function contains($v,$arg){
		return in_array(trim(strtolower($v)), explode(chr(32), trim(strtolower($arg))));
	}
	static function tel($v){
		return preg_match("/^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$/",$v);
	}
	static function email($v){
		return filter_var($v, \FILTER_VALIDATE_EMAIL);
	}
	static function charMax($v,$arg){
		$v = strip_tags($v);
		$v = str_replace([' ',"\n","\r","\t"],'',$v);
		return self::maxlength($v,$arg);
	}
	static function charMin($v,$arg){
		$v = strip_tags($v);
		$v = str_replace([' ',"\n","\r","\t"],'',$v);
		return self::minlength($v,$arg);
	}
	static function lengthMax($v,$arg){
		return self::strlen($v)<=(int)$arg;
	}
	static function lengthMin($v,$arg){
		return self::strlen($v)>=(int)$arg;
	}
	static function lengthExact($v,$arg){
		return self::strlen($v)==(int)$arg;
	}
	static function lengthBetween($value, $min, $max){
		$length = self::strlen($value);
		return $length >= $min && $length <= $max;
	}
	static function alpha($v){
		return preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i",$v)!==FALSE;
	}
	static function alpha_numeric($v){
		return preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i",$v)!==FALSE;
	}
	static function alpha_dash($v){
		return preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i",$v)!==FALSE;
	}
	static function numeric($v){
		return is_numeric($v);
	}
	static function maximum($v,$r){
		return (float)$v<=(float)$r;
	}
	static function minimum($v,$r){
		return (float)$v>=(float)$r;
	}
	static function bigMaximum($v,$r){
		if(function_exists('bccomp'))
			return !(bccomp($v, $r, 14) == 1);
		else
			return $r >= $v;
	}
	static function bigMinimum($v,$r){
		if(function_exists('bccomp'))
			return !(bccomp($r, $v, 14) == 1);
		else
			return $r <= $v;
	}
	static function integer($v){
		return filter_var($value, \FILTER_VALIDATE_INT)!==false;
	}
	static function boolean($v){
		return is_bool($v)||is_bool(filter_var($v, \FILTER_VALIDATE_BOOLEAN));
	}
	static function float($v){
		return is_float($v)||filter_var($v, \FILTER_VALIDATE_FLOAT);
	}
	static function url($v){
		return filter_var($v, \FILTER_VALIDATE_URL);
	}
	static function url_exists($v){
		$v = str_replace(['http://','https://','ftp://'],'',strtolower($v)); 
		return function_exists('checkdnsrr')?checkdnsrr($v):gethostbyname($v)!=$v;
	}
	static function ip($v){
		return filter_var($v,\FILTER_VALIDATE_IP)!==FALSE;
	}
	static function name($v){
		return preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ '-])+$/i", $v)!==FALSE;
	}
	//static function cc($c){
		//$number = preg_replace('/\D/', '', $v);
		//$number_length = strlen($v);
	  	//$parity = $number_length % 2;
	 	//$total = 0;
	  	//for($i=0;$i<$number_length;$i++){
			//$digit = $number[$i];
			//if($i%2==$parity) {
		  		//$digit *= 2;
		  		//if($digit>9) $digit -= 9;
			//}
			//$total += $digit;
	  	//}
		//return $total%10==0;
	//}
	static function creditCard($value,$cards=null){
		$numberIsValid = function () use ($value) {
			$number = preg_replace('/[^0-9]+/', '', $value);
			$sum = 0;
			$strlen = strlen($number);
			if($strlen < 13)
				return false;
			for ($i = 0; $i < $strlen; $i++) {
				$digit = (int) substr($number, $strlen - $i - 1, 1);
				if ($i % 2 == 1) {
					$sub_total = $digit * 2;
					if ($sub_total > 9)
						$sub_total = ($sub_total - 10) + 1;
				} else {
					$sub_total = $digit;
				}
				$sum += $sub_total;
			}
			if ($sum > 0 && $sum % 10 == 0)
				return true;
			return false;
		};

		if ($numberIsValid()) {
			if(!isset($cards)){
				return true;
			}
			else{
				$cardRegex = array(
					'visa'		  => '#^4[0-9]{12}(?:[0-9]{3})?$#',
					'mastercard'	=> '#^5[1-5][0-9]{14}$#',
					'amex'		  => '#^3[47][0-9]{13}$#',
					'dinersclub'	=> '#^3(?:0[0-5]|[68][0-9])[0-9]{11}$#',
					'discover'	  => '#^6(?:011|5[0-9]{2})[0-9]{12}$#',
				);

				if(isset($cards)){
					foreach ($cards as $card) {
						if(in_array($card, array_keys($cardRegex))&&preg_match($cardRegex[$card], $value)===1)
							return true;
					}
				}
				else{
					foreach($cardRegex as $regex){
						if(preg_match($regex, $value)===1)
							return true;
					}
				}
			}
		}
		return false;
	}
	static function validDate($value){
		$isDate = false;
		if ($value instanceof \DateTime)
			$isDate = true;
		else
			$isDate = strtotime($value) !== false;
		return $isDate;
	}
	static function dateFormat($value, $format){
		$parsed = date_parse_from_format($format, $value);
		return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
	}
	static function dateBefore($value, $before){
		$vtime = ($value instanceof \DateTime) ? $value->getTimestamp() : strtotime($value);
		$ptime = ($before instanceof \DateTime) ? $before->getTimestamp() : strtotime($before);
		return $vtime < $ptime;
	}
	static function dateAfter($value, $after){
		$vtime = ($value instanceof \DateTime) ? $value->getTimestamp() : strtotime($value);
		$ptime = ($after instanceof \DateTime) ? $after->getTimestamp() : strtotime($after);
		return $vtime > $ptime;
	}
	static function date($date,$required=false){
		if(is_array($date)){
			$ok = !$required;
			foreach(array_keys($date) as $k)
				if(($required||!empty($date[$k]))&&!($ok=self::date($date[$k],$required)))
					return false;
			return $ok;
		}
		else{
			preg_match( '#^(?P<year>\d{2}|\d{4})([- /.])(?P<month>\d{1,2})\2(?P<day>\d{1,2})$#', $date, $matches );
			return $date=='0000-00-00'|| (preg_match( '#^(?P<year>\d{2}|\d{4})([- /.])(?P<month>\d{1,2})\2(?P<day>\d{1,2})$#', $date, $matches )
				   && checkdate($matches['month'],$matches['day'],$matches['year']));
		}
	}
	static function time($time,$required=false){
		if(is_array($time)){
			$ok = !$required;
			foreach(array_keys($time) as $k)
				if(($required||!empty($time[$k]))&&!($ok=self::time($time[$k],$required)))
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
	static function equals($one,$two,$strict=false){
		return $strict?$one===$two:$on==$two;
	}
	static function differents($one,$two,$strict=false){
		return $strict?$one!==$two:$on!=$two;
	}
	static function isArray($a){
		return is_array($a);
	}
	static function inArray($v,$a,$s=false){
		return in_array($v,$a,$s);
	}
	static function notInArray($v,$a,$s=false){
		return !in_array($v,$a,$s);
	}
	static function inString($v, $str){
		if(!is_string($str)||!is_string($v))
			return false;
		return (strpos($v,$str)!==false);
	}
	static function isInstanceOf($value, $class){
		$isInstanceOf = false;
		if (is_object($value)) {
			if(is_object($class) && $value instanceof $class)
				$isInstanceOf = true;
			if (get_class($value) === $class)
				$isInstanceOf = true;
		}
		if(is_string($value)&&is_string($class)&&get_class($value)===$class)
			$isInstanceOf = true;
		return $isInstanceOf;
	}
	static function regex($v, $regex){
		return preg_match($regex, $v);
	}
	protected static function stringLength($value){
		return function_exists('mb_strlen')?mb_strlen($value):strlen($value);
	}
}