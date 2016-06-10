<?php
namespace MyApp\Composer;
class EventsHandler{
	static function setup($event){
		$GLOBALS['ioDialogRedCat'] = $event->getIO();
		$php = 'packages/redcatphp/redcatphp/artist';
		$_SERVER['argv'] = $GLOBALS['argv'] = [$php,'install:end'];
		include $php;
	}
	static function postUpdateCmd($event){
		return self::setup($event);
	}
	static function postCreateProjectCmd($event){
		return self::setup($event);
	}
}