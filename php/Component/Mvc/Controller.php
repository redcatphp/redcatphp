<?php namespace Surikat\Component\Mvc;
use Surikat\Component\Vars\ArrayObject;
use Surikat\Component\Templator\Template;
use Surikat\Component\Templator\TML;
use Surikat\Component\DependencyInjection\MutatorMagic;
class Controller{
	use MutatorMagic;
	protected $Router;
	protected $View;
	protected $prefixTmlCompile = '';
	function __invoke($params,$uri,$Router){
		$path = is_string($params)?$params:$params[0];
		$this->Router = $Router;
		if(method_exists($Router,'getDirHook')
			&&$hook = $Router->getDirHook()){
			$this->getView()->setDirCwd([
				$hook.'/',
				SURIKAT_LINK.$hook.'/',
			]);
		}
		$v = $this->getView();
		$v->onCompile(function($TML){
			if($TML->Template->getParent())
				return;
			if(!isset($TML->childNodes[0])||$TML->childNodes[0]->namespace!='Presenter')
				$TML->prepend('<Presenter:Presenter uri="static" />');
			$this->Templator_Toolbox->JsIs($TML);
			if(!$this->Dev_Level->VIEW)
				$this->Templator_Toolbox->autoMIN($TML);
		});
		$this->display($path.'.tml');
	}
	function setRouter($Router){
		$this->Router = $Router;
	}
	function getRouter(){
		return $this->Router;
	}
	function setView($View){
		$this->View = $View;
	}
	function getView(){
		if(!isset($this->View)){
			$this->setView(new Template());
			$this->View->setController($this);
		}
		return $this->View;
	}
	function addPrefixTmlCompile($prefix){
		$this->prefixTmlCompile .= $prefix;
	}
	function display($file){
		$v = $this->getView();
		$v->set('URI',$this->getRouter());
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
			$v->set('URI',$this->getRouter());
			$v->display($c.'.tml');
		}
		catch(\Surikat\Component\Exception\View $e){
			$this->Http_Request->code($e->getMessage());
		}
		exit;
	}
}