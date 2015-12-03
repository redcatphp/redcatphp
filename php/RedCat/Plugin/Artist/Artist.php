<?php
namespace RedCat\Plugin\Artist;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
abstract class Artist extends Command{
	protected $description;
	
	protected $args = [];
	protected $requiredArgs = [];
	
	protected $opts = [];
	protected $requiredOpts = [];
	protected $shortOpts = [];
	
	protected $cwd;
	
	protected function configure(){
		$this->cwd = defined('REDCAT_CWD')?REDCAT_CWD:getcwd().'/';
		$c = explode('\\', get_class($this));
		$c = array_pop($c);
		$c = strtolower(preg_replace('/([^A-Z])([A-Z])/', '$1:$2', $c));
		$this->setName($c);
		if(isset($this->description))
			$this->setDescription($this->description);
		foreach($this->args as $k=>$v){
			if(is_integer($k)){
				$arg = $v;
				$description = '';
			}
			else{
				$arg = $k;
				$description = $v;
			}
			$mode = in_array($arg,$this->requiredArgs)?InputArgument::REQUIRED:InputArgument::OPTIONAL;
			$this->addArgument($arg,$mode,$description);
		}
		foreach($this->opts as $k=>$v){
			if(is_integer($k)){
				$opt = $v;
				$description = '';
			}
			else{
				$opt = $k;
				$description = $v;
			}
			$mode = in_array($arg,$this->requiredOpts)?InputOption::VALUE_REQUIRED:InputOption::VALUE_NONE;
			$short = isset($this->shortOpts[$opt])?$this->shortOpts[$opt]:null;
			$this->addOption($opt,$short,$mode,$description);
		}
	}
}