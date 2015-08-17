<?php namespace Wild\Localize;
use Wild\Localize\Gettext\Extractors\PhpCode;
class getTextExtractorPHP extends getTextExtractor{
	protected static function parseFile($file,$sourceDir=null){
		if($sourceDir){
			if(defined('SURIKAT_CWD'))
				$cwd = SURIKAT_CWD;
			else
				$cwd = getcwd();
			chdir($sourceDir);
			$file = substr($file,strlen($sourceDir));
		}
		$msg = '';
		$translations = PhpCode::fromFile($file);
		foreach($translations as $translation){
			$tr = [];
			if ($translation->hasComments()) {
				foreach ($translation->getComments() as $comment) {
					$tr[] = '# '.$comment;
				}
			}
			if ($translation->hasReferences()) {
				foreach ($translation->getReferences() as $reference) {
					$tr[] = '#: '.$reference[0].(!is_null($reference[1]) ? ':'.$reference[1] : null);
				}
			}
			if ($translation->hasContext()) {
				$tr[] = 'msgctxt '.self::quote($translation->getContext());
			}
			$msgid = self::multilineQuote($translation->getOriginal());
			if (count($msgid) === 1) {
				$tr[] = 'msgid '.$msgid[0];
			} else {
				$tr[] = 'msgid ""';
				$tr = array_merge($tr, $msgid);
			}
			if ($translation->hasPlural()) {
				$tr[] = 'msgid_plural '.self::quote($translation->getPlural());
				$tr[] = 'msgstr[0] '.self::quote($translation->getTranslation());

				foreach ($translation->getPluralTranslation() as $k => $v) {
					$tr[] = 'msgstr['.($k + 1).'] '.self::quote($v);
				}
			} else {
				$tr[] = 'msgstr '.self::quote($translation->getTranslation());
			}
			$tr[] = "\n";
			$msg .= implode("\n",$tr);
		}
		if(isset($cwd))
			chdir($cwd);
		return $msg;
	}
}