<?php
namespace Wild\DataMap\Validation;
class Ruler {
	// static function __callStatic($func,array $args=array()){return true;} //court-circuit
	static function unique($v,$db){
		return $this->model->id||!$db->getRow($this->model->tableName,' WHERE '.$this->col.'=?',[$v]);
	}
	static function required($v){
		return !empty($v);
	}
	static function contains($v,$arg){
		return in_array(trim(strtolower($v)), explode(chr(32), trim(strtolower($arg))));
	}
	static function tel($v){
		return preg_match("/^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$/",$v);
	}
	static function email($v){
		return filter_var($v, FILTER_VALIDATE_EMAIL);
	}
	static function maxchar($v,$arg){
		$v = strip_tags($v);
		$v = str_replace([' ',"\n","\r","\t"],'',$v);
		return self::maxlength($v,$arg);
	}
	static function minchar($v,$arg){
		$v = strip_tags($v);
		$v = str_replace([' ',"\n","\r","\t"],'',$v);
		return self::minlength($v,$arg);
	}
	static function maxlength($v,$arg){
		return strlen($v)<=(int)$arg;
	}
	static function minlength($v,$arg){
		return strlen($v)>=(int)$arg;
	}
	static function exactlength($v,$arg){
		return strlen($v)==(int)$arg;
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
	static function integer($v){
		return is_integer($v)||is_integer(filter_var($v, FILTER_VALIDATE_INT));
	}
	static function boolean($v){
		return is_bool($v)||is_bool(filter_var($v, FILTER_VALIDATE_BOOLEAN));
	}
	static function float($v){
		return is_float($v)||filter_var($v, FILTER_VALIDATE_FLOAT);
	}
	static function url($v){
		return filter_var($v, FILTER_VALIDATE_URL);
	}
	static function url_exists($v){
		$v = str_replace(['http://','https://','ftp://'],'',strtolower($v)); 
		return function_exists('checkdnsrr')?checkdnsrr($v):gethostbyname($v)!=$v;
	}
	static function ip($v){
		return filter_var($v,FILTER_VALIDATE_IP)!==FALSE;
	}
	static function name($v){
		return preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ '-])+$/i", $v)!==FALSE;
	}
	static function cc($c){
		$number = preg_replace('/\D/', '', $v);
		$number_length = strlen($v);
	  	$parity = $number_length % 2;
	 	$total = 0;
	  	for($i=0;$i<$number_length;$i++){
	    	$digit = $number[$i];
	    	if($i%2==$parity) {
	      		$digit *= 2;
	      		if($digit>9) $digit -= 9;
	    	}
	    	$total += $digit;
	  	}
		return $total%10==0;
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
	static function time(&$time,$required=false){
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
}