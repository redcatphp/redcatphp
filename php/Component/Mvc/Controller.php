<?php namespace Surikat\Component\Mvc;
use Surikat\Component\Vars\ArrayObject;
use Surikat\Component\Mvc\View;
use Surikat\Component\Templator\TML;
use Surikat\Component\DependencyInjection\MutatorMagic;
class Controller{
	use MutatorMagic;
	protected $prefixTmlCompile = '';
	function __invoke($params,$uri,$Route){
		$path = is_string($params)?$params:$params[0];
		//var_dump($this);
		$this->Route = $Route;
		if(method_exists($Route,'getDirHook')
			&&$hook = $Route->getDirHook()){
			$this->Mvc_View->setDirCwd([
				$hook.'/',
				SURIKAT_LINK.$hook.'/',
			]);
		}
		$this->Mvc_View->onCompile(function($TML){
			if($TML->Template->getParent())
				return;
			$this->Templator_Toolbox->JsIs($TML);
			if(!$this->Dev_Level->VIEW)
				$this->Templator_Toolbox->autoMIN($TML);
		});
		$this->display($path.'.tml');
	}
	function Mvc_View(){
		$View = new View();
		$View->setDependency('Mvc_Controller',$this);
		return $View;
	}
	function addPrefixTmlCompile($prefix){
		$this->prefixTmlCompile .= $prefix;
	}
	function display($file){
		$this->Mvc_View->set('URI',$this->Route);
		$this->Mvc_View->setDirCompile(SURIKAT_TMP.'tml/compile/'.$this->prefixTmlCompile);
		$this->Mvc_View->setDirCache(SURIKAT_TMP.'tml/cache/'.$this->prefixTmlCompile);
		try{
			$this->Mvc_View->display($file);
		}
		catch(\Surikat\Component\Exception\View $e){
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			$this->Mvc_View->set('URI',$this->Route);
			$this->Mvc_View>display($c.'.tml');
		}
		catch(\Surikat\Component\Exception\View $e){
			$this->Http_Request->code($e->getMessage());
		}
		exit;
	}
}