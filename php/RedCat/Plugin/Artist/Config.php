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
			$ref = self::dotOffset($key,$config['$']);
			$print = $config->var_codify($ref);
		}
		else{
			$ref = self::dotOffset($key,$config['$'],$value);			

			file_put_contents($path,"<?php\nreturn ".(string)$config.';');
			
			$print = "$key setted to $value in $path";
		}
		$output->writeln($print);
	}
	private static function dotOffset($dotKey,&$config,$value=null){
		$dotKey = explode('.',$dotKey);
		$k = array_shift($dotKey);
		if(!isset($config[$k]))
			return;
		$v = &$config[$k];
		while($k = array_shift($dotKey)){
			if(!isset($v[$k]))
				return;
			$v = &$v[$k];
		}
		if(func_num_args()>2)
			$v = $value;
		return $v;
	}
}