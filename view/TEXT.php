<?php namespace surikat\view;
use surikat\view\TML;
use surikat\control\str;
class TEXT extends CORE{
	var $nodeName = 'TEXT';
	protected $hiddenWrap = true;
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		if($this->parent&&$this->parent->vFile&&$this->parent->vFile->isXhtml)
			$text = str::cleanXhtml($text);
		$text = self::phpImplode($text,$constructor);
		
		if(strpos('<?php ',$text)===false)
			$this->innerHead($text);
		else{
			$open = 0;
			$php = '';
			$xml = '';
			$tokens = token_get_all($text);
			$b = false;
			foreach($tokens as $token){
				if(is_array($token)){
					switch($token[0]){
						case T_OPEN_TAG:
							$open = 1;
							if($xml)
								$this->childNodes[] = new TEXT($this,$nodeName,$xml,$this);
							$xml = '';
							$php = '<?php ';
						break;
						case T_OPEN_TAG_WITH_ECHO:
							$open = 2;
							if($xml)
								$this->childNodes[] = new TEXT($this,$nodeName,$xml,$this);
							$xml = '';
							$php = '<?php echo ';
						break;
						case T_CLOSE_TAG:
							$open = 0;
							$this->childNodes[] = new PHP($this,'PHP',$php.($open===2&&substr(trim($php),-1)!=';'?';':'').'?>');
							$b = true;
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
			if(trim($php)){
				$this->childNodes[] = new PHP($this,'PHP',$php.($open===2?';'&&substr(trim($php),-1)!=';':'').'?>');
				$b = true;
			}
			if(trim($xml))
				$this->childNodes[] = new TEXT($this,$nodeName,$xml,$this);
		}
	}
	function biohazard(){
		if(!$this->parent||!$this->parent->antibiotique)
			$this->contentText = new TML('<loremipsum mini>');
	}
}
