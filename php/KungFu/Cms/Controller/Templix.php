<?php
namespace KungFu\Cms\Controller;
class Templix{
	protected $Dispatcher;
	function __construct($Dispatcher=null){
		$this->Dispatcher = $Dispatcher;
	}
}