<?php namespace Surikat\Component\DependencyInjection;
trait ConstantTrait {
	function constant($c){
		return constant(get_class($this).'::'.$c);
	}
}