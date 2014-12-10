<?php namespace SimplePO;
class SimplePO {
	protected $catalogue_name;
	protected $MsgStore;
	protected $POParser;
	static function import($name,$file){
		return self::catalogue($name)->importation($file);
	}
	static function export($name,$file){
		return self::catalogue($name)->exportation($file);
	}
	private static $catalogue = [];
	static function catalogue($lg){
		if(!isset(self::$catalogue[$lg]))
			self::$catalogue[$lg] = new SimplePO($lg);
		return self::$catalogue[$lg];
	}
	function __construct($name){
		$this->catalogue_name = $name;
		$this->MsgStore = new DBPoMsgStore();	
		if($this->catalogue_name)
			$this->MsgStore->init($this->catalogue_name);
		$this->POParser = new POParser($this->MsgStore);
	}
	function exportation($file){
		$stream = fopen($file,'w');
		$this->POParser->writePoFileToStream($stream);
		fclose($stream);
	}
	function importation($file){
		$stream = fopen($file,'r');
		$this->POParser->parseEntriesFromStream($stream);
		fclose($stream);
	}
}