<?php
namespace KungFu\Cms\Controller;
use Templix\Templix as Templix_Templix;
class Templix{
	protected $Dispatcher;
	protected $Route;
	protected $path;
	protected $params;
	protected $Templix;
	function __construct($Dispatcher=null){
		$this->Dispatcher = $Dispatcher;
	}
	function Templix(){
		return $this->Templix?:$this->Templix = new Templix_Templix();
	}
	function __invoke($params,$path,$Route){
		$this->Route = $Route;
		$this->path = $path;
		$this->params = $params;
		return $this->Templix();
	}
}