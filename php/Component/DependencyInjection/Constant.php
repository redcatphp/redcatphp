<?php namespace Surikat\DependencyInjection;
trait Constant {
	function constant($c){
		return constant(get_class($this).'::'.$c);
	}
}