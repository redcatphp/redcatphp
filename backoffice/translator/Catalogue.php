<?php namespace Translator;
use Surikat\FileSystem\FS;
class Catalogue {
	protected $db;
	protected $lang;
	protected $name;
	protected $id;
	protected $POParser;
	static function headerPots(){
		$pots = SURIKAT_PATH.'langs/header.pots';
		if(!is_file($pots)){
			FS::mkdir($pots,true);
			copy(__DIR__.'/header.pots',$pots);
		}
		return file_get_contents($pots);
	}
	function __construct($db,$lang,$name){
		$this->db = $db;
		$this->lang = $lang;
		$this->name = $name?$name:'messages';
		$catalogue = $this->db->findOrNewOne('catalogue',['name'=>$name,'lang'=>$lang]);
		if(!$catalogue->id)
			$catalogue->store();
		$this->id = $catalogue->id;
		$MsgStore = new DBPoMsgStore($this->db,$this->id);
		$this->POParser = new POParser($MsgStore);
	}
	function id(){
		return $this->id;
	}
	function export($file){
		$stream = fopen($file,'w');
		$this->POParser->writePoFileToStream($stream,self::headerPots());
		fclose($stream);
	}
	function import($file,$atline=null){
		if(!$atline)
			$this->db->exec("UPDATE message SET isObsolete=1 WHERE catalogue_id=?",[$this->id]);
		$stream = fopen($file,'r');
		$r = $this->POParser->parseEntriesFromStream($stream,$atline);
		fclose($stream);
		return $r;
	}
}