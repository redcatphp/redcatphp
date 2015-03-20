<?php
namespace Surikat\Observer;
use SplObjectStorage;
trait Subject{
	private $__observers;
	function attach($observer){
		if(!isset($this->__observers))
			$this->__observers = new SplObjectStorage();
		$this->__observers->attach($observer);
		return $this;
	}
	function detach($observer){
		$this->__observers->detach($observer);
		return $this;
	}
	function notify($event=null){
		foreach($this->__observers as $observer){
			$observer->update($this,$event);
		}
		return $this;
	}
}