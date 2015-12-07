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
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$key = $input->getArgument('key');
		$value = $input->getArgument('value');
		$path = $this->cwd.'.config.php';
		$config = new TokenTree($path);
		if(!$key){
			$print = $config->var_codify($config['$']);
		}
		elseif(!isset($value)){
			$ref = $config->dot('$.'.$key);
			$print = $config->var_codify($ref);
		}
		else{
			$ref = $config->dot('$.'.$key,$value);
			file_put_contents($path,(string)$config);
			$print = "$key setted to $value in $path";
		}
		$output->writeln($print);
	}	
}