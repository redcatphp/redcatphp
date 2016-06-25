<?php
namespace MyApp\Composer;
class EventsHandler{
	static function setup($event){
		$GLOBALS['ioDialogRedCat'] = $event->getIO();
		$php = 'artist';
		$_SERVER['argv'] = $GLOBALS['argv'] = [$php,'install:end'];
		ob_start();
		include $php;
	}
	static function postInstallCmd($event){
		return self::setup($event);
	}
	static function postUpdateCmd($event){
		return self::setup($event);
	}
	static function postCreateProjectCmd($event){
		return self::setup($event);
	}
}