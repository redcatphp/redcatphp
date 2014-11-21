<?php  namespace SimplePO;
class TempPoMsgStore{
	private $msgs;
	function write($msg,$isHeader) {
		 $this->msgs[] = $msg;
	}
	function read() {
		return $this->msgs;
	}
}