<?php namespace Surikat\Templator;
use Surikat\Exception\ViewTML as ExceptionTML;
abstract class PARSER{
	const STATE_PROLOG_NONE = 0;
	const STATE_PROLOG_EXCLAMATION = 1;
	const STATE_PROLOG_DTD = 2;
	const STATE_PROLOG_INLINEDTD = 3;
	const STATE_PROLOG_COMMENT = 4;
	const STATE_PARSING = 5;
	const STATE_PARSING_COMMENT = 6;
	const STATE_NOPARSING = 7;
	const STATE_PARSING_OPENER = 8;
	const STATE_ATTR_NONE = 9;
	const STATE_ATTR_KEY = 10;
	const STATE_ATTR_VALUE = 11;
	const PIO = '*~#@?!?#+1';
	const PIC = '0+#@!?!#~*';	
	private static $PIO_L;
	private static $PIC_L;
	private static $PI_STR = [self::PIO,self::PIC];
	private static $PI_HEX;
	protected $parseReplacement = [
		'\\<'=>'&lt;',
		'\\>'=>'&gt;',
		'\\"'=>'&quot;',
	];
	static function initialize(){
		self::$PI_HEX = [self::strToHex(self::$PI_STR[0]),self::strToHex(self::$PI_STR[1])];
		self::$PIO_L = strlen(self::PIO);
		self::$PIC_L = strlen(self::PIC);
	}
	static function phpImplode($tid,$o){
		$a = &$o->__phpSRC;
		$open = null;
		$str = '';
		$id = '';
		for($i=0;$i<strlen($tid);$i++){
			if(substr($tid,$i,self::$PIO_L)==self::PIO){
				$open = true;
				$i+=self::$PIO_L-1;
			}
			elseif(substr($tid,$i,self::$PIC_L)==self::PIC){
				$open = false;
				$i+=self::$PIC_L-1;
				$str .= $a[$id];
				unset($a[$id]);
				$id = '';
			}
			elseif($open)
				$id .= $tid{$i};
			else
				$str .= $tid{$i};
		}
		if(substr($str,-3)=='=""')
			$str = substr($str,0,-3);
		return $str;
	}
	static function short_open_tag(&$s){
		$str = '';
		$c = strlen($s)-1;
		for($i=0;$i<=$c;$i++){
			if($s[$i].@$s[$i+1]=='<?'&&@$s[$i+2]!='='&&(@$s[$i+2].@$s[$i+3].@$s[$i+4])!='php'){
				$y = $i+2;
				$tmp = '<?php ';
				do{
					$p = strpos($s,'?>',$y);
					if($p===false)
						break;
					$p += 2;
					$tmp .= substr($s,$y,$p-$y);
					$tk = @token_get_all(trim($tmp));
					$tk = end($tk);
					$y = $p;
				}
				while(!(is_array($tk)&&$tk[0]===T_CLOSE_TAG));
				$str .= $tmp;
				$i = $y-1;
			}
			else
				$str .= $s[$i];
		}
		$s = $str;
		return $s;
	}
	private function parseML($xmlText){
		self::short_open_tag($xmlText);
		$tokens = token_get_all(str_replace(self::$PI_STR,self::$PI_HEX,$xmlText));
		$xmlText = '';
		$open = 0;
		$php = '';
		$xml = '';
		foreach($tokens as $token){
			if(is_array($token)){
				switch($token[0]){
					case T_OPEN_TAG:
						$open = 1;
						$xmlText .= $xml;
						$xml = '';
						$php = '<?php ';
					break;
					case T_OPEN_TAG_WITH_ECHO:
						$open = 2;
						$xmlText .= $xml;
						$xml = '';
						$php = '<?php echo ';
					break;
					case T_CLOSE_TAG:
						$uid = uniqid(null,true);
						$this->__phpSRC[$uid] = $php.($open===2&&substr(trim($php),-1)!=';'?';':'').'?>';
						$open = 0;
						$xmlText .= self::PIO.$uid.self::PIC;
						$php = '';
					break;
					default:
						if($open)
							$php .= $token[1];
						else
							$xml .= $token[1];
					break;
				}
			}
			else{
				if($open)
					$php .= $token;
				else
					$xml .= $token;
			}
		}
		if($open){
			$uid = uniqid(null,true);
			$this->__phpSRC[$uid] = $php.($open===2?';'&&substr(trim($php),-1)!=';':'').'?>';
			$xmlText .= self::PIO.$uid.self::PIC;
		}
		else
			$xmlText .= $xml;
		
		$xmlText = str_replace(array_keys($this->parseReplacement),array_values($this->parseReplacement),$xmlText);
		
		$state = self::STATE_PROLOG_NONE;
		$charContainer = '';
		$quoteType = '';
		$total = strlen($xmlText);
		for($i=0;$i<$total;$i++){
			$currentChar = $xmlText{$i};
			$this->characterNumber += 1;
			switch($currentChar){
				case "\n":
					$this->lineNumber += 1;
					$this->characterNumber = 1;
					$charContainer .= $currentChar;
				break;
				case '<':
					switch($state){
						case self::STATE_PARSING_OPENER:
						case self::STATE_PARSING:
							if ($xmlText{($i+1)}=='!'){
								$this->fireCharacterData($charContainer);
								if(substr($charContainer,1,7)!='[CDATA['&&substr($xmlText,$i+2,2)!='--'){
									$state = self::STATE_PROLOG_EXCLAMATION;
									$charContainer = '';
								}		
								$charContainer .= $currentChar;
							}
							else{
								$state = self::STATE_PARSING_OPENER;
								$this->fireCharacterData($charContainer);
								$charContainer = '';
							}
						break;
						case self::STATE_ATTR_VALUE:
						case self::STATE_NOPARSING:
						case self::STATE_PARSING_COMMENT:
							$charContainer .= $currentChar;
						break;
						default:
							$this->fireCharacterData($charContainer);
							$charContainer = '';
							if ($xmlText{($i+1)}=='!'){
								$this->fireCharacterData($charContainer);
								$state = self::STATE_PROLOG_EXCLAMATION;
								$charContainer .= $currentChar;
							}
							else {
								$state = self::STATE_PARSING;
								$i+=-1;
							}
						break;
					}
				break;
				case '=':
					switch($state){
						case self::STATE_PARSING_OPENER:
							$quote = $xmlText{($i+1)};
							$y = $i+2;
							$charContainer .= '='.$quote;
							while(($ch=$xmlText{($y++)})!=$quote){
								$charContainer .= $ch;
							}
							$charContainer .= $quote;
							$i = $y-1;
						break;
						case self::STATE_PARSING:
							if (substr($charContainer, 0, 8) == '![CDATA['){
								$charContainer .= $currentChar;
								break;
							}
						case self::STATE_NOPARSING:
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				case '-':
					switch($state){
						case self::STATE_PARSING_OPENER:
						case self::STATE_PARSING:						
							if (($xmlText{($i - 1)} == '-') && ($xmlText{($i - 2)} == '!')
								&& ($xmlText{($i - 3)} == '<')) {
								$state = self::STATE_PARSING_COMMENT;
								$charContainer = ' ';
							}
							else
								$charContainer .= $currentChar;							
						break;
						case self::STATE_PROLOG_EXCLAMATION:
							$state = self::STATE_PROLOG_COMMENT;	
							$charContainer = '';
						break;
						case self::STATE_PROLOG_COMMENT:
							if (!((($xmlText{($i + 1)} == '-')  && ($xmlText{($i + 2)} == '>')) || 
								($xmlText{($i + 1)} == '>') ||
								(($xmlText{($i - 1)} == '-')  && ($xmlText{($i - 2)}== '!')) ))
								$charContainer .= $currentChar;
						break;
						case self::STATE_NOPARSING:
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				case '"':
				case "'":
					switch($state){
						case self::STATE_PARSING_OPENER:
							$state = self::STATE_ATTR_VALUE;
							$quoteType = $currentChar;
						break;
						case self::STATE_ATTR_VALUE:
							if($quoteType==$currentChar)
								$state = self::STATE_PARSING_OPENER;
						break;
					}
					$charContainer .= $currentChar;
				break;
				case '>':
					switch($state){
						case self::STATE_NOPARSING:
							$on = $this->currentTag;
							$nn = $on->nodeName;
							$nnn = '</'.$nn.'>';
							$lnn = strlen($nnn)*-1;
							$charContainer .= $currentChar;
							if(substr($charContainer,$lnn)==$nnn){
								$charContainer = substr($charContainer,0,$lnn);
								if(trim($charContainer)){
									$textNode = new TEXT();
									$textNode->setParent($on);
									$textNode->setNodeName('TEXT_UNPARSED');
									$textNode->setBuilder($this);
									$textNode->parse($charContainer);
									$on[] = $textNode;
								}
								$this->fireEndElement($nn);
								$charContainer = '';
								$state = self::STATE_PARSING;
							}
						break;
						case self::STATE_PARSING_OPENER:						
						case self::STATE_PARSING:						
							if ((substr($charContainer, 0, 8) == '![CDATA[') &&
								!((self::getCharFromEnd($charContainer, 0) == ']') &&
								(self::getCharFromEnd($charContainer, 1) == ']'))) {
								$charContainer .= $currentChar;
							}
							else {
								$state = self::STATE_PARSING;
								$charContainer = trim($charContainer);			
								$firstChar = @$charContainer{0};
								$myAttributes = [];
								switch($firstChar){
									case '/':
										$tagName = substr($charContainer, 1);
										$this->fireEndElement($tagName);
									break;
									case '!':
										$upperCaseTagText = strtoupper($charContainer);
										if (strpos($upperCaseTagText, '![CDATA[') !== false) {
											$openBraceCount = 0;
											$textNodeText = '';
											for($y=0;$y<strlen($charContainer);$y++) {
												$currentChar = $charContainer{$y};
												if (($currentChar == ']') && ($charContainer{($y + 1)} == ']'))
													break;
												else if ($openBraceCount > 1)
													$textNodeText .= $currentChar;
												else if ($currentChar == '[')
													$openBraceCount++;
											}
											$this->fireCDataSection($textNodeText);
										}
									break;
									default:
										if ((strpos($charContainer, '"') !== false) || (strpos($charContainer, "'") !== false)){
											$tagName = '';
											for($y=0;$y<strlen($charContainer);$y++){
												$currentChar = $charContainer{$y};
												if (($currentChar == ' ') || ($currentChar == "\t") ||
													($currentChar == "\n") || ($currentChar == "\r") ||
													($currentChar == "\x0B")) {
													$myAttributes = self::parseAttributes(substr($charContainer, $y));
													break;
												}
												else
													$tagName .= $currentChar;
											}
											if (strrpos($charContainer, '/')==(strlen($charContainer)-1)){
												$this->fireElement($tagName, $myAttributes);
											}
											else
												$this->fireStartElement($tagName, $myAttributes, $state);
										}
										else{
											if(strpos($charContainer,' ')!==false){
												$x = explode(' ',$charContainer);
												$charContainer = array_shift($x);
												foreach($x as $k)
													if($k=='/')
														$charContainer .= '/';
													else
														$myAttributes[] = $k;
											}
											if (strpos($charContainer, '/') !== false) {
												$charContainer = trim(substr($charContainer, 0, (strrchr($charContainer, '/') - 1)));
												$this->fireElement($charContainer, $myAttributes);
											}
											else {
												$this->fireStartElement($charContainer, $myAttributes, $state);
											}
										}
									break;					
								}
								$charContainer = '';
							}
						break;
						case self::STATE_PROLOG_COMMENT:
							$state = self::STATE_PROLOG_NONE;
							$this->fireComment($charContainer);
							$charContainer = '';
						break;
						case self::STATE_PROLOG_DTD:
							$state = self::STATE_PROLOG_NONE;
							$this->fireDTD($charContainer.$currentChar);
							$charContainer = '';
						break;
						case self::STATE_PROLOG_INLINEDTD:
							if($xmlText{($i-1)}==']'){
								$state = self::STATE_PROLOG_NONE;
								$this->fireDTD($charContainer.$currentChar);						
								$charContainer = '';
							}
							else
								$charContainer .= $currentChar;
						break;
						case self::STATE_PARSING_COMMENT:
							if(($xmlText{($i-1)}=='-')&&($xmlText{($i - 2)}=='-')){
								$this->fireComment(substr($charContainer,0,strlen($charContainer)-2));
								$charContainer = '';
								$state = self::STATE_PARSING;
							}
							else
								$charContainer .= $currentChar;
						break;
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				case 'D':
					switch($state){
						case self::STATE_PROLOG_EXCLAMATION:
							$state = self::STATE_PROLOG_DTD;	
							$charContainer .= $currentChar;
						break;
						case self::STATE_NOPARSING:
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				case '[':
					switch($state){
						case self::STATE_PROLOG_DTD:
							$charContainer .= $currentChar;
							$state = self::STATE_PROLOG_INLINEDTD;
						break;
						case self::STATE_NOPARSING:
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				default:
					$charContainer .= $currentChar;
				break;
			}
		}
		$this->fireCharacterData($charContainer);
		switch($state){
			case self::STATE_NOPARSING:
				$this->throwException('Unexpected end of file, expected end of noParse Tag &lt;/'.$this->currentTag->nodeName.'&gt;');
			break;
			case self::STATE_PARSING_COMMENT:
				$this->throwException('Unexpected end of file, expected end of comment Tag --&gt;');
			break;
			case self::STATE_PARSING:
			default:
				if($this->currentTag&&$this->currentTag->nodeName&&!$this->currentTag->__closed&&!$this->currentTag->selfClosed)
					$this->throwException('Unexpected end of file, expected end of Tag &lt;/'.$this->currentTag->nodeName.'&gt;');
			break;
		}
	}
	private static function getCharFromEnd($text, $index) {
		$len = strlen($text);
		$char = $text{($len - 1 - $index)};
		return $char;
	}
	private static function parseAttributes($attrText){
		$attrArray = [];
		$total = strlen($attrText);
		$keyDump = '';
		$valueDump = '';
		$currentState = self::STATE_ATTR_NONE;
		$quoteType = '';
		$keyDumpI = 0;
		for($i=0;$i<$total;$i++){	
			$currentChar = $attrText{$i};
			if($currentState==self::STATE_ATTR_NONE&&trim($currentChar))
				$currentState = self::STATE_ATTR_KEY;
			switch ($currentChar){
				case '=':
					if ($currentState == self::STATE_ATTR_VALUE)
						$valueDump .= $currentChar;
					else {
						$currentState = self::STATE_ATTR_VALUE;
						$quoteType = '';
					}
				break;
				case '"':
					if ($currentState == self::STATE_ATTR_VALUE) {
						if ($quoteType=='')
							$quoteType = '"';
						elseif ($quoteType == $currentChar) {
							$keyDump = trim($keyDump);
							$attrArray[$keyDump] = trim($valueDump)?$valueDump:'';
							$keyDump = $valueDump = $quoteType = '';
							$currentState = self::STATE_ATTR_NONE;
						}
						else
							$valueDump .= $currentChar;
					}
					else{
						$keyDump = $keyDumpI++;
						$valueDump = '';
						$currentState = self::STATE_ATTR_VALUE;
						$quoteType = '"';
					}
				break;
				case "'":
					if ($currentState == self::STATE_ATTR_VALUE) {
						if ($quoteType == '')
							$quoteType = "'";
						elseif ($quoteType == $currentChar){
							$keyDump = trim($keyDump);
							$attrArray[$keyDump] = trim($valueDump)?$valueDump:'';
							$keyDump = $valueDump = $quoteType = '';
							$currentState = self::STATE_ATTR_NONE;
						}
						else
							$valueDump .= $currentChar;
					}
					else{
						$keyDump = $keyDumpI++;
						$valueDump = '';
						$currentState = self::STATE_ATTR_VALUE;
						$quoteType = "'";
					}
				break;
				case "\t":
				case "\x0B":
				case "\n":
				case "\r":
				case ' ':
					if($currentState==self::STATE_ATTR_KEY){
						$currentState = self::STATE_ATTR_NONE;
						if($keyDump)
							$attrArray[] = trim($keyDump);
						$keyDump = $valueDump = $quoteType = '';
					}
					elseif($currentState==self::STATE_ATTR_VALUE)
						$valueDump .= $currentChar;
				break;
				default:
					if ($currentState == self::STATE_ATTR_KEY)
						$keyDump .= $currentChar;
					else
						$valueDump .= $currentChar;
				break;
			}
		}
		if(trim($keyDump))
			$attrArray[] = trim($keyDump);
		return $attrArray;
	}
	private static function strToHex($s){
		$h = '';
		for ($i=0;$i<strlen($s);$i++)
			$h .= '&#'.ord($s{$i}).';';
		return $h;
	}
	protected static function checkPIOC($check){
		return strpos($check,self::PIO)!==false&&strpos($check,self::PIC)!==false;
	}

	private $currentTag;
	private $__phpSRC = [];

	protected $onLoad = [];
	protected $onLoaded = [];
	
	protected $lineNumber = 1;
	protected $characterNumber = 1;
	
	private function addToCurrent($name,$attributes,$class=null){
		if(!$this->currentTag)
			$this->currentTag = $this;
		if(($pos=strpos($name,'+'))!==false){
			$x = explode('+',$name);
			$a = [];
			$node = new Group();
			$node->setBuilder($this);
			$node->setParent($this->currentTag);
			$node->setNodeName($name);
			$node->make($attributes);
			$node->lineNumber = $this->lineNumber;
			$node->characterNumber = $this->characterNumber;
			$sc = null;
			foreach($x as $n){
				$c = self::getClass($n);
				$g = new $c();
				$g->setBuilder($this);
				$g->setParent($this->currentTag);
				$g->setNodeName($n);
				$g->make($attributes);
				$sc = $g->selfClosed&&$sc!==false;
				$node->addToGroup($g);
			}
			if($sc)
				$node->selfClosed = true;
		}
		else{
			if($class===true)
				$class = 'Templator\\'.$name;
			$c = $class?$class:self::getClass($name);
			$node = new $c();
			$node->setBuilder($this);
			$node->setParent($this->currentTag);
			$node->setNodeName($name);
			$node->make($attributes);
			$node->lineNumber = $this->lineNumber;
			$node->characterNumber = $this->characterNumber;
		}
		$this->currentTag[] = $node;
		return $node;
	}
	private function fireElement($name,$attributes){
		$attributes['/'] = '';
		if(($pos=strpos($name,'&'))!==false){
			$x = explode('&',$name);
			$name = array_pop($x);
			foreach($x as $n)
				$this->fireElement($n,$attributes);
		}
		$this->addToCurrent($name,$attributes)->closed();
	}
	private function fireStartElement($name,$attributes,&$state=null){
		if(($pos=strpos($name,'&'))!==false){
			$x = explode('&',$name);
			$name = array_pop($x);
			foreach($x as $n)
				$this->fireStartElement($n,$attributes);
		}
		$this->currentTag = $this->addToCurrent($name,$attributes);
		if($this->currentTag->noParseContent)
			$state = self::STATE_NOPARSING;
		if($this->currentTag->selfClosed===true){
			$this->currentTag->closed();
			if($this->currentTag->parent)
				$this->currentTag = $this->currentTag->parent;
		}
	}
	private function fireEndElement($name){
		if(($pos=strpos($name,'&'))!==false){
			$x = explode('&',$name);
			$x = array_reverse($x);
			$name = array_pop($x);
			foreach($x as $n)
				$this->fireEndElement($n);
		}
		if($name!=$this->currentTag->nodeName)
			$this->throwException('Unexpected &lt;/'.$name.'&gt;, expected &lt;/'.$this->currentTag->nodeName.'&gt;');
		$this->currentTag->closed();
		if($this->currentTag->parent)
			$this->currentTag = $this->currentTag->parent;
	}
	private function fireDTD($doctype){
		$this->addToCurrent('DOCTYPE',$doctype,true);
	}
	private function fireComment($comment){
		$this->addToCurrent('COMMENT',$comment,true);
	}
	private function fireCharacterData($text){
		if(trim($text))
			$this->addToCurrent('TEXT',$text,true);
	}
	private function fireCDataSection($text){
		$this->addToCurrent('CDATA',$text,true);
	}

	protected static function getClass($n){
		if($p=strpos($n,':'))
			$n = substr($n,0,$p);
		$n = strtolower($n);
		$n = str_replace('-','_',$n);
		if(class_exists($c='Templator\\'.(ctype_upper($n)?$n:'TML_'.ucfirst($n))))
			return $c;
		return 'Templator\\TML';
	}
	function evalue($v,$vars=null){
		if(isset($vars))
			extract($vars);
		ob_start();
		eval('?>'.$v);
		return ob_get_clean();
	}
	function evaluate(){
		return ob_start()&&eval('?>'.$this)!==false?ob_get_clean():'';
	}
	function parse($arg){
		$this->clean();
		if(!is_string($arg))
			$arg = "$arg";
		$n = func_num_args();
		if($n>1&&($params=func_get_arg(1)))
			foreach((array)$params as $k=>$v)
				$arg = str_replace('{{:'.$k.':}}',$v,$arg);
		$pos = 0;
		if(preg_match_all('/\\{\\{::(.*?)::\\}\\}/', $arg, $matches))
			foreach($matches[1] as $i=>$eve)
				$arg = substr($arg,0,$pos=strpos($arg,$matches[0][$i],$pos)).$this->evalue($eve).substr($arg,$pos+strlen($matches[0][$i]));
		$this->parseML($arg);
		if($n<3||!func_get_arg(2))
			$this->triggerLoaded();
	}
	function make($arg){
		if(is_string($arg)){
			$this->parse($arg);
		}
		else{
			$this->interpret($arg);
		}
	}
	protected function interpret($attributes,$nodeName=null){
		if(isset($nodeName))
			$this->nodeName = $nodeName;
		if(($pos=strpos($this->nodeName,':'))!==false){
			$this->namespace = ucfirst(substr($this->nodeName,0,$pos));
			$this->namespaceClass = ucfirst(substr($this->nodeName,$pos+1));
			$this->_namespaces = explode(':',trim($this->namespace.':'.$this->namespaceClass,':'));
			foreach(array_keys($this->_namespaces) as $i)
				$this->_namespaces[$i] = ucfirst($this->_namespaces[$i]);
		}
		$this->metaAttribution = $attributes;
		$this->opened();
	}
	protected function throwException($msg){
		if($this->Template)
			$msg .= $this->exceptionContext();
		throw new ExceptionTML($msg);
	}
	function exceptionContext(){
		return ' on "'.$this->Template->getPath().':'.$this->lineNumber.'#'.$this->characterNumber.'"';
	}
}
PARSER::initialize();