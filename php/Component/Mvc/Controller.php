<?php namespace Surikat\Component\Mvc;
use Surikat\Component\Vars\ArrayObject;
use Surikat\Component\Mvc\View;
use Surikat\Component\Templator\TML;
use Surikat\Component\DependencyInjection\MutatorMagic;
class Controller{
	use MutatorMagic;
	protected $View;
	protected $prefixTmlCompile = '';
	function __invoke($params,$uri,$Route){
		$path = is_string($params)?$params:$params[0];
		$this->Route = $Route;
		if(method_exists($Route,'getDirHook')
			&&$hook = $Route->getDirHook()){
			$this->getView()->setDirCwd([
				$hook.'/',
				SURIKAT_LINK.$hook.'/',
			]);
		}
		$v = $this->getView();
		$v->onCompile(function($TML){
			if($TML->Template->getParent())
				return;
			$this->Templator_Toolbox->JsIs($TML);
			if(!$this->Dev_Level->VIEW)
				$this->Templator_Toolbox->autoMIN($TML);
		});
		$this->display($path.'.tml');
	}
	function setView($View){
		$this->View = $View;
	}
	function getView(){
		if(!isset($this->View)){
			$this->setView(new View());
			$this->View->setController($this);
		}
		return $this->View;
	}
	function addPrefixTmlCompile($prefix){
		$this->prefixTmlCompile .= $prefix;
	}
	function display($file){
		$v = $this->getView();
		$v->set('URI',$this->Route);
		$v->setDirCompile(SURIKAT_TMP.'tml/compile/'.$this->prefixTmlCompile);
		$v->setDirCache(SURIKAT_TMP.'tml/cache/'.$this->prefixTmlCompile);
		try{
			$v->display($file);
		}
		catch(\Surikat\Component\Exception\View $e){
			$this->error($e->getMessage());
		}
	}
	function error($c){
		try{
			$v = $this->getView();
			$v->set('URI',$this->Route);
			$v->display($c.'.tml');
		}
		catch(\Surikat\Component\Exception\View $e){
			$this->Http_Request->code($e->getMessage());
		}
		exit;
	}
}