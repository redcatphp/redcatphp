<?php namespace KungFu\Cms\Dispatcher;
use Unit\Dispatcher;
use Unit\Route\Extension;
use Unit\Route\ByTml;
use Unit\Route\ByPhpX;
class Backoffice extends Dispatcher{
	protected $Templix;
	public $pathFS = 'plugin/backoffice';
	function __construct(){
		$this->Authentic_Session->setName('surikat_backoffice');
		$this
			->append(new Extension('css|js|png|jpg|jpeg|gif'),new Synaptic($this->pathFS))
			->append(new ByTml('',$this->pathFS),function(){
				$this->Authentic_Auth->lockServer($this->Authentic_Auth->constant('RIGHT_MANAGE'));
				return $this->Templix();
			})
			->append(new ByPhpX('',$this->pathFS),function($paths){
				$this->Authentic_Auth->lockServer($this->Authentic_Auth->constant('RIGHT_MANAGE'));
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;
	}
	function __invoke(){
		\ObjexLoader\Container::get()
			->Unit_AutoloadPsr4
			->addNamespace('',SURIKAT.$this->pathFS.'/php')
		;
		return $this->run(func_get_arg(0));
	}
}