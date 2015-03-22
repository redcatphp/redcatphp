<?php namespace Surikat\Route;
class ByTml extends Faceted{
	protected $match;
	protected $dir = 'tml';
	protected $dirFS = 'tml';
	protected $dirHook;
	protected $dirHookFS;
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
	function __invoke(&$uri){
		if($this->dirHook&&strpos($uri,$this->dirHook.'/')!==0)
			return;
		$params = parent::__invoke($uri);
		if($this->dirHook)
			$params[0] = substr($params[0],strlen($this->dirHook));
		$file = $this->dirFS.'/'.ltrim($params[0],'/').'.tml';
		if(	is_file(SURIKAT_PATH.$file)
			||is_file(SURIKAT_SPATH.$file))
			return $params;
	}
	function getDirHook(){
		return $this->dirHookFS;
	}
}