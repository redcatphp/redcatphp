<?php namespace Surikat\Dispatcher;
use Surikat\Core\Synaptic as CoreSynaptic;
class Synaptic extends Dispatcher{
	protected $pathFS;
	function __construct($pathFS=''){
		$this->pathFS = rtrim($pathFS,'/');
		if(!empty($this->pathFS))
			$this->pathFS .= '/';
	}
	function load($filename,$file){
		CoreSynaptic::load($this->pathFS.$file);
	}
}