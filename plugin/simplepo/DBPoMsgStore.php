<?php namespace SimplePO;
use model\R;
class DBPoMsgStore {
	function init( $catalogue_name ){
		$q = new Query();
		$catalogue = $q->sql("SELECT * FROM catalogue WHERE name = ?",$catalogue_name)->fetch();
		if (!$catalogue) {
			$q->sql("INSERT INTO catalogue (name) VALUES (?)",$catalogue_name)->execute();
			$this->catalogue_id = $q->insertId();
		} else {
			$this->catalogue_id = $catalogue['id'];
		}
	}
	function write( $msg, $isHeader ){
		$msg['isHeader'] = $isHeader ? 1 : 0;		
		$b = R::findOrNewOne('message',[
			'catalogue_id'=>$this->catalogue_id,
			'msgid'=>@$msg["msgid"],
			'reference'=> @$msg["reference"],
			'isHeader'=> @$msg['isHeader'],
		]);
		foreach([
			'isObsolete'=> 0,
			'msgstr'=> @$msg["msgstr"],
			'comments'=> @$msg["translator-comments"],
			'extractedComments'=> @$msg["extracted-comments"],
			'previousUntranslatedString'=> @$msg["previous-untranslated-string"],
			'flags'=> @$msg["flags"],
		] as $k=>$v)
			$b->$k = $v;
		R::store($b);
	}
	function read(){
		$q = new Query();
		return $q->sql("SELECT * FROM message WHERE catalogue_id = ? AND LENGTH(msgstr)>0 AND isObsolete=0 ORDER BY isHeader DESC,isObsolete,id", $this->catalogue_id)->fetchAll();
	}
}