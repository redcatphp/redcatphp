<?php namespace surikat\view;
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
	
	 //const PIO = '#PHP_ID_OPEN#';
	 //const PIC = '#PHP_ID_CLOSE#';
	 //const PIO = '#@?!?#';
	 //const PIC = '#@!?!#';
	const PIO = '*~#@?!?#+1';
	const PIC = '0+#@!?!#~*';
	
	private static $PIO_L;
	private static $PIC_L;
	private static $PI_STR = array(self::PIO,self::PIC);
	private static $PI_HEX;
	static function initialize(){
		self::$PI_HEX = array(self::strToHex(self::$PI_STR[0]),self::strToHex(self::$PI_STR[1]));
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
				unset($a[$id]); //freeing memory!
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
	//private static $short_open_tag = array(
		//'<?'=>'<?php ',
		//'<?php php'=>'<?php ',
		//'<?php ='=>'<?=',
	//);
	private function short_open_tag(&$s){ //first is faster but second more strict
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
		//$xmlText = str_replace(array_keys(self::$short_open_tag),array_values(self::$short_open_tag),$xmlText);
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
		$state = self::STATE_PROLOG_NONE;
		$charContainer = '';
		$quoteType = '';
		$xmlText = trim($xmlText);
		$total = strlen($xmlText);
		for($i=0;$i<$total;$i++){
			$currentChar = $xmlText{$i};
			if($state===self::STATE_NOPARSING){
				$on = $this->currentTag;
				$nn = $on->nodeName;
				$nnn = '</'.$nn.'>';
				$lnn = strlen($nnn)*-1;
				$charContainer .= $currentChar;
				if($currentChar=='>'&&substr($charContainer,$lnn)==$nnn){
					$charContainer = substr($charContainer,0,$lnn);
					if(trim($charContainer))
						$on[] = new TEXT($on,'TEXT',$charContainer,$this);
					$this->fireEndElement($nn);
					$charContainer = '';
					$state = self::STATE_PARSING;
				}
			}
			else{
				switch($currentChar){
					case '<':
						switch($state){
							case self::STATE_PARSING_OPENER:
							case self::STATE_PARSING:
								if(substr($charContainer,0,8)=='![CDATA[')
									$charContainer .= $currentChar;
								else{
									$state = self::STATE_PARSING_OPENER;
									if(trim($charContainer))
										$this->fireCharacterData($charContainer);
									$charContainer = '';
								}
							break;
							case self::STATE_PARSING_COMMENT:
								$charContainer .= $currentChar;
							break;
							default:
								if(trim($charContainer))
									$this->fireCharacterData($charContainer);
								$charContainer = '';
								if ($xmlText{($i+1)}=='!'){
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
								while(($ch=$xmlText{($y++)})!=$quote)
									$charContainer .= $ch;
								$charContainer .= $quote;
								$i = $y-1;
							break;
							case self::STATE_PARSING:
								if (substr($charContainer, 0, 8) == '![CDATA['){
									$charContainer .= $currentChar;
									break;
								}
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
							case self::STATE_PARSING_OPENER:						
							case self::STATE_PARSING:						
								if ((substr($charContainer, 0, 8) == '![CDATA[') &&
									!((self::getCharFromEnd($charContainer, 0) == ']') &&
									(self::getCharFromEnd($charContainer, 1) == ']'))) {
									$charContainer .= $currentChar;
								}
								else {
									$state = self::STATE_PARSING;
									//parseTag
									$charContainer = trim($charContainer);			
									$firstChar = @$charContainer{0};
									$myAttributes = array();
									switch($firstChar){
										case '/':
											$tagName = substr($charContainer, 1);				
											$this->fireEndElement($tagName);
										break;
										case '!':
											$upperCaseTagText = strtoupper($charContainer);
											if (strpos($upperCaseTagText, '![CDATA[') !== false) { //CDATA Section
												$openBraceCount = 0;
												$textNodeText = '';
												for($y=0;$y<strlen($charContainer);$y++) {
													$currentChar = $charContainer{$y};
													if (($currentChar == ']') && ($charContainer{($y + 1)} == ']'))
														break;
													else if ($openBraceCount > 1)
														$textNodeText .= $currentChar;
													else if ($currentChar == '[') //this won't be reached after the first open brace is found
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
												if (strrpos($charContainer, '/')==(strlen($charContainer)-1)){ //check $charContainer, but send $tagName
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
						switch ($state) {
							case self::STATE_PROLOG_EXCLAMATION:
								$state = self::STATE_PROLOG_DTD;	
								$charContainer .= $currentChar;
							break;
							default:
								$charContainer .= $currentChar;
							break;
						}
					break;
					case '[':
						switch ($state) {						
							case self::STATE_PROLOG_DTD:
								$charContainer .= $currentChar;
								$state = self::STATE_PROLOG_INLINEDTD;
							break;
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
		}
		if($charContainer)
			$this->fireCharacterData($charContainer);
	}
	private static function getCharFromEnd($text, $index) {
		$len = strlen($text);
		$char = $text{($len - 1 - $index)};
		return $char;
	}
	private static function parseAttributes($attrText){
		$attrText = trim($attrText);
		$attrArray = array();
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
	private $__phpSRC = array();

	protected $onLoad = array();
	protected $onLoaded = array();
	private function addToCurrent($name,$attributes){
		if(!$this->currentTag)
			$this->currentTag = $this;
		if(($pos=strpos($name,'+'))!==false){
			$x = explode('+',$name);
			$a = array();
			$node = new Group($this->currentTag,$name,$attributes,$this);
			$sc = null;
			foreach($x as $n){
				$c = self::getClass($n);
				$g = new $c($this->currentTag,$n,$attributes,$this);
				$sc = $g->selfClosed&&$sc!==false;
				$node->addToGroup($g);
			}
			if($sc)
				$node->selfClosed = true;
		}
		else{
			$c = self::getClass($name);
			$node = new $c($this->currentTag,$name,$attributes,$this);
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
		$this->addToCurrent(strtolower($name),$attributes)->closed();
	}
	private function fireStartElement($name,$attributes,&$state=null){
		if(($pos=strpos($name,'&'))!==false){
			$x = explode('&',$name);
			$name = array_pop($x);
			foreach($x as $n)
				$this->fireStartElement($n,$attributes);
		}
		$this->currentTag = $this->addToCurrent(strtolower($name),$attributes);
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
			throw new \UnexpectedValueException('Unexpected &lt;/'.$name.'&gt;, expected &lt;/'.$this->currentTag->nodeName.'&gt; in "'.$this->vFile->path.'"  ');
		$this->currentTag->closed();
		if($this->currentTag->parent)
			$this->currentTag = $this->currentTag->parent;
	}
	private function fireDTD($doctype){
		$this->addToCurrent('DOCTYPE',$doctype);
	}
	private function fireComment($comment){
		$this->addToCurrent('COMMENT',$comment);
	}
	private function fireCharacterData($text){
		$this->addToCurrent('TEXT',$text);
	}
	private function fireCDataSection($text){
		$this->addToCurrent('CDATA',$text);
	}

	protected static function getClass($n){
		if($p=strpos($n,':'))
			$n = substr($n,0,$p);
		$n = str_replace('-','_',$n);
		if(class_exists($c='view\\'.(ctype_upper($n)?$n:'TML_'.ucfirst($n))))
			return $c;
		return 'view\\TML';
	}

	protected function parse($arg,$params=null,$noload=null){
		$this->clean();
		if(!is_string($arg))
			$arg = "$arg";
		if(isset($params))
			foreach((array)$params as $k=>$v)
				$arg = str_replace('{{:'.$k.':}}',$v,$arg);
		$pos = 0;
		if(preg_match_all('/\\{\\{::(.*?)::\\}\\}/', $arg, $matches))
			foreach($matches[1] as $i=>$eve)
				$arg = substr($arg,0,$pos=strpos($arg,$matches[0][$i],$pos)).eval('return '.$eve.';').substr($arg,$pos+strlen($matches[0][$i]));
		$this->parseML($arg);
		if(!$noload)
			$this->triggerLoaded();
	}
	protected function interpret($args){
		$this->nodeName = array_shift($args);
		if(($pos=strpos($this->nodeName,':'))!==false){
			$this->namespace = substr($this->nodeName,0,$pos);
			$this->namespaceClass = substr($this->nodeName,$pos+1);
			$this->_namespaces = explode(':',trim($this->namespace.':'.$this->namespaceClass,':'));
		}
		$this->metaAttribution = (array)array_shift($args);
		$this->constructor = array_shift($args);
		$this->opened();
	}
}
PARSER::initialize();