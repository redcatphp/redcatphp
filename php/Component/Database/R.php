<?php namespace Surikat\Component\Database;
use Surikat\Component\Database\RedBeanPHP\Facade;
class R extends Facade{}
if(R::loadDB('default'))
	R::selectDatabase('default');