<?php namespace Surikat\Dispatcher;
use Surikat\Service\ServiceSynaptic;
class Synaptic extends Dispatcher{
	protected $pathFS;
	function __construct($pathFS=''){
		$this->pathFS = rtrim($pathFS,'/');
		if(!empty($this->pathFS))
			$this->pathFS .= '/';
	}
	function load($filename,$file){
		ServiceSynaptic::load($this->pathFS.$file);
	}
}