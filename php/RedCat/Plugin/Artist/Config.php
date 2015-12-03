<?php
namespace RedCat\Plugin\Artist;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
class Config extends Artist{
	protected $description = "Update .config.php file at root of application";

	protected $args = [
		'key'=>"The key in '$' associative array, recursive access with dot, eg: dev.php for 'dev'=>['php'=>...]",
		'value'=>"The value to assign",
	];
	
	protected $opts = [
		"constant"=>"If set, it will set value as constant",
		"cast"=>"If set, it will cast the value as specified: int, string, float",
	];
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$key = $input->getArgument('key');
		$value = $input->getArgument('value');
		$path = $this->cwd.'.config.php';
		$config = include($path);
		
		if(!$key){
			$print = self::var_export($config['$']);
		}
		elseif(!$value){
			$ref = self::dotOffset($key,$config['$']);
			$print = self::var_export($ref);
		}
		else{

			if($input->getOption('constant')){
				$value = constant($value);
			}
			elseif($cast=$input->getOption('cast')){
				switch($cast){
					case 'const':
					case 'constant':
						$value = constant($value);
					break;
					case 'boolean':
					case 'bool':
						$value = (bool)$value;
					break;
					case 'str':
					case 'string':
						$value = (string)$value;
					break;
					case 'float':
						$value = (float)$value;
					break;
					case 'int':
					case 'integer':
						$value = (int)$value;
					break;
					case 'a':
					case 'array':
						$value = (array)$value;
					break;
					case 'obj':
					case 'object':
						$value = (object)$value;
					break;
				}
			}
			else{
				$value = eval('return '.$value.';');
			}
			$ref = self::dotOffset($key,$config['$'],$value);			
			file_put_contents($path,"<?php\nreturn ".self::var_export($config).';');
			$print = "$key setted to ".self::var_export($value)." in $path";
		}
		$output->writeln($print);
	}
	private static function var_export($var, $indent=0){
		switch(gettype($var)){
			case 'string':
				return "'".addcslashes($var, '\'')."'";
			case 'array':
				$indexed = array_keys($var) === range(0, count($var) - 1);
				$r = [];
				foreach($var as $key => $value){
					$r[] = str_repeat("\t",$indent+1)
						 .($indexed?'':self::var_export($key).' => ')
						 .self::var_export($value, $indent+1);
				}
				return "[\n" . implode(",\n", $r) . "\n" . str_repeat("\t",$indent) . "]";
			case 'boolean':
				return $var?'true':'false';
			default:
				if(is_float($var))
					return (string)$var;
				return var_export($var, true);
		}
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