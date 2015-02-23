<?php namespace Surikat\DependencyInjection;
use Surikat\DependencyInjection\MutatorCall;
use Surikat\DependencyInjection\MutatorProperty;
trait MutatorMagic{
	use Mutator;
	use MutatorMagicProperty;
	use MutatorMagicCall;
}