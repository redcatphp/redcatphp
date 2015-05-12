<?php namespace KungFu\Cms\Dispatcher;
use Authentic\Auth;
use Authentic\Session;
use KungFu\Cms\Controller\Templix;;
use Unit\Dispatcher;
use Unit\Route\Extension;
use KungFu\Cms\Route\ByTml;
use KungFu\Cms\Route\ByPhpX;
class Backoffice extends Dispatcher{
	protected $Templix;
	public $pathFS = 'plugin/backoffice';
	function __construct(){
		$this
			->append(new Extension('css|js|png|jpg|jpeg|gif'),new Synaptic($this->pathFS))
			->append(new ByTml('',$this->pathFS),function(){
				$this->lock();
				return $this->Templix();
			})
			->append(new ByPhpX('',$this->pathFS),function($paths){
				$this->lock();
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;
	}
	function Templix(){
		return $this->Templix?:$this->Templix = new Templix();
	}
	function lock(){
		$Session = new Session();
		$Session->setName('surikat_backoffice');
		$Auth = new Auth($Session);
		$Auth->lockServer(Auth::RIGHT_MANAGE);
	}
	function __invoke(){
		\ObjexLoader\Container::get()
			->Unit_AutoloadPsr4
			->addNamespace('',SURIKAT.$this->pathFS.'/php')
		;
		return $this->run(func_get_arg(0));
	}
}