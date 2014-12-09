<?php namespace Surikat\I18n;
use View\TML;
class tmlGetText{
	static function parse($sources,$sourceDir=null){
		$msg = '';
		foreach((array)$sources as $source)
			if(is_dir($source))
				$msg .= self::dir($source,$sourceDir);
			else
				$msg .= self::file($source,$sourceDir);
		return $msg;
	}
	private static function fs($str){
		return str_replace(['"',"\n"], ['\"','\n'], stripslashes($str));
	}
	private static function dir($dir,$sourceDir=null){
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
				$msg .= self::file($f,$sourceDir);
		}
		closedir($dh);
		return $msg;
	}
	private static function file($file,$sourceDir=null){
		$msg = '';
		$filename = $file;
		if($sourceDir)
			$filename = substr($filename,strlen($sourceDir));
		$content = file_get_contents($file);
		if(empty($content))
			return;
		$TML = new TML($content);
		$TML('*[ni18n]')->remove();
		$TML('TEXT:hasnt(PHP)')->each(function($el)use(&$msg,$filename){
			$el = trim("$el");
			if($el)
				$msg .= "#: $filename \nmsgid \"".self::fs($el)."\"\nmsgstr \"\" \n\n";
		});
		$TML('*')->each(function($el)use(&$msg,$filename){
			foreach($el->attributes as $k=>$v){
				$v = trim($v);
				if(strpos($k,'i18n-')===0&&$v)
					$msg .= "#: $filename \nmsgid \"".self::fs($v)."\"\nmsgstr \"\" \n\n";
			}
		});
		return $msg;
	}
}