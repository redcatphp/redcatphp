<?php namespace Route;
use Route\Faceted;
use Route\ByPhp;
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
		foreach($this->dirs as $d){
			if($f=realpath(($adir=$d.'/').$file))
				return [$this->dirFS,$path,$adir.$this->dirFS,$f];
		}
		if(!$index){
			$path .= '/index.php';
			$file = $this->dirFS.'/'.$path;
			if(is_file($f=($adir=getcwd().'/').$file)
				||is_file($f=($adir=getcwd().'/Surikat/').$file)
			){
				return [$this->dirFS,$path,$adir.$this->dirFS,$f];
			}
		}
	}
}