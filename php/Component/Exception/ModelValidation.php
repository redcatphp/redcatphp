<?php namespace Surikat\Exception;
use Traversable;
class ModelValidation extends \Surikat\Exception\Exception{
	function getFlattenData($glue='.'){
		$data = [];
		foreach($this->getData() as $k=>$v){
			$this->recursivePoint($k,$v,$data,$glue);
		}
		return $data;
	}
	private function recursivePoint($k,$v,&$data=[],$glue='.'){
		if(is_array($v)||$v instanceof Traversable){
			foreach($v as $_k=>$_v){
				$this->recursivePoint($k.$glue.$_k,$_v,$data);
			}
		}
		else{
			$data[$k] = $v;
		}
	}
}