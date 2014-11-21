<?php namespace SimplePO;
use control;
use i18n\phpmo;
use i18n\tmlGetText;
use model\R;
class MessageService {
	var $potfile = 'langs/messages.pot';
	function getMessages($id) {
		$q = new Query();
		$messages = $q->sql("SELECT * FROM message WHERE catalogue_id=? AND isHeader <> 1 ORDER BY msgstr != '', flags != 'fuzzy' ", $id)->fetchAll();
		foreach($messages as &$m) {
			$m['fuzzy'] = strpos($m['flags'],'fuzzy') !== FALSE;
			$m['isObsolete'] = !!$m['isObsolete'];
		}
		return $messages;
	}
	function getCatalogues(){
		$q = new Query();
		return $q->sql("SELECT c.name,c.id,COUNT(*) as message_count, COALESCE(SUM(LENGTH(m.msgstr) >0),0) as translated_count FROM catalogue c LEFT JOIN message m ON m.catalogue_id=c.id AND m.isHeader=0 GROUP BY c.id")->fetchAll();
	}
	function updateMessage($id, $comments, $msgstr, $fuzzy){
		$q = new Query();
		$flags = $fuzzy ? 'fuzzy' : '';
		$q->sql("UPDATE message SET comments=?, msgstr=?, flags=? WHERE id=?", $comments, $msgstr, $flags, $id)->execute();
	}
	function makePot(){
		$potfile = control::$CWD.$this->potfile;
		$pot = file_get_contents(control::$CWD.'langs/header.pot');
		$pot = str_replace("{ctime}",gmdate('Y-m-d H:iO',is_file($potfile)?filemtime($potfile):time()),$pot);
		$pot = str_replace("{mtime}",gmdate('Y-m-d H:iO'),$pot);
		$pot .= tmlGetText::parse(control::$CWD.'view',control::$CWD);
		file_put_contents($potfile,$pot);
	}
	function cleanObsolete(){
		R::selectDatabase('langs');
		R::exec("DELETE FROM message WHERE isObsolete=1");
	}
	
	function countPotMessages(){
		$POParser = new POParser();
		return ['message_count'=>$POParser->countEntriesFromStream(fopen(control::$CWD.'langs/messages.pot', 'r'))];
	}

	function importCatalogue($cid=null,$lg=null){
		R::selectDatabase('langs');
		if(!isset($cid))
			$cid = (int)@$_POST['cid'];
		if(!isset($lg))
			$lg = @$_POST['lang'];
		if(!isset($cid)&&$lg)
			$cid = R::getCell('SELECT id from catalogue WHERE name=?',[$lg]);
		if(!isset($lg)&&$cid)
			$lg = R::getCell('SELECT name from catalogue WHERE id=?',[$cid]);
		if(!isset($lg)||!isset($cid))
			return;
		
		R::exec("UPDATE message SET isObsolete=1 WHERE catalogue_id=?",[$cid]);
		SimplePO::import($lg,control::$CWD.$this->potfile);
	}
	function exportCatalogue($cid=null,$lg=null){
		R::selectDatabase('langs');
		if(!isset($cid))
			$cid = (int)@$_POST['cid'];
		if(!isset($lg))
			$lg = @$_POST['lang'];
		if(!isset($cid)&&$lg)
			$cid = R::getCell('SELECT id from catalogue WHERE name=?',[$lg]);
		if(!isset($lg)&&$cid)
			$lg = R::getCell('SELECT name from catalogue WHERE id=?',[$cid]);
		if(!isset($lg)||!isset($cid))
			return;
		
		$path = control::$CWD.'langs/'.$lg.'/LC_MESSAGES/messages.';
		$po = $path.'po';
		$mo = $path.'mo';
		SimplePO::export($lg,$po);
		phpmo::convert($po,$mo);
		
		foreach(glob($path.'*.mo') as $f)
			unlink($f);
		copy($mo,$path.time().'.mo');
	}
}