<?php namespace Surikat\Component\DependencyInjection;
trait MutatorFacadeTrait{
	use MutatorMagicTrait, FacadeTrait{
		FacadeTrait::__call insteadof MutatorMagicTrait;
		MutatorMagicTrait::__call as ___call;
	}
}