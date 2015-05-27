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
	function query($path=null,$vars=[]){
		if(!pathinfo($path,PATHINFO_EXTENSION))
			$path .= '.tml';
		if($this->setPath($path)||$this->setPath('404'))
			$this->display(null,$vars);
		else
			http_response_code(404);
	}
	function __invoke($file){
		if(is_array($file)){
			list($hook,$file) = (array)$file;
			$this->setDirCwd([$hook.'/','Surikat/'.$hook.'/']);
		}
		return $this->query($file);
	}
}