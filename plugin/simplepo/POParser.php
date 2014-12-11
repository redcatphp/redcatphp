<?php namespace SimplePO;
class POParser{
	var $fileHandle;
	var $entryStore;
	protected $context = [];
	protected $line_count = 0;
	protected $match_expressions = [
		[
			'type' => 'translator-comments',
			're_match' => '/(^# )|(^#$)/',
			're_capture' => '/#\s*(.*)$/',
		],
		[
			'type' => 'extracted-comments',
			're_match' => '/^#. /',
			're_capture' => '/#.\s+(.*)$/',
		],
		[
			'type' => 'reference',
			're_match' => '/^#: /',
			're_capture' => '/#:\s+(.*)$/',

		],
		[
			'type' => 'flags',
			're_match' => '/^#, /',
			're_capture' => '/#,\s+(.*)$/',
		],
		[
			'type' => 'previous-untranslated-string',
			're_match' => '/^#\| /',
			're_capture' => '/#\|\s+(.*)$/',
		],
		[
			'type' => 'msgid',
			're_match' => '/^msgid /',
			're_capture' => '/msgid\s+(".*")/',
		],
		[
			'type' => 'msgstr',
			're_match' => '/^msgstr /',
			're_capture' => '/msgstr\s+(".*")/',
		],
		[
			'type' => 'string',
			're_match' => '/^\s*"/',
			're_capture' => '/^\s*(".*")/',
		],
		[
			'type' => 'obsolete-msgid',
			're_match' => '/^#~\s+msgid /',
			're_capture' => '/#~\s+msgid\s+(".*")/',
		],
		[
			'type' => 'obsolete-msgstr',
			're_match' => '/^#~\s+msgstr /',
			're_capture' => '/#~\s+msgstr\s+(".*")/',
		],
		[
			'type' => 'obsolete-string',
			're_match' => '/^#~\s+\s*"/',
			're_capture' => '/^#~\s+(".*")/',
		],
		[
			'type' => 'empty',
			're_match' => '/^$/',
			're_capture' => '/^()$/'
		]
		
	];

	function __construct($entryStore=null){
		$this->entryStore = $entryStore;
	}
	
	function writePoFileToStream($fh,$preout=false) {
		if($preout)
			fwrite($fh, $preout);
		$entries = $this->entryStore->read();
		foreach($entries as $entry)
			fwrite( $fh, $this->convertEntryToString($entry) );
	}
	function countEntriesFromStream($fh) {
		$this->lineNumber = 0;
		$entry_count = -3;
		while(($line = fgets($fh)) !== false ){
			$this->lineNumber++;
			$line = $this->parseLine($line);
			if($line["type"]=="empty")
				$entry_count++;
		}
		return $entry_count;
	}
	function parseEntriesFromStream($fh) {
		$this->lineNumber = 0;
		$entry_count = 0;
		$entry_lines = [];

		while( ($line = fgets($fh)) !== false ) {
			$this->lineNumber++;
			$line = $this->parseLine($line);
			if ( $line["type"] != "empty" ){
				$entry_lines[] = $line;
			}
			else {
				$entry = $this->reduceLines($entry_lines);
				$this->saveEntry( $entry, $entry_count++ );
				$entry_lines = [];
			}
		}
		if ( $entry_lines ){
			$entry = $this->reduceLines($entry_lines);
			$this->saveEntry( $entry, $entry_count++ );
		}
	}
	
	function parseLine( $line ){
		$this->line_count++;
		$line_object = [];
		foreach($this->match_expressions as $m) {
			if(preg_match($m['re_match'],$line) ) {
				preg_match($m['re_capture'],$line,$matches);
				$line_object['value'] = isset($matches[1])?$matches[1]:null;
				$line_object['type'] = $m['type'];
			}
		}
		if(!$line_object)
			throw new \Exception( sprintf("unrecognized line fomat at line: %d",$this->line_count) );		
		return $line_object;
	}

	function decodeStringFormat( $str ){
		if ( substr($str, 0, 1) == '"' && substr($str, -1,1) == '"' ){
			$result = substr($str, 1, -1);
			$translations = ["\\\\"=>"\\", "\\n"=>"\n",'\\"'=>'"'];
			$result = strtr($result, $translations);
		} else {
			throw new \Exception("Invalid PO string (should be surrounded by quotes)\n$str\n");
		}
		return $result;
	}
	
	/**
	*	translates 
	*	Hello"
	*	World 
	*	to 
	*	 ""
	*	"Hello\"\n"
	*	 "World"
	*/
	function encodeStringFormat($message_string){
		$result = strtr($message_string, ['\n'=>"\n"]);
		
		// translate the characters to escapted versions.
		$translations = ["\n"=>"\\n",'"'=>'\\"',"\\"=>"\\\\"];
		$result = strtr($message_string, $translations);

		// put the \n's at the end of the lines.
		$result = str_replace("\\n","\\n\n",$result);
		
		// wrap text so po files can be edited nicely.	
		 $lines = explode("\n",$result);
		 foreach($lines as &$l) {
			 $l = $this->wordwrap($l,78);
		 }
		 $result = implode("\n",$lines);
		
		// if there are mutiple lines, lets prefix everything with a ""	like the gettext tools
		if(strpos($result,"\n"))
			$result = "\n" . $result;
		
		// wrap each line in quotes
		$result = $this->addPrefixToLines('"',$result);
		$result = $this->addSuffixToLines('"',$result);
		
		return $result;
		
	}
	function addPrefixToLines($prefix,$text) {
		$text = explode("\n",$text);
		foreach($text as &$line) {
			$line = $prefix . $line;
		}
		return implode("\n",$text);
	}
	function addSuffixToLines($suffix,$text) {
		$text = explode("\n",$text);
		foreach($text as &$line) {
			$line = $line . $suffix;
		}
		return implode("\n",$text);
	}
	function wordwrap($text,$max_len=75) {
		$result = "";
		$ll=0;
		$words = explode(" ",$text);
		foreach($words as $w) {
			$lw = mb_strlen($w,'UTF-8');
			if ( $ll + $lw + 1 < $max_len) {
				$result .= $w ." ";
				$ll += $lw + 1;
			}
			else { 
				$result .= "\n" . $w . " ";
				$ll = $lw + 1;
			}
		}
		$result = substr($result,0,-1);
		return $result;
	}

	function saveEntry( $entry, $entry_count ){
		$this->entryStore->write($entry, $entry_count == 0 );
	}
	function reduceLines( $entry_lines ){
		$entry = [];
		$context = "";
		$is_obsolete = false;
		foreach ( $entry_lines as $line ) {
			// convert the obsolete types into normal type, and mark as obsolete;
			if (substr($line['type'],0,9) == "obsolete-") {
				$is_obsolete = true;
				$line['type'] = substr($line['type'],9);
				preg_match('/".*"/',$line['value'],$m);
				$line['value'] = $m[0];
			}

			if($line['type'] == "string") {
				if($context == "msgid" || $context == "msgstr"){
					$entry[ $context ][] = $this->decodeStringFormat( $line['value'] );
				} else{
					throw new Exception("String in invalid position: " . $line["value"]);
				}
			} else {
				$context = $line["type"];
				if( $line["type"] == "msgid" || $line["type"] == "msgstr" )
					$entry[$line["type"]][] = $this->decodeStringFormat( $line["value"] );
				else
					$entry[$line["type"]][] = $line["value"];
			}
		}
		foreach($entry as $k=>&$v)
			$v	= implode((in_array($k,['msgid',"msgstr"])?'':"\n"),$v);
		$entry['isObsolete'] = $is_obsolete;
		return $entry;
	}
	function convertEntryToString( $entry ){
		$prefixes = [
			"comments"=>"# ", 
			"extractedComments"=>"#. ", 
			"reference"=>"#: ", 
			"flags"=>"#. ", 
			"previousUntranslatedString"=>"#| "
		];		
		$msg = "";
		foreach ( $entry as $k=>$v )
			if($v && @$prefixes[$k])
				$msg .= $this->addPrefixToLines(@$prefixes[$k],$v) . "\n";
		$msgid = 'msgid ' . $this->encodeStringFormat($entry['msgid']);
		$msgstr = 'msgstr ' . $this->encodeStringFormat($entry['msgstr']);
		if($entry['isObsolete']) {
			$msgid =	$this->addPrefixToLines('#~ ',$msgid);
			$msgstr = $this->addPrefixToLines('#~ ',$msgstr);
		}
		$msg .= $msgid . "\n";
		$msg .= $msgstr . "\n";
		$msg .= "\n";
		return $msg;
	}
}