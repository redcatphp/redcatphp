<?php
namespace MyApp\Artist;
use RedCat\Artist\ArtistPlugin;
use RedCat\Framework\PHPConfig\TokenTree;
class Setup extends ArtistPlugin{
	protected $description = "Finalize installation";
	protected $args = [];
	protected $opts = ['force'];
	protected $mainDbnameDefault = "redcat-db";
	protected $gitEmailDefault = "";
	protected $gitNameDefault = "";
	protected function exec(){
		$this->runCmd('install:redcatphp');
	}
}