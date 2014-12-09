<?php namespace Surikat\Route;
use ArrayAccess;
class Router_ByTml extends Router_SuperURI{
	protected $match;
	protected $dir = 'tml';
	protected $dirHook;
	function __construct($dir=null){
		if(isset($dir)){
			$this->dir = $dir;
			$this->dirHook = trim($dir,'/');
		}
	}
	function match($url){
		if($this->dirHook&&strpos($url,'/'.$this->dirHook.'/')!==0)
			return;
		$params = parent::match($url);
		if($this->dirHook)
			$params[0] = substr($params[0],strlen($this->dirHook));
		$file = $this->dir.'/'.$params[0].'.tml';
		if(	is_file(SURIKAT_PATH.$file)
			||is_file(SURIKAT_SPATH.$file))
			return $params;
	}
	function getDirHook(){
		return $this->dirHook;
	}
}