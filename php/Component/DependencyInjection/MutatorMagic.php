<?php namespace Surikat\Component\DependencyInjection;
use Surikat\Component\DependencyInjection\MutatorCall;
use Surikat\Component\DependencyInjection\MutatorProperty;
trait MutatorMagic{
	use Mutator;
	use MutatorMagicProperty;
	use MutatorMagicCall;
}