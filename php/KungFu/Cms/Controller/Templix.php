<?php
namespace KungFu\Cms\Controller;
use Templix\Templix as Templix_Templix;
use KungFu\TemplixPlugin\Toolbox;
use ObjexLoader\MutatorMagicTrait;
class Templix{
	use MutatorMagicTrait;
	protected $Route;
	protected $path;
	protected $params;
	protected $Templix;
	function __construct(){
		
	}
	function Templix(){
		return $this->Templix?:$this->Templix = new Templix_Templix();
	}
	function display($file = null, $vars = []){
		try{
			$this->Templix()->display($file, $vars);
		}
		catch(DomainException $e){
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			$this->Templix()->display($c.'.tml');
		}
		catch(DomainException $e){
			http_response_code($e->getMessage());
		}
		exit;
	}
	function __invoke($params,$path,$Route){
		$this->Route = $Route;
		$this->path = $path;
		$this->params = $params;
		
		$path = is_string($params)?$params:$params[0];
		if(method_exists($Route,'getDirHook')
			&&$hook = $Route->getDirHook()){
			$this->setDirCwd([
				$hook.'/',
				'Surikat/'.$hook.'/',
			]);
		}
		$this->Templix()->Route = $this->Route;
		$this->Templix()->onCompile(function($Tml){
			if($Tml->Template->getParent())
				return;
			$Toolbox = new Toolbox();
			$Toolbox->JsIs($Tml);
			if(!$Tml->devLevel()&Templix_Templix::DEV_TEMPLATE)
				$Toolbox->autoMIN($Tml);
		});
		$this->display($path.'.tml');
	}
}