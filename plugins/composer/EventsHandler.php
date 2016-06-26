<?php
namespace MyApp\Composer;
class EventsHandler{
	static function setup($event){
		$GLOBALS['ioDialogRedCat'] = $event->getIO();
		$php = 'bin/artist.phar';
		$_SERVER['argv'] = $GLOBALS['argv'] = [$php,'--plugins=plugins/artist="MyApp\\Artist"','setup'];
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