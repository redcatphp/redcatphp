<?php namespace Wild\Localize;
use Wild\Templix\Markup;
class getTextExtractorTemplix extends getTextExtractor{
	protected static function parseFile($file,$sourceDir=null){
		$filename = $file;
		//if($sourceDir)
			//$filename = substr($filename,strlen($sourceDir));
		$content = file_get_contents($file);
		if(empty($content))
			return;
		$msg = '';
		$TML = new Markup($content);
		$TML('*[ni18n]')->remove();
		$TML('TEXT:hasnt(PHP)')->each(function($el)use(&$msg,$filename){
			$el = trim("$el");
			if($el)
				$msg .= "#: $filename \nmsgid ".self::quote($el)."\nmsgstr \"\" \n\n";
		});
		$TML('*')->each(function($el)use(&$msg,$filename){
			foreach($el->attributes as $k=>$v){
				$v = trim($v);
				if(strpos($k,'i18n-')===0&&$v)
					$msg .= "#: $filename \nmsgid ".self::quote($v)."\nmsgstr \"\" \n\n";
			}
		});
		return $msg;
	}
}