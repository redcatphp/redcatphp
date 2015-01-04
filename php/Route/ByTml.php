<?php namespace Surikat\Route;
use Route\Faceted;
class ByTml extends Faceted{
	protected $match;
	protected $dir = 'tml';
	protected $dirHook;
	function __construct($dir=null){
		if(isset($dir)){
			$this->dir = $dir;
			$this->dirHook = trim($dir,'/');
		}
	}
	function __invoke(&$uri){
		if($this->dirHook&&strpos($uri,'/'.$this->dirHook.'/')!==0)
			return;
		$params = parent::__invoke($uri);
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