<?php
namespace Unit\Dispatcher;
use ObjexLoader\MutatorTrait;
class Event{
	use MutatorTrait;
	protected $events = [];
	function append($event,$listener,$index=0){
		return $this->event($event,$listener,$index);
	}
	function prepend($event,$listener,$index=0){
		return $this->event($event,$listener,$index,true);
	}
	function event($event,$listener,$index=0,$prepend=false){
		if(!isset($this->events[$event]))
			$this->events[$event] = [];
		if(!isset($this->events[$event][$index]))
			$this->events[$event][$index] = [];
		if($prepend)
			array_unshift($this->events[$event][$index],$listener);
		else
			array_push($this->events[$event][$index],$listener);
		return $this;
	}
	function __invoke(){
		return $this->trigger(func_get_arg(0));
	}
	function trigger($fire,$args=[]){
		ksort($this->events);
		if(isset($this->events[$event])){
			foreach($this->events[$event] as $group){
				foreach($group as $e){
					list($event,$listener) = $e;
					if(is_array($listener)&&isset($listener[0])&&is_string($listener[0]))
						$listener = $this->getDependency(array_shift($listener),$listener);
					call_user_func_array($listener,$args);
				}
			}
		}
	}
}