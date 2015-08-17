<?php namespace Wild\Localize;
class FileReader {
  var $_pos;
  var $_fd;
  var $_length;
  function __construct($filename){
    if(file_exists($filename)){
      $this->_length=filesize($filename);
      $this->_pos = 0;
      $this->_fd = fopen($filename,'rb');
      if(!$this->_fd){
        $this->error = 3;
        return false;
      }
    }
    else{
      $this->error = 2;
      return false;
    }
  }
  function read($bytes){
    if ($bytes) {
      fseek($this->_fd, $this->_pos);
      $data = '';
      while($bytes > 0){
        $chunk  = fread($this->_fd, $bytes);
        $data  .= $chunk;
        $bytes -= strlen($chunk);
      }
      $this->_pos = ftell($this->_fd);
      return $data;
    }
	else
		return '';
  }
  function seekto($pos) {
    fseek($this->_fd, $pos);
    $this->_pos = ftell($this->_fd);
    return $this->_pos;
  }
  function currentpos() {
    return $this->_pos;
  }
  function length() {
    return $this->_length;
  }
  function close() {
    fclose($this->_fd);
  }
}