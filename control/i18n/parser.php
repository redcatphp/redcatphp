<?php namespace surikat\control\i18n;
use control;
use control\FS;
use model\R;
use view\TML;
class parser{
	private static $tpl_extensions = ['tml','atml','btml','tpl','php'];
	private static $lang_compil_path;
	static function add_dates(){
		$outc = '';
		// foreach(Surikati18n::$datetimeStrings as $k){
			// $outc .= "#: Surikati18n.php:0 \n";
			// $outc .= 'msgid "DATE_'.$k."\"\n";
			// $outc .= "msgstr \"\" \n\n";
		// }
		$potfile = control::$CWD.'langs/messages.pot';
		if($handle=fopen($potfile,'a')){
			fwrite($handle,$outc);
			fclose($handle);
		}
	}
	static function compile_mo_from_po($dir){
		$mofile = control::$CWD.$dir.'/LC_MESSAGES/messages.mo';
		$pofile = control::$CWD.$dir.'/LC_MESSAGES/messages.po';
		if(is_file($mofile)) unlink($mofile);
		self::phpmo_convert($pofile,$mofile);
	}
	
	static function sources_compiler(){
		$potfile = control::$CWD.'langs/messages.pot';
		$add = @file_get_contents(control::$CWD.'langs/header.pot');
		$add = str_replace("{ctime}",gmdate('Y-m-d H:iO',is_file($potfile)?filemtime($potfile):time()),$add);
		$add = str_replace("{mtime}",gmdate('Y-m-d H:iO'),$add);
		// if(is_file($potfile)) @unlink($potfile);
		file_put_contents($potfile,$add);
		foreach(func_get_args() as $arg){
			if(is_array($arg)){
				self::ttml2c([$arg[0],@$arg[1]]);
			}
			else{
				self::ttml2c([$arg,true]);
			}
		}
	}
	static function ttml2c(){
		$args = func_get_args();
		if(empty($args)) return;
		self::$lang_compil_path = control::$TMP.'langs/';
		FS::mkdir(self::$lang_compil_path);
		foreach($args as $arg) {
			if(is_array($arg)){
				if(is_dir($arg[0])){
					self::do_dir($arg[0],$arg[1]);
				}
				else{
					self::do_file($arg[0]);
				}
			}
			else{
				if(is_dir($arg)){
					self::do_dir($arg);
				}
				else{
					self::do_file($arg);
				}
			}
		}
	}
	
	private static function fs($str){
		$str = stripslashes($str);
		$str = str_replace('"', '\"', $str);
		$str = str_replace("\n", '\n', $str);
		return $str;
	}
	private static function do_dir($dir,$recurs=true){
		$d = dir($dir);
		while (false !== ($entry = $d->read())) {
			if ($entry=='.'||$entry=='..') continue;
			$entry = $dir.'/'.$entry;
			if (is_dir($entry)&&$recurs) { // if a directory, go through it
				self::do_dir($entry,$recurs);
			}
			else{
				$pi = pathinfo($entry);
				if (isset($pi['extension']) && in_array($pi['extension'], self::$tpl_extensions)) {
					self::do_file($entry);
				}
			}
		}
		$d->close();
	}
	private static function do_file($file){
		$filename = substr($file,strlen(control::$CWD));
		$potfile = control::$CWD.'langs/messages.pot';
		$outc = '';
		if($handle=fopen($potfile,'a')){
			$content = file_get_contents($file);
			if(empty($content))
				return;
				
			$TML = new TML($content);
			$TML('TEXT:hasnt(PHP)')->each(function($el){
				$el->write('__("'.str_replace("\n","\\n",$el).'")');
			});
			$content = "$TML";
			
			foreach(explode("\n",$content) as $l=>$line){
				preg_match_all('/__\\("([^"\\)]+)/s',$line,$matches);
				for($i=0;$i<count($matches[0]);$i++){
					$outc .= "#: $filename:$l \n";
					$outc .= 'msgid "'.self::fs($matches[1][$i])."\"\n";
					$outc .= "msgstr \"\" \n\n";
				}
				preg_match_all('/__\\(\'([^\'\\)]+)/s',$line,$matches);
				for($i=0;$i<count($matches[0]);$i++){
					$outc .= "#: $filename:$l \n";
					$outc .= 'msgid "'.self::fs($matches[1][$i])."\"\n";
					$outc .= "msgstr \"\" \n\n";
				}
			}
			fwrite($handle,$outc);
			fclose($handle);
		}
	}
	
	/**
	 * php.mo 0.1 by Joss Crowcroft (http://www.josscrowcroft.com)
	 * 
	 * Converts gettext translation '.po' files to binary '.mo' files in PHP.
	 * 
	 * Usage: 
	 * <?php require('php-mo.php'); self::phpmo_convert( 'input.po', [ 'output.mo' ] ); ?>
	 * 
	 * NB:
	 * - If no $output_file specified, output filename is same as $input_file (but .mo)
	 * - Returns true/false for success/failure
	 * - No warranty, but if it breaks, please let me know
	 * 
	 * More info:
	 * https://github.com/josscrowcroft/php.mo
	 * 
	 * Based on php-msgfmt by Matthias Bauer (Copyright © 2007), a command-line PHP tool
	 * for converting .po files to .mo.
	 * (http://wordpress-soc-2007.googlecode.com/svn/trunk/moeffju/php-msgfmt/msgfmt.php)
	 * 
	 * License: GPL v3 http://www.opensource.org/licenses/gpl-3.0.html
	 */

	/**
	 * The main .po to .mo function
	 */
	static function phpmo_convert($input, $output = false) {
		if ( !$output )
			$output = str_replace( '.po', '.mo', $input );

		$hash = self::phpmo_parse_po_file( $input );
		if ( $hash === false ) {
			return false;
		} else {
			self::phpmo_write_mo_file( $hash, $output );
			return true;
		}
	}

	static function phpmo_clean_helper($x) {
		if (is_array($x)) {
			foreach ($x as $k => $v) {
				$x[$k] = self::phpmo_clean_helper($v);
			}
		} else {
			if ($x[0] == '"')
				$x = substr($x, 1, -1);
			$x = str_replace("\"\n\"", '', $x);
			$x = str_replace('$', '\\$', $x);
		}
		return $x;
	}

	/* Parse gettext .po files. */
	/* @link http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files */
	static function phpmo_parse_po_file($in) {
		// read .po file
		$fh = fopen($in, 'r');
		if ($fh === false) {
			// Could not open file resource
			return false;
		}

		// results array
		$hash =  [];
		// temporary array
		$temp =  [];
		// state
		$state = null;
		$fuzzy = false;

		// iterate over lines
		while(($line = fgets($fh, 65536)) !== false) {
			$line = trim($line);
			if ($line==='') continue;
			$split = preg_split('/\s/', $line, 2);
			if(count($split)<2){
				continue;
			}
			list($key, $data) = $split;
			
			switch ($key) {
				case '#,' : // flag...
					$fuzzy = in_array('fuzzy', preg_split('/,\s*/', $data));
				case '#' : // translator-comments
				case '#.' : // extracted-comments
				case '#:' : // reference...
				case '#|' : // msgid previous-untranslated-string
					// start a new entry
					if (sizeof($temp) && array_key_exists('msgid', $temp) && array_key_exists('msgstr', $temp)) {
						if (!$fuzzy)
							$hash[] = $temp;
						$temp =  [];
						$state = null;
						$fuzzy = false;
					}
					break;
				case 'msgctxt' :
					// context
				case 'msgid' :
					// untranslated-string
				case 'msgid_plural' :
					// untranslated-string-plural
					$state = $key;
					$temp[$state] = $data;
					break;
				case 'msgstr' :
					// translated-string
					$state = 'msgstr';
					$temp[$state][] = $data;
					break;
				default :
					if (strpos($key, 'msgstr[') !== FALSE) {
						// translated-string-case-n
						$state = 'msgstr';
						$temp[$state][] = $data;
					} else {
						// continued lines
						switch ($state) {
							case 'msgctxt' :
							case 'msgid' :
							case 'msgid_plural' :
								$temp[$state] .= "\n" . $line;
								break;
							case 'msgstr' :
								$temp[$state][sizeof($temp[$state]) - 1] .= "\n" . $line;
								break;
							default :
								// parse error
								fclose($fh);
								return FALSE;
						}
					}
					break;
			}
		}
		fclose($fh);
		
		// add final entry
		if ($state == 'msgstr')
			$hash[] = $temp;

		// Cleanup data, merge multiline entries, reindex hash for ksort
		$temp = $hash;
		$hash =  [];
		foreach ($temp as $entry) {
			foreach ($entry as & $v) {
				$v = self::phpmo_clean_helper($v);
				if ($v === FALSE) {
					// parse error
					return FALSE;
				}
			}
			$hash[$entry['msgid']] = $entry;
		}

		return $hash;
	}

	/* Write a GNU gettext style machine object. */
	/* @link http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files */
	static function phpmo_write_mo_file($hash, $out) {
		// sort by msgid
		ksort($hash, SORT_STRING);
		// our mo file data
		$mo = '';
		// header data
		$offsets =  [];
		$ids = '';
		$strings = '';

		foreach ($hash as $entry) {
			$id = $entry['msgid'];
			if (isset ($entry['msgid_plural']))
				$id .= "\x00" . $entry['msgid_plural'];
			// context is merged into id, separated by EOT (\x04)
			if (array_key_exists('msgctxt', $entry))
				$id = $entry['msgctxt'] . "\x04" . $id;
			// plural msgstrs are NUL-separated
			$str = implode("\x00", $entry['msgstr']);
			// keep track of offsets
			$offsets[] =  [
				strlen($ids
			), strlen($id), strlen($strings), strlen($str)];
			// plural msgids are not stored (?)
			$ids .= $id . "\x00";
			$strings .= $str . "\x00";
		}

		// keys start after the header (7 words) + index tables ($#hash * 4 words)
		$key_start = 7 * 4 + sizeof($hash) * 4 * 4;
		// values start right after the keys
		$value_start = $key_start +strlen($ids);
		// first all key offsets, then all value offsets
		$key_offsets =  [];
		$value_offsets =  [];
		// calculate
		foreach ($offsets as $v) {
			list ($o1, $l1, $o2, $l2) = $v;
			$key_offsets[] = $l1;
			$key_offsets[] = $o1 + $key_start;
			$value_offsets[] = $l2;
			$value_offsets[] = $o2 + $value_start;
		}
		$offsets = array_merge($key_offsets, $value_offsets);

		// write header
		$mo .= pack('Iiiiiii', 0x950412de, // magic number
		0, // version
		sizeof($hash), // number of entries in the catalog
		7 * 4, // key index offset
		7 * 4 + sizeof($hash) * 8, // value index offset,
		0, // hashtable size (unused, thus 0)
		$key_start // hashtable offset
		);
		// offsets
		foreach ($offsets as $offset)
			$mo .= pack('i', $offset);
		// ids
		$mo .= $ids;
		// strings
		$mo .= $strings;

		file_put_contents($out, $mo);
	}
	
}
