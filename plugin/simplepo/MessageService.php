<?php namespace SimplePO;
use Surikat\I18n\msgfmt;
use Surikat\I18n\tmlGetText;
use Surikat\Model\R;
use Surikat\Model\Query;
class MessageService {
	var $potfile = 'langs/messages.pot';
	private $db;
	function __construct() {
		$this->db = include(__DIR__.'/dbo.php');
	}
	function getCountMessages($id){
		return (new Query('message',$this->db))
			->select('COUNT(*)')
			->where('catalogue_id=?',[$id])
			->getCell()
		;
	}
	function getMessages($id, $page, $order, $sort) {
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
	function getCatalogues(){
		foreach(glob(SURIKAT_PATH.'langs/*',GLOB_ONLYDIR) as $d)
			SimplePO::catalogue(pathinfo($d,PATHINFO_FILENAME));
		return $this->db->getAll("SELECT c.name,c.id,COUNT(*) as message_count, COALESCE(SUM(LENGTH(m.msgstr) >0),0) as translated_count FROM catalogue c LEFT JOIN message m ON m.catalogue_id=c.id GROUP BY c.id");
	}
	function updateMessage($id, $comments, $msgstr, $fuzzy){
		$flags = $fuzzy&&$fuzzy!='false' ? 'fuzzy' : '';
		$this->db->exec("UPDATE message SET comments=?, msgstr=?, flags=? WHERE id=?", [$comments, $msgstr, $flags, $id]);
	}
	function makePot(){
		$potfile = SURIKAT_PATH.$this->potfile;
		$pot = file_get_contents(SURIKAT_PATH.'langs/header.pot');
		$pot = str_replace("{ctime}",gmdate('Y-m-d H:iO',is_file($potfile)?filemtime($potfile):time()),$pot);
		$pot = str_replace("{mtime}",gmdate('Y-m-d H:iO'),$pot);
		$pot .= tmlGetText::parse(SURIKAT_PATH.'tml',SURIKAT_PATH);
		file_put_contents($potfile,$pot);
	}
	function cleanObsolete(){
		$this->db->exec("DELETE FROM message WHERE isObsolete=1");
	}
	
	function countPotMessages(){
		$POParser = new POParser();
		return ['message_count'=>$POParser->countEntriesFromStream(fopen(SURIKAT_PATH.'langs/messages.pot', 'r'))];
	}

	function importCatalogue(){
		if(!isset($_POST['cid']))
			return;
		$cid = (int)$_POST['cid'];
		$lg = $this->db->getCell('SELECT name from catalogue WHERE id=?',[$cid]);
		if(!isset($lg))
			return;
		$atline = @$_POST['atline'];
		if(!$atline)
			$this->db->exec("UPDATE message SET isObsolete=1 WHERE catalogue_id=?",[$cid]);
		return SimplePO::import($lg,SURIKAT_PATH.$this->potfile,$atline);
	}
	function exportCatalogue(){
		if(!isset($_POST['cid']))
			return;
		$cid = (int)$_POST['cid'];
		$lg = $this->db->getCell('SELECT name from catalogue WHERE id=?',[$cid]);
		if(!isset($lg))
			return;
		$path = SURIKAT_PATH.'langs/'.$lg.'/LC_MESSAGES/messages.';
		$po = $path.'po';
		$mo = $path.'mo';
		SimplePO::export($lg,$po);
		msgfmt::convert($po,$mo);
		foreach(glob($path.'*.mo') as $f)
			unlink($f);
		copy($mo,$path.time().'.mo');
	}
}