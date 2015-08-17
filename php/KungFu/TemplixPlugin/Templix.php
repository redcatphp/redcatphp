<?php
namespace KungFu\TemplixPlugin;
use Unit\Di;
class Templix extends \Wild\Templix\Templix{
	private $di;
	function __construct($file=null,$vars=null,
		$devTemplate=true,$devJs=true,$devCss=true,$devImg=false,
		Di $di
	){
		parent::__construct($file,$vars,$devTemplate,$devJs,$devCss,$devImg);
		$this->di = $di;
		$this->onCompile(function($tml){
			if($tml->templix->getParent())
				return;
			$toolbox = $this->di->create(__NAMESPACE__.'\Toolbox');
			$toolbox->is($tml);
			if(!$tml->devTemplate)
				$toolbox->autoMIN($tml);
		});
	}
	function query($path=null,$vars=[]){
		$vars = array_merge([
			'URI'=>$path,
		],$vars);
		if(!pathinfo($path,PATHINFO_EXTENSION))
			$path .= '.tml';
		if($this->setPath($path)||$this->setPath('404.tml'))
			$this->display(null,$vars);
		else
			http_response_code(404);
	}
	function __invoke($file){
		if(is_array($file)){
			list($hook,$file) = (array)$file;
			$this->setDirCwd([$hook.'/','surikat/'.$hook.'/']);
		}
		return $this->query($file);
	}
}