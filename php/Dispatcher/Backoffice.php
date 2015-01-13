<?php namespace Surikat\Dispatcher;
use Route\ByTml;
use Route\ByPhp;
use Route\I18n;
class Backoffice extends ViewController{
	protected $pathFS = 'backoffice';
	function __construct(){
		$this->setHooks();
	}
	function setHooks(){
		$this
			->append(new ByTml('',$this->pathFS),$this)
			->append(new ByPhp('',$this->pathFS),function($paths){
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;		
	}
}