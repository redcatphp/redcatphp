<?php namespace surikat\control; 
abstract class FS {
	static function recurse($file,$arg,$pattern=null,$asc=null,&$ret=array(),$skiplink=null){
		foreach(glob($file.'/'.($pattern?$pattern:'*')) as $f){
			if($skiplink&&is_link($f)){
				continue;
			}
			elseif($asc){
				$ret[] = call_user_func($arg,$f);
				if(is_dir($f))
					$ret[] = self::recurse($f,$arg);
			}
			else{
				if(is_dir($f))
					$ret[] = self::recurse($f,$arg);
				$ret[] = call_user_func($arg,$f);
			}
		}
		return $ret;
	}
	static function mkdir($file,$isFile=null){
		$x = explode('/',$file);
		if($isFile)
			array_pop($x);
		$dir = '';
		foreach($x as $d)
			if(!is_dir($dir=$dir.$d.'/')&&!@mkdir($dir))
				throw new \Exception('Please run that command in terminal or do it manually: sudo mkdir "'.$dir.'" -m 0777');
					
	}
	static function humanSize($bytes,$decimals=2){
		$sz = 'BKMGTP';  
		$factor = floor((strlen($bytes) - 1) / 3);  
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];  
    }
    static function get_absolute_path($path) {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}
?>
