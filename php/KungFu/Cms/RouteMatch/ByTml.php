<?php namespace KungFu\Cms\RouteMatch;
class ByTml {
	protected $match;
	protected $dir = 'template';
	protected $dirFS = 'template';
	protected $dirHook;
	protected $dirHookFS;
	protected $dirs = ['','Surikat/'];
	function __construct($dir=null,$dirFS=null){
		if(isset($dir)||isset($dirFS)){
			if(!isset($dirFS))
				$dirFS = $dir;
			$this->dir = $dir;
			$this->dirFS = $dirFS;
			$this->dirHook = trim($dir,'/');
			$this->dirHookFS = trim($dirFS,'/');
		}
	}
	function __invoke($uri){
		if($this->dirHook&&strpos($uri,$this->dirHook.'/')!==0)
			return;
		if($this->dirHook)
			$uri = substr($uri,strlen($this->dirHook));
		$file = $this->dirFS.'/'.ltrim($uri,'/').'.tml';
		foreach($this->dirs as $d){
			if(	is_file($d.$file) )
				return $uri;
		}
	}
	function getDirHook(){
		return $this->dirHookFS;
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