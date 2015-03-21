<?php namespace Surikat\Route;
use Route\Faceted;
use Surikat\Route\ByPhp;
class ByPhpX extends ByPhp {
	function __invoke($uri){
		if($this->dir&&strpos($uri,$this->dir)!==0)
			return;
		$path = ltrim(substr($uri,strlen($this->dir)),'/');
		if(!$path||substr($path,-1)=='/'){
			$path .= 'index.php';
			$index = true;
		}
		else{
			$index = false;
		}
		$file = $this->dirFS.'/'.$path;
		if(	is_file($f=($adir=SURIKAT_PATH).$file)
			||is_file($f=($adir=SURIKAT_SPATH).$file)
		){
			return [$this->dirFS,$path,$adir.$this->dirFS,$f];
		}
		elseif(!$index){
			$path .= '/index.php';
			$file = $this->dirFS.'/'.$path;
			if(is_file($f=($adir=SURIKAT_PATH).$file)
				||is_file($f=($adir=SURIKAT_SPATH).$file)
			){
				return [$this->dirFS,$path,$adir.$this->dirFS,$f];
			}
		}
	}
}