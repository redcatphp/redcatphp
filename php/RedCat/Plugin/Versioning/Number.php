<?php
namespace RedCat\Plugin\Versioning;
class Number{
	private $hashLength;
	private $cwd;
	private $hash;
	function __construct($cwd=null,$hashLength=9){
		if(!isset($cwd)){
			if(defined('REDCAT_CWD'))
				$cwd = REDCAT_CWD;
			else
				$cwd = getcwd();
		}
		$this->setCwd($cwd);
		$this->setHashLength($hashLength);
	}
	function setHashLength($l){
		$this->hashLength = $l;
	}
	function setCwd($c){
		$this->cwd = $c;
	}
	function get(){
		if(file_exists($file=$this->cwd.'.git/refs/heads/master')||file_exists($file=$this->cwd.'.revision')){
			$h = file_get_contents($file);
			if($this->hashLength)
				$h = substr($h,0,$this->hashLength);
			return $h;
		}
	}
	function load(){
		$this->hash = (string)$this->get();
	}
	function __toString(){
		if(!isset($this->hash))
			$this->load();
		return $this->hash;
	}
}