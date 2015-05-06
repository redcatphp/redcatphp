<?php namespace Unit\Mvc;
use ObjexLoader\MutatorMagicTrait;
use DomainException;
class Controller{
	use MutatorMagicTrait;
	protected $prefixTmlCompile = '';
	function __invoke($params,$uri,$Route){
		$path = is_string($params)?$params:$params[0];
		$this->Route = $Route;
		if(method_exists($Route,'getDirHook')
			&&$hook = $Route->getDirHook()){
			$this->Unit_Mvc_View->setDirCwd([
				$hook.'/',
				'Surikat/'.$hook.'/',
			]);
		}
		$this->Unit_Mvc_View->onCompile(function($Tml){
			if($Tml->Template->getParent())
				return;
			$this->Templix_Toolbox->JsIs($Tml);
			if(!$this->Dev_Level->VIEW)
				$this->Templix_Toolbox->autoMIN($Tml);
		});
		$this->display($path.'.tml');
	}
	function Unit_Mvc_View(){
		$View = $this->defaultDependency('Unit_Mvc_View');
		if(method_exists($View->getEngine(),'setDependency'))
			$View->getEngine()->setDependency('Unit_Mvc_Controller',$this);
		return $View;
	}
	function addPrefixTmlCompile($prefix){
		$this->prefixTmlCompile .= $prefix;
	}
	function display($file){
		$this->Unit_Mvc_View->setDirCompile('.tmp/templix/compile/'.$this->prefixTmlCompile);
		$this->Unit_Mvc_View->setDirCache('.tmp/templix/cache/'.$this->prefixTmlCompile);
		try{
			$this->Unit_Mvc_View->display($file);
		}
		catch(DomainException $e){
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			$this->Unit_Mvc_View->display($c.'.tml');
		}
		catch(DomainException $e){
			http_response_code($e->getMessage());
		}
		exit;
	}
}