<?php namespace Translator;
class DBPoMsgStore {
	private $db;
	private $id;
	function __construct($db,$id){
		$this->id = $id;
		$this->db = $db;
	}
	function write($msg){
		if(!$msg["msgid"])
			return;
		$b = $this->db->findOrNewOne('message',[
			'catalogue_id'=>$this->id,
			'msgid'=>$msg["msgid"],
			'reference'=> @$msg["reference"],
		]);
		foreach([
			'isObsolete'=> 0,
			'msgstr'=> (string)$b->msgstr,
			'comments'=> @$msg["translator-comments"],
			'extractedComments'=> @$msg["extracted-comments"],
			'previousUntranslatedString'=> @$msg["previous-untranslated-string"],
			'flags'=> @$msg["flags"],
		] as $k=>$v)
			$b->$k = $v;
		$this->db->store($b);
	}
	function read(){
		return $this->db->getAll("SELECT * FROM message WHERE catalogue_id = ? AND LENGTH(msgstr)>0 AND isObsolete=0 ORDER BY msgid COLLATE NOCASE ASC", [$this->id]);
	}
}