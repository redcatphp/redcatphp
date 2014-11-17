<?php namespace surikat\control\i18n;
use control;
use control\FS;
use model\R;
use view\TML;
class parser{
	private static $lang_compil_path;
	static function compile_mo_from_po($dir){
		$mofile = control::$CWD.$dir.'/LC_MESSAGES/messages.mo';
		$pofile = control::$CWD.$dir.'/LC_MESSAGES/messages.po';
		if(is_file($mofile)) unlink($mofile);
		phpmo::convert($pofile,$mofile);
	}
	
	static function sources_compiler($sources){
		$potfile = control::$CWD.'langs/messages.pot';
		$add = @file_get_contents(control::$CWD.'langs/header.pot');
		$add = str_replace("{ctime}",gmdate('Y-m-d H:iO',is_file($potfile)?filemtime($potfile):time()),$add);
		$add = str_replace("{mtime}",gmdate('Y-m-d H:iO'),$add);
		// if(is_file($potfile)) @unlink($potfile);
		file_put_contents($potfile,$add);
		self::ttml2c($sources);
	}
	static function ttml2c($sources){
		self::$lang_compil_path = control::$TMP.'langs/';
		FS::mkdir(self::$lang_compil_path);
		foreach($sources as $source){
			if(is_dir($source))
				self::do_dir($source);
			else
				self::do_file($source);
		}
	}
	
	private static function fs($str){
		$str = stripslashes($str);
		$str = str_replace('"', '\"', $str);
		$str = str_replace("\n", '\n', $str);
		return $str;
	}
	private static function do_dir($dir){
		foreach(glob($dir.'/*') as $entry){
			if (is_dir($entry))
				self::do_dir($entry);
			else
				self::do_file($entry);
		}
	}
	private static function do_file($file){
		$filename = substr($file,strlen(control::$CWD));
		$potfile = control::$CWD.'langs/messages.pot';
		$outc = '';
		if($handle=fopen($potfile,'a')){
			$content = file_get_contents($file);
			if(empty($content))
				return;
				
			$TML = new TML($content);
			$TML('*[not-i18n]')->remove();
			$TML('TEXT:hasnt(PHP)')->each(function($el)use(&$outc,$filename){
				$el = trim("$el");
				if($el)
					$outc .= "#: $filename \nmsgid \"".self::fs($el)."\"\nmsgstr \"\" \n\n";
			});
			$TML('*')->each(function($el)use(&$outc,$filename){
				foreach($el->attributes as $k=>$v){
					$v = trim($v);
					if(strpos($k,'i18n-')===0&&$v){
						$outc .= "#: $filename \nmsgid \"".self::fs($v)."\"\nmsgstr \"\" \n\n";
					}
				}
			});
			
			fwrite($handle,$outc);
			fclose($handle);
		}
	}	
}