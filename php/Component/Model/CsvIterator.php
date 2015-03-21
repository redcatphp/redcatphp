<?php namespace Surikat\Model;
use Iterator;
class CsvIterator implements Iterator{
    private $row_size;
    private $enclosure;
    private $escape;
    private $filePointer;
    private $currentElement;
    private $rowCounter;
    private $delimiter;
    private $trim;
    private $keys;
    private $keysKeepI;
    private $callback;
	function __construct($file,$delimiter=',',$enclosure='"',$escape="\\",$length=0,$trim=true,$keys=null,$keysKeepI=false,$callback=null){
		$this->filePointer = fopen($file, 'r');
		$this->delimiter = $delimiter;
		$this->enclosure = $enclosure;
		$this->escape = $escape;
		$this->row_size = $length;            
		$this->trim = true; 
        if(!$this->filePointer)
            throw new Exception('The file "'.$file.'" cannot be read.');
        if($keys)
			$this->setKeys($keys);
		$this->keysKeepI = $keysKeepI;
		$this->callback = $callback;
    }
    function setCallback($callback){
		$this->callback = $callback;
	}
    function setKeys($keys,$keysKeepI=false){
		$this->keys = $keys;
		$this->keysKeepI = $keysKeepI;
	}
    function rewind() {
        $this->rowCounter = 0;
        rewind($this->filePointer);
    }
    function current() {
        $this->currentElement =  fgetcsv($this->filePointer, $this->row_size, $this->delimiter, $this->enclosure, $this->escape);
        $this->rowCounter++;
        if($this->trim)
			foreach($this->currentElement as &$v)
				$v = trim($v);
        if($this->keys){
			$c = count($this->currentElement)-1;
			for($i=0;$i<=$c;$i++){
				if(isset($this->keys[$i])){
					if($this->keys[$i]===true)
						continue;
					if($this->keys[$i]!==false)
						$this->currentElement[$this->keys[$i]] = $this->currentElement[$i];
				}
				if(!$this->keysKeepI)
					unset($this->currentElement[$i]);
			}
		}
		if($this->callback)
			call_user_func_array($this->callback,[&$this->currentElement]);
        return $this->currentElement;
    }
    function key() {
        return $this->rowCounter;
    }
    function next() {
        return !feof($this->filePointer);
    }
	function valid() {
        if (!$this->next()) {
            fclose($this->filePointer);
            return false;
        }
        return true;
    }
}
/*
Usage :
<?php
$csvIterator = new CsvIterator('/path/to/csvfile.csv');
foreach ($csvIterator as $row => $data) {
    // do somthing with $data
}
*/