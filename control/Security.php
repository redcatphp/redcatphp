<?php namespace surikat\control; 
abstract class Security{
	static function filterDDS($val){ //protection against "dot-dot-slash"/"directory traversal" attack
		if(is_integer($val))
			return $val;
		elseif(is_array($val)){
			foreach(array_keys($val) as $k){
				if(!is_integer($k)){
					$tmp = $k;
					$k = self::directory_antitraversal($k);
					if($k!=$tmp){
						$val[$k] = $val[$tmp];
						unset($val[$tmp]);
					}
				
				}
				$val[$k] = self::directory_antitraversal($val[$k]);
			}
			return $val;
		}
		while(!(stripos($val,'./')===false&&stripos($val,'..')===false))
			$val = str_replace(['./','..'],'',$val);
		return $val;
	}
}
?>
