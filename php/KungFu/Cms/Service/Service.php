<?php namespace KungFu\Cms\Service;
class Service {
	function __invoke($func=null){
		if(!func_num_args()){
			http_response_code(404);
			return;
		}
		$func = str_replace('/','_',$func);
		list($c,$m) = self::__funcToCm($func);
		return (new $c())->$m();
	}
	protected static function StudlyCaps($str){
		$str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', $str);
		return $str;
	}
	protected static function __funcToCm($func){
		$pos = strpos($func,'_');
		$c = 'KungFu\Cms\Service\Service_';
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