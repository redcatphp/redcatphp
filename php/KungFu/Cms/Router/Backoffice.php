<?php
namespace KungFu\Cms\Router;
use Authentic\Auth;
use Authentic\Session;
use KungFu\Cms\Controller\Templix;;
use Unit\Router;
use Unit\RouteMatch\Extension;
use KungFu\Cms\RouteMatch\ByTml;
use KungFu\Cms\RouteMatch\ByPhpX;
class Backoffice extends Router{
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
		global $SURIKAT;
		$SURIKAT['Autoloader']->addNamespace('',SURIKAT.$this->pathFS.'/php');
		return $this->run(func_get_arg(0));
	}
}