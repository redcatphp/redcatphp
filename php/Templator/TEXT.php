<?php namespace Surikat\Templator;
use Surikat\Templator\TML;
use Surikat\Vars\STR;
class TEXT extends CORE{
	var $nodeName = 'TEXT';
	protected $hiddenWrap = true;
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		$this->nodeName = $nodeName;
		if($this->parent&&$this->parent->View&&$this->parent->View->isXhtml)
			$text = STR::cleanXhtml($text);
		
		$text = self::phpImplode($text,$constructor);
		$this->textInject($text,$nodeName);
		
		//$this->biohazard();
	}
	function biohazard(){
		if(!$this->parent||!$this->parent->antibiotique)
			$this->write(new TML('<loremipsum mini>'));
	}
	function textInject($text,$nodeName='TEXT'){
		if(strpos($text,'<?php ')===false)
			$this->innerHead($text);
		else{
			$open = 0;
			$php = '';
			$xml = '';
			$tokens = token_get_all($text);
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
							$this->childNodes[] = new PHP($this,'PHP',$php.($open===2&&substr(trim($php),-1)!=';'?';':'').'?>',$this->constructor);
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
			if($php)
				$this->childNodes[] = new PHP($this,'PHP',$php.($open===2?';'&&substr(trim($php),-1)!=';':'').'?>',$this->constructor);
			if($xml)
				$this->childNodes[] = new TEXT($this,$nodeName,$xml,$this);
		}
	}
}
