<?php namespace Surikat\Dispatcher;
use Surikat\Core\HTTP;
//use Surikat\Core\Exception;
use Surikat\Dispatcher\Synaptic;
use Surikat\Route\ByTml;
use Surikat\Route\ByPhp;
use Surikat\Route\Extension;
class Backoffice extends ViewController{
	protected $pathFS = 'backoffice';
	function __construct(){
		$this->setHooks();
	}
	function setHooks(){
		$this
			->append(new Extension('css|js|png|jpg|jpeg|gif'), [new Synaptic($this->pathFS),'load'])
			->append(new ByTml('',$this->pathFS),$this)
			->append(new ByPhp('',$this->pathFS),function($paths){
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;		
	}
}