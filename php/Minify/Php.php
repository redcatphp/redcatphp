<?php namespace Minify;
use ObjexLoader\MutatorFacadeTrait;
class Php {
	use MutatorFacadeTrait;
	var $minifyHTML;
	private $tokens = [];
	private $result;
	function _process($text,$minifyHTML=true){
		$this->tokens = [];
		$this->result = null;
		$this->minifyHTML = $minifyHTML;
		$this->add_tokens($text);
		return str_replace('?><?php','',$this);
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
			$l = strlen($this->result)-1;
			if(preg_match("~^\\w\\w$~", (isset($this->result[$l])?$this->result[$l]:'').$text[0]))
				$this->result .= " ";
			$this->result .= $text;
		}
	}
	private function compressLoopHTML(){
		$php = false;
		$html = '';
		foreach($this->tokens as $t){
			$text = $t[1];
			$l = strlen($this->result)-1;
			if(strlen($text)&&preg_match("~^\\w\\w$~",(isset($this->result[$l])?$this->result[$l]:'').$text[0]))
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
					$tmp = $this->_Html->process($text);
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
		$this->result = "";
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