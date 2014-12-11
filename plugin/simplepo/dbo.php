<?php
$db = Surikat\Model\R::getDatabase('langs');
if(!$db->exists()){
	foreach(explode(';',file_get_contents(__DIR__.'/install.sql')) as $l)
		$db->execMulti($l);
}
return $db;