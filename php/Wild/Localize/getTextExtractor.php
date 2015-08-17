<?php namespace Wild\Localize;
class getTextExtractor{
	static function parse($sources,$sourceDir=null){
		$msg = '';
		foreach((array)$sources as $source)
			if(is_dir($source))
				$msg .= self::dir($source,$sourceDir);
			else
				$msg .= static::parseFile($source,$sourceDir);
		return $msg;
	}
	static function multilineQuote($string){
        $lines = explode("\n", $string);
        $last = count($lines) - 1;

        foreach ($lines as $k => $line) {
            if ($k === $last) {
                $lines[$k] = self::quote($line);
            } else {
                $lines[$k] = self::quote($line."\n");
            }
        }

        return $lines;
    }
	protected static function quote($str){
		return '"'.str_replace(['"',"\n","\r"], ['\"','\n',''], stripslashes($str)).'"';
	}
	protected static function dir($dir,$sourceDir=null){
		$msg = '';
		$dir = rtrim($dir,'/').'/';
		$dh = opendir($dir);
		while($file=readdir($dh)){
			if($file=='.'||$file=='..')
				continue;
			$f = $dir.$file;
			if (is_dir($f))
				$msg .= self::dir($f,$sourceDir);
			else
				$msg .= static::parseFile($f,$sourceDir);
		}
		closedir($dh);
		return $msg;
	}
}