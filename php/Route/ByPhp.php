<?php namespace Surikat\Route;
use Route\Faceted;
class ByPhp {
	protected $dir = '';
	protected $dirFS = '';
	function __construct($dir=null,$dirFS=null){
		if(isset($dir)||isset($dirFS)){
			if(!isset($dirFS))
				$dirFS = $dir;
			$this->dir = $dir;
			$this->dirFS = $dirFS;
		}
	}
	function __invoke($uri){
		if(($p=strpos($uri,$this->dir))!==0)
			return;
		$path = ltrim(substr($uri,strlen($this->dir)),'/');
		if(!$path||substr($path,-1)=='/')
			$path .= 'index';
		$file = $this->dirFS.'/'.$path;
		if(	is_file($f=($adir=SURIKAT_PATH).$file)
			||is_file($f=($adir=SURIKAT_SPATH).$file))
			return [$this->dirFS,$path,$adir.$this->dirFS,$f];
	}
}