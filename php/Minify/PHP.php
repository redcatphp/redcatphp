<?php namespace Surikat\Minify; 
class PHP {
	var $minifyHTML;
	private $tokens = [];
	private $head;
	private $result;
	static function minify($out,$head='',$minifyHTML=true){
		$o = new self($out,$head,$minifyHTML);
		$out = str_replace('?><?php','',$o);
		unset($o);
		return $out;
	}
	function __construct($text,$head=null,$minifyHTML=null){
		$this->add_tokens($text);
		$this->head = $head;
		$this->minifyHTML = $minifyHTML;
	}
	function __toString() {
		$this->remove_public_modifier();
		$str = $this->generate_result();
		return "$str";
	}
	private function compressLoop(){
		foreach($this->tokens as $t) {
			$text = $t[1];
			if(!strlen($text))
				continue;
			if(preg_match("~^\\w\\w$~", $this->result[strlen($this->result) - 1] . $text[0]))
				$this->result .= " ";
			$this->result .= $text;
		}
	}
	private function compressLoopHTML(){
		$php = true;
		$html = '';
		foreach($this->tokens as $t){
			$text = $t[1];
			if(strlen($text)&&preg_match("~^\\w\\w$~",$this->result[strlen($this->result)-1].$text[0]))
				$this->result .= ' ';
			if(($tt=trim($text))=='?>'){
				$this->result .= $text;
				$php = false;
			}
			elseif($tt=='<?php'||$tt=='<?'){
				$php = true;
				$this->result .= $text;
				if(substr($text,-1)!=' ')
					$this->result .= ' ';
			}
			else{
				if(!$php){
					$tmp = HTML::minify($text);
					if(preg_match("/\\s/",substr($text,-1)))
						$tmp .= ' ';
					if(preg_match("/\\s/",substr($text,0,1)))
						$tmp = ' '.$tmp;
					$text = $tmp;
				}
				$this->result .= $text;
			}
		}
	}
	private function generate_result() {
		$this->result = "<?php ";
		if($this->head)
			$this->result .= $this->head;
		if($this->minifyHTML)
			$this->compressLoopHTML();
		else
			$this->compressLoop();
		return $this->result;
	}
	private function remove_public_modifier() { 
		for($i = 0; $i < count($this->tokens) - 1; $i++) {
			if($this->tokens[$i][0] == T_PUBLIC){
				if($this->tokens[$i-1][0] == T_STATIC){
					$this->tokens[$i] = $this->tokens[$i + 1][1][0] == '$' ? ["", ""] : [-1, ""]; //added by surikat
				}
				else{
					$this->tokens[$i] = $this->tokens[$i + 1][1][0] == '$' ? [T_VAR, "var"] : [-1, ""];
				}
			}
		}            
	}
	private function add_tokens($text) {            
		$tokens = token_get_all(trim($text));
		if(!count($tokens))
			return;
		
		if(is_array($tokens[0]) && $tokens[0][0] == T_OPEN_TAG)
			array_shift($tokens);
			
		$last = count($tokens) - 1;
		if(is_array($tokens[$last]) && $tokens[$last][0] == T_CLOSE_TAG)
			array_pop($tokens);
		$pending_whitespace = count($this->tokens) ? "\n" : "";
		foreach($tokens as $t) {
			if(!is_array($t))
				$t = [-1, $t];
			if($t[0] == T_COMMENT || $t[0] == T_DOC_COMMENT)
				continue;
			if($t[0] == T_WHITESPACE) {
				$pending_whitespace .= $t[1];
				continue;
			}				
			$this->tokens[] = $t;        
			$pending_whitespace = "";
		}
	}
	private function encode_id($value) {                                
		$result = "";            
		if($value > 52) {
			$result .= $this->encode_id_digit($value % 53);
			$value = floor($value / 53);
		}            
		while($value > 62) {
			$result .= $this->encode_id_digit($value % 63);
			$value = floor($value / 63);
		}
		$result .= $this->encode_id_digit($value);
		return $result;
	}
	private function encode_id_digit($digit) {
		if($digit < 26)
			return chr(65 + $digit);
		if($digit < 52)
			return chr(71 + $digit);
		if($digit == 52)
			return "_";
		return chr($digit - 5);
	}    
}
