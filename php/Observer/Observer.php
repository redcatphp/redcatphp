<?php
namespace Surikat\Observer;
trait Observer{
	function update($subject,$event=null){
		$method = 'on'.str_replace(' ', '', ucwords(str_replace('.', ' ', $event)));
		if(method_exists($this,$method)){
			$this->$method($subject);
		}
	}
}