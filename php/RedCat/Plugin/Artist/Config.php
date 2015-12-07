<?php
namespace RedCat\Plugin\Artist;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RedCat\Plugin\PHPConfig\TokenTree;
class Config extends Artist{
	protected $description = "Update .config.php file at root of application";

	protected $args = [
		'key'=>"The key in '$' associative array, recursive access with dot, eg: dev.php for 'dev'=>['php'=>...]",
		'value'=>"The value to assign",
	];
	protected $opts = [
		'unset'=>'unset a key in array',
		'push'=>'append a value in array',
		'unshift'=>'prepend a value in array',
	];
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$key = $input->getArgument('key');
		$value = $input->getArgument('value');
		$path = $this->cwd.'.config.php';
		$config = new TokenTree($path);
		if(!$key){
			$print = $config->var_codify($config['$']);
		}
		elseif(!isset($value)){
			$unset = $input->getOption('unset');
			if($unset){
				unset($config['$.'.$key]);
				$print = "$key unsetted in $path";
			}
			else{
				$print = $config->var_codify($config['$.'.$key]);
			}
		}
		else{
			$push = $input->getOption('push');
			$unshift = $input->getOption('unshift');
			$ref = &$config['$.'.$key];
			if($push){
				if(!is_array($ref))
					$ref = (array)$ref;
				array_push($ref,$value);
				d($ref);
				$print = "$value appened to $key in $path";
			}
			if($unshift){
				if(!is_array($ref))
					$ref = (array)$ref;
				array_unshift($ref,$value);
				$print = "$value prepended to $key in $path";
			}
			if(!$push&&!$unshift){
				$config['$.'.$key] = $value;
				$print = "$key setted to $value in $path";
			}
			file_put_contents($path,(string)$config);
		}
		$output->writeln($print);
	}	
}