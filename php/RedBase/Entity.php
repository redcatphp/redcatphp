<?php
namespace RedBase;
class Entity implements Observer{
	//function beforePut(){}
	//function beforeCreate(){}
	//function beforeRead(){}
	//function beforeUpdate(){}
	//function beforeDelete(){}
	//function afterPut(){}
	//function afterCreate(){}
	//function afterRead(){}
	//function afterUpdate(){}
	//function afterDelete(){}
	
	function beforeRecursive(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function beforePut(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function beforeCreate(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function beforeRead(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function beforeUpdate(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function beforeDelete(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function afterPut(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function afterCreate(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function afterRead(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function afterUpdate(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
	function afterDelete(){
		echo 'called '.$this->_type.':'.__FUNCTION__.'()<br>';
	}
}