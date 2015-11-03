<?php
namespace RedCat\Route\Match;
class ByPhp{
	protected $dir = '';
	protected $dirFS = '';
	protected $dirs = [''];
	function __construct($dir=null,$dirFS=null){
		if(isset($dir)||isset($dirFS)){
			if(!isset($dirFS))
				$dirFS = $dir;
			$this->dir = $dir;
			$this->dirFS = $dirFS;
		}
	}
	function __invoke($uri){
		if($this->dir&&strpos($uri,$this->dir)!==0)
			return;
		$path = ltrim(substr($uri,strlen($this->dir)),'/');
		if(!$path||substr($path,-1)=='/')
			$path .= 'index.php';
		$file = $this->dirFS.'/'.$path;
		foreach($this->dirs as $d){
			if($f=realpath(($adir=$d.'/').$file))
				return [$this->dirFS,$path,$adir.$this->dirFS,$f];
		}
	}
	function setDirs($d){
		$this->dirs = (array)$d;
		foreach($this->dirs as $d){
			if($d)
				$this->dirs[$k] = rtrim($d,'/').'/';
		}
	}
	function prependDir($d){
		array_unshift($this->dirs,$d?rtrim($d,'/').'/':'');
	}
	function appendDir($d){
		$this->dirs[] = $d?rtrim($d,'/').'/':'';
	}
}