<?php namespace Translator;
use Surikat\Core\FS;
use Surikat\I18n\msgfmt;
use Surikat\I18n\tmlGetText;
use Surikat\Model\R;
use Surikat\Model\Query;
class MessageService {
	var $potfile = 'langs/messages.pot';
	private $db;
	function __construct() {
		$this->db = Surikat\Model\R::getDatabase('langs');
		if(!$this->db->exists()){
			foreach(explode(';',file_get_contents(__DIR__.'/install.sql')) as $l)
				$this->db->execMulti($l);
		}
	}
	function getCountMessages($id){
		return (new Query('message',$this->db))
			->select('COUNT(*)')
			->where('catalogue_id=?',[$id])
			->getCell()
		;
	}
	function getMessages($lang, $id, $page, $order, $sort) {
		$limit = 15;
		$offset = ($page-1)*$limit;
		switch($order){
			case 'fuzzy':
				$order = 'flags';
			break;
			case 'depr':
				$order = 'isObsolete';
			break;
			default:
				$order = 'msgid';
			break;
			case 'msgid':
			case 'msgstr':
			case 'isObsolete':
			case 'flags':
			break;
		}
		if(!$id)
			$id = (new Query('message',$this->db))
				->select('id')
				->where('lang=?',[$lang])
				->getCell()
			;
		$messages = (new Query('message',$this->db))
			->where('catalogue_id=?',[$id])
			->orderBy($order.' COLLATE NOCASE')
			->sort($sort)
			->limit($limit)
			->offset($offset)
			->getAll()
		;
		foreach($messages as &$m) {
			$m['fuzzy'] = strpos($m['flags'],'fuzzy') !== FALSE;
			$m['isObsolete'] = !!$m['isObsolete'];
		}
		return $messages;
	}
	function getStats($lang,$id){
		//\Core\Dev::on(\Core\Dev::MODEL);
		return $this->db->getAll("SELECT c.name,c.id,COUNT(*) as message_count, COALESCE(SUM(LENGTH(m.msgstr) >0),0) as translated_count FROM catalogue c LEFT JOIN message m ON m.catalogue_id=c.id WHERE lang=? AND c.id=? GROUP BY c.id",[$lang,(int)$id]);
	}
	function getCatalogues($lang){
		if(!$lang)
			return;
		new Catalogue($this->db,$lang,'messages');
		$names = [];
		foreach(glob(SURIKAT_PATH.'langs/*.pot') as $name)
			$names[] = pathinfo($name,PATHINFO_FILENAME);
		return $names;
		//return $this->db->getAll("SELECT c.name,c.id,COUNT(*) as message_count, COALESCE(SUM(LENGTH(m.msgstr) >0),0) as translated_count FROM catalogue c LEFT JOIN message m ON m.catalogue_id=c.id WHERE lang=? GROUP BY c.id",[$lang]);
	}
	function updateMessage($id, $comments, $msgstr, $fuzzy){
		$flags = $fuzzy&&$fuzzy!='false' ? 'fuzzy' : '';
		$this->db->exec("UPDATE message SET comments=?, msgstr=?, flags=? WHERE id=?", [$comments, $msgstr, $flags, $id]);
	}
	function makePot(){
		$potfile = SURIKAT_PATH.$this->potfile;
		$pot = file_get_contents(SURIKAT_PATH.'langs/header.pots');
		$pot = str_replace("{ctime}",gmdate('Y-m-d H:iO',is_file($potfile)?filemtime($potfile):time()),$pot);
		$pot = str_replace("{mtime}",gmdate('Y-m-d H:iO'),$pot);
		$pot .= tmlGetText::parse(SURIKAT_PATH.'tml',SURIKAT_PATH);
		file_put_contents($potfile,$pot);
	}
	function cleanObsolete(){
		$this->db->exec("DELETE FROM message WHERE isObsolete=1");
	}
	
	function countPotMessages(){
		return (new POParser())->countEntriesFromStream(fopen(SURIKAT_PATH.'langs/messages.pot', 'r'));
	}

	function importCatalogue($lang,$name,$atline=null){
		if($lang&&$name)
			return (new Catalogue($this->db,$lang,$name))->import(SURIKAT_PATH.$this->potfile,$atline);
	}
	function exportCatalogue($lang,$name){
		if(!$lang||!$name)
			return;
		$path = SURIKAT_PATH.'langs/'.$lang.'/LC_MESSAGES/messages.';
		FS::mkdir($path,true);
		$po = $path.'po';
		$mo = $path.'mo';
		(new Catalogue($this->db,$lang,$name))->export($po);
		msgfmt::convert($po,$mo);
		foreach(glob($path.'*.mo') as $f)
			unlink($f);
		copy($mo,$path.time().'.mo');
	}
}