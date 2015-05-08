<?php namespace Templix;
use ObjexLoader\MutatorMagicTrait;
use DomainException;
class Templix extends Template {
	use MutatorMagicTrait;
	function display($file = null, $vars = []){
		try{
			parent::display($file, $vars);
		}
		catch(DomainException $e){
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			parent::display($c.'.tml');
		}
		catch(DomainException $e){
			http_response_code($e->getMessage());
		}
		exit;
	}
	var $Route;
	function __invoke($params,$uri,$Route){
		$path = is_string($params)?$params:$params[0];
		$this->Route = $Route;
		if(method_exists($Route,'getDirHook')
			&&$hook = $Route->getDirHook()){
			$this->setDirCwd([
				$hook.'/',
				'Surikat/'.$hook.'/',
			]);
		}
		$this->onCompile(function($Tml){
			if($Tml->Template->getParent())
				return;
			$Toolbox = new Toolbox();
			$Toolbox->JsIs($Tml);
			if(!$this->Dev_Level()->VIEW)
				$Toolbox()->autoMIN($Tml);
		});
		$this->display($path.'.tml');
	}
}