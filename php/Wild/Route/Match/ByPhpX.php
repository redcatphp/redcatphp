<?php
namespace Wild\Route\Match;
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
			if(($f=realpath(($adir=$d?$d.'/':'').$file))&&is_file($f))
				return [$this->dirFS,$path,$adir.$this->dirFS,$f];
		}
		if(!$index){
			$path .= '/index.php';
			$file = $this->dirFS.'/'.$path;
			foreach($this->dirs as $d){
				if(($f=realpath(($adir=$d.'/').$file))&&is_file($f)){
					return [$this->dirFS,$path,$adir.$this->dirFS,$f];
				}
			}
		}
	}
}