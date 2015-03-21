<?php namespace Surikat\Exception;
class Exception extends \Exception{
    private $_data;
    function __construct($message, $code = 0, Exception $previous = null, $data = null){
		foreach(func_get_args() as $arg)
			if(is_string($arg))
				$message = $arg;
			elseif(is_integer($arg))
				$code = $arg;
			elseif($arg instanceof \Exception)
				$previous = $arg;
			elseif(is_array($arg)||is_object($arg))
				$data = $arg;
		if(!is_string($message))
			$message = 'Unexpected Exception';
		if(!is_integer($code))
			$code = 0;
		if(!$previous instanceof Exception)
			$previous = null;
        parent::__construct($message, $code, $previous);
        $this->_data = $data;
    }
    function getData(){
		return $this->_data;
	}
    function setData($data){
		$this->_data = $data;
	}
}
