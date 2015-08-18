<?php
/*
 * DiExpand - Dependencies lazy loader
 *
 * @package Kinetic
 * @version 1.0
 * @link http://github.com/surikat/Kinetic/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
namespace Wild\Kinetic;
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