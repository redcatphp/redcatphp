<?php namespace Surikat\FileSystem;
abstract class INI{
	static function arrayToStr($assoc_arr,$has_sections=true,$quotes=false){
		$content = ""; 
		if($has_sections){
			$arr_tmp = $assoc_arr;
			$assoc_arr = [];
			foreach(array_keys($arr_tmp) as $key){ 
				if(!is_array($arr_tmp[$key])){
					$assoc_arr[$key] = $arr_tmp[$key];
					unset($arr_tmp[$key]);
				}
			}
			foreach(array_keys($arr_tmp) as $key){ 
				$assoc_arr[$key] = $arr_tmp[$key];
			}
			unset($arr_tmp);
			
			foreach($assoc_arr as $key=>$elem){ 
				if($quotes){
					$key='"'.$key.'"';
					// $key=addslashes($key);
				}
				if(!is_array($elem)){
					$elem = [$key=>$elem]; 
				}
				else{
					$content .= "[".$key."]\n"; 
				}
				foreach ($elem as $key2=>$elem2) { 
					if(is_array($elem2)) {
						for($i=0;$i<count($elem2);$i++) { 
							if(is_bool($elem)){
								$elem2[$i] = $elem2[$i]?'true':'false';
							}
							elseif($quotes){
								$elem2[$i]='"'.$elem2[$i].'"';
								// $elem2[$i]=addslashes($elem2[$i]);
							}
							$content .= $key2."[]=".$elem2[$i]."\n"; 
						} 
					} 
					else{
						if(is_bool($elem2)){
							$elem2 = $elem2?'true':'false';
						}
						elseif($quotes){
							$elem2 = '"'.$elem2.'"';
							// $elem2 = addslashes($elem2);
						}
						$content .= $key2."=".$elem2."\n"; 
					}
				} 
			} 
		}
		else{
			foreach($assoc_arr as $key=>$elem){ 
				if($quotes){
					$key='"'.$key.'"';
					// $key=addslashes($key);
				}
				if(is_array($elem)) { 
					for($i=0;$i<count($elem);$i++){
						if(is_bool($elem[$i])){
							$elem[$i] = $elem[$i]?'true':'false';
						}
						elseif($quotes){
							$elem[$i]='"'.$elem[$i].'"';
							// $elem[$i]=addslashes($elem[$i]);
						}
						$content .= $key."[]=".$elem[$i]."\n"; 
					} 
				}
				else{
					if(is_bool($elem)){
						$elem = $elem?'true':'false';
					}
					elseif($quotes){
						$elem='"'.$elem.'"';
						// $elem=addslashes($elem);
					}
					$content .= $key."=".$elem."\n"; 
				}
			} 
		}
		return $content;
	}
	static function write($path,$assoc_arr,$has_sections=true,$quotes=false){
		if (!$handle = fopen($path, 'w')) { 
			return false; 
		}
		$content = self::arrayToStr($assoc_arr,$has_sections,$quotes);
		if (!fwrite($handle, $content)){
			return false; 
		}
		fclose($handle); 
		return true; 
	}
}