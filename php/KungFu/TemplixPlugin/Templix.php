<?php
namespace KungFu\TemplixPlugin;
use Unit\DiContainer;
class Templix extends \Templix\Templix{
	private $di;
	function __construct($file=null,$vars=null,$options=null,DiContainer $di){
		parent::__construct($file,$vars,$options);
		$this->di = $di;
		$this->onCompile(function($tml){
			if($tml->Template->getParent())
				return;
			$toolbox = $this->di->create(__NAMESPACE__.'\Toolbox');
			$toolbox->JsIs($tml);
			if(!$tml->devLevel()&self::DEV_TEMPLATE)
				$toolbox->autoMIN($tml);
		});
	}
	function query($file=null,$vars=[]){
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.tml';
		//try{
			$this->display($file,$vars);
		//}
		//catch(\DomainException $e){
			//try{
				//$this->display($e->getMessage().'.tml',$vars);
			//}
			//catch(\DomainException $e){
				//http_response_code($e->getMessage());
			//}
		//}
	}
	function __invoke($file){
		if(is_array($file)){
			list($hook,$file) = (array)$file;
			$this->setDirCwd([$hook.'/','Surikat/'.$hook.'/']);
		}
		return $this->query($file);
	}
}