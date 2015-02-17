<?php namespace Surikat\Vars;
class JSON{
	const VAL_TEXT= 12001;
	const VAL_STRING= 12002;
	const VAL_REGEXP= 12003;
	const VAL_COMMT1= 12004;
	const VAL_COMMT2= 12005;
	private $forceArray;
	private function __construct($type,$forceArray){
		$this->forceArray = $forceArray;
		$this->fields = ($type=='[')||$this->forceArray?[]:new \stdClass();
	}
	private function add_name(&$text){
		$this->name = $text;
		$text = '';
	}
	private function add_value(&$text){
		if (!isset ($this->name)) @$this->fields[] = $text; // weird input like a mix of fields and array elements will cause warnings here
		elseif($this->forceArray) $this->fields[$this->name] = $text;
		else                      $this->fields->{$this->name} = $text;
		$text = '';
	}
	static function decode($json,$forceArray=null){
		$stack =  [];
		$text = "";
		$state = self::VAL_TEXT;
		$len = strlen($json);
		for($i=0;$i!=$len;$i++){
			$c = $json[$i];
			switch ($state){
				case self::VAL_TEXT:
					switch ($c){
						case '{' :
						case '[' : array_unshift($stack,new JSON($c,$forceArray)); break;
						case '}' :
						case ']' : $stack[0]->add_value($text); $text = array_shift ($stack)->fields; break;
						case ':' : $stack[0]->add_name($text); break;
						case ',' : $stack[0]->add_value($text); break;
						case '"' :
						case "'" : $closer = $c; $state = self::VAL_STRING; break;
						case '/' :
							assert($i != ($len-1));
							switch ($json[$i+1]){
								case '/': $state = self::VAL_COMMT1; break;
								case '*': $state = self::VAL_COMMT2; break;
								default : $state = self::VAL_REGEXP; $text .= $c;
							}
						break;
						case "\r":
						case "\n":
						case "\t":
						case ' ' : break;
						default  : $text .= $c;
					}
				break;
				case self::VAL_STRING: if  ($c != $closer)               $text .= $c; else $state = self::VAL_TEXT; break;
				case self::VAL_REGEXP: if (($c !=  ',') && ($c !=  '}')) $text .= $c; else { $i--; $state = self::VAL_TEXT; } break;
				case self::VAL_COMMT1: if (($c == "\r") || ($c == "\n")) $state = self::VAL_TEXT; break;
				case self::VAL_COMMT2:
					if ($c != '*') break;
					assert($i != ($len-1));
					if ($json[$i+1] == '/') { $i++; $state = self::VAL_TEXT; }
			}
		}
		assert($state==self::VAL_TEXT);
		return $text;
	}
	public static function header(){
		header("Content-type: application/json; charset=utf-8");
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Pragma: no-cache");
	}
}