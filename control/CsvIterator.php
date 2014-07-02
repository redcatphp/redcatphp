<?php namespace surikat\control;
use Iterator;
class CsvIterator implements Iterator{
    private $row_size;
    private $enclosure;
    private $escape;
    private $filePointer;
    private $currentElement;
    private $rowCounter;
    private $delimiter;
	function __construct($file,$delimiter=',',$enclosure='"',$escape="\\",$length=0){
        try {
            $this->filePointer = fopen($file, 'r');
            $this->delimiter = $delimiter;
            $this->enclosure = $enclosure;
            $this->escape = $escape;
            $this->row_size = $length;
            
        }
        catch(Exception $e){
            throw new Exception('The file "'.$file.'" cannot be read.');
        }
    }
    function rewind() {
        $this->rowCounter = 0;
        rewind($this->filePointer);
    }
    function current() {
        $this->currentElement = fgetcsv($this->filePointer, $this->row_size, $this->delimiter, $this->enclosure, $this->escape);
        $this->rowCounter++;
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
