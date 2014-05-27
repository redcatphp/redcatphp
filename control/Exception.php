<?php namespace surikat\control;
class Exception extends \Exception{
    private $_data;
    public function __construct($message, $code = 0, Exception $previous = null, $_data = null){
		foreach(func_get_args() as $arg)
			if(is_string($arg))
				$message = $arg;
			elseif(is_integer($arg))
				$code = $arg;
			elseif($arg instanceof \Exception)
				$previous = $arg;
			elseif(is_array($arg)||is_object($arg))
				$data = $arg;
        parent::__construct($message, $code, $previous);
        $this->_data = $_data;
    }
    public function getData(){
		return $this->_data;
	}
    public function setData($data){
		$this->_data = $data;
	}
}
