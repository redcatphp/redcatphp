<?php namespace Translator;
class Catalogue {
	protected $db;
	protected $lang;
	protected $name;
	protected $id;
	protected $POParser;
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
		$this->POParser->writePoFileToStream($stream,file_get_contents(SURIKAT_PATH.'langs/header.pots'));
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