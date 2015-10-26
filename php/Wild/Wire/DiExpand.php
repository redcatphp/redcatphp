<?php
/*
 * DiExpand - Dependencies lazy loader
 *
 * @package Wire
 * @version 1.4
 * @link http://github.com/surikat/Wire/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
namespace Wild\Wire;
class DiExpand{
	private $x;
	function __construct($x,$params=[]){
		$this->x = $x;
		$this->params = $params;
	}
	function __invoke(Di $di, $share = []){
		if(is_string($this->x))
			return $di->create($this->x,$this->params,false,$share);
		else
			return call_user_func_array($this->x,$this->params);
	}
}