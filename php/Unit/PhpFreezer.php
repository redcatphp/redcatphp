<?php
namespace Unit;
/*
if(!defined('SURIKAT_FREEZE_PHP')) define('SURIKAT_FREEZE_PHP',false);
if(SURIKAT_FREEZE_PHP){
	[...]
}
else{
	require_once __DIR__.'/php/Unit/Autoloader.php';
	Unit\Autoloader::register([SURIKAT_CWD.'php',SURIKAT.'php']);
}
*/
class PhpFreezer{
	/*
	if(!is_dir(SURIKAT_CWD.'.tmp/php-frozen')){
		require_once SURIKAT.'php/Unit/PhpFreezer.php';
		Unit\PhpFreezer::makeDir([SURIKAT.'php',SURIKAT_CWD.'php'],SURIKAT_CWD.'.tmp/php-frozen');
	}
	require_once SURIKAT_CWD.'.tmp/php-frozen/Unit/Autoloader.php';
	Unit\Autoloader::register(SURIKAT_CWD.'.tmp/php-frozen');
	*/
	static function makeDir($directories,$target){
		set_time_limit(0);
		foreach($directories as $directory){
			self::traverseDirectory($directory,function($relative,$content)use($target){
				$tg = $target.'/'.$relative;
				$dir = dirname($tg);
				if(!is_dir($dir))
					mkdir($dir,0777,true);
				file_put_contents($tg,$content);
			});
		}
	}
	/*
	if(!is_file(SURIKAT_CWD.'.tmp/php-frozen.phar')){
		require_once SURIKAT.'php/Unit/PhpFreezer.php';
		Unit\PhpFreezer::makePhar([SURIKAT.'php',SURIKAT_CWD.'php'],SURIKAT_CWD.'.tmp/php-frozen.phar','Surikat',"require_once 'phar://Surikat/Unit/Autoloader.php'; Unit\Autoloader::register('phar://Surikat');");
	}
	require_once SURIKAT_CWD.'.tmp/php-frozen.phar';
	*/
	static function makePhar($directories,$target,$alias=null,$stub=''){
		$dir = dirname($target);
		if(!is_dir($dir))
			mkdir($dir,0777,true);
		set_time_limit(0);
		$phar = new \Phar($target,0,$alias);
		$phar->startBuffering();
		$phar->setStub('<?php Phar::mapPhar('.($alias?"'$alias'":'').'); '.$stub.' __HALT_COMPILER();');
		foreach($directories as $directory){
			self::traverseDirectory($directory,function($relative,$content)use($phar){
				$phar->addFromString($relative,$content);
			});
		}
		$phar->stopBuffering();
	}
	private static function traverseDirectory($dir,$callback,$directory=null){
		$dir = rtrim($dir,'/').'/';
		if($directory===null)
			$directory = $dir;
		if(is_dir($dir)){
			$dh = opendir($dir);
			if($dh){
				while($f=readdir($dh)){
					if($f=='.'||$f=='..')
						continue;
					$file = $dir.$f;
					if(is_dir($file)){
						self::traverseDirectory($file,$callback,$directory);
					}
					else{
						$tg = substr($file,strlen($directory));
						$content = file_get_contents($file);
						if(strtolower(pathinfo($file,PATHINFO_EXTENSION))=='php')
							$content = self::minifyPhp($content);
						call_user_func($callback,$tg,$content);
					}
				}
				closedir($dh);
			}
		}
	}
	static function minifyPhp($text){
		$ntokens = [];
		$tokens = token_get_all(trim($text));
		foreach($tokens as $t){
			if(!is_array($t))
				$t = [-1, $t];
			if($t[0] == T_COMMENT || $t[0] == T_DOC_COMMENT)
				continue;
			if($t[0] == T_WHITESPACE)
				continue;
			$ntokens[] = $t;
		}
		for($i = 0; $i < count($ntokens) - 1; $i++) {
			if($ntokens[$i][0] == T_PUBLIC){
				if($ntokens[$i-1][0] == T_STATIC){
					$ntokens[$i] = $ntokens[$i + 1][1][0] == '$' ? ["", ""] : [-1, ""];
				}
				else{
					$ntokens[$i] = $ntokens[$i + 1][1][0] == '$' ? [T_VAR, "var"] : [-1, ""];
				}
			}
		}
		$result = "";
		foreach($ntokens as $t) {
			$text = $t[1];
			if(!strlen($text))
				continue;
			$l = strlen($result)-1;
			if(preg_match("~^\\w\\w$~", (isset($result[$l])?$result[$l]:'').$text[0]))
				$result .= " ";
			$result .= $text;
		}
		$result = str_replace('?><?php','',$result);
		return $result;
	}
}