<?php namespace Surikat\Service;
use Surikat\DependencyInjection\Mutator;
class Service {
	use Mutator;
	function __invoke($func=null){
		if(!func_num_args()){
			$this->getDependency('HTTP')->code('404');
			return;
		}
		$func = str_replace('/','_',$func);
		list($c,$m) = self::__funcToCm($func);
		$this->getDependency($c)->$m();
	}
	protected static function StudlyCaps($str){
		$str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', $str);
		return $str;
	}
	protected static function __funcToCm($func){
		$pos = strpos($func,'_');
		$c = 'Service\\Service_';
		if($pos){
			$c .= ucfirst(substr($func,0,$pos));
			$m = lcfirst(self::StudlyCaps(substr($func,$pos+1)));
		}
		else{
			$c .= ucfirst($func);
			$m = '__invoke';
		}
		$c = self::StudlyCaps($c);
		return [$c,$m];
	}
}