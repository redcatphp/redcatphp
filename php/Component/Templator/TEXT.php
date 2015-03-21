<?php namespace Surikat\Templator;
use Surikat\Templator\TML;
use Surikat\Vars\STR;
class TEXT extends CORE{
	var $nodeName = 'TEXT';
	protected $hiddenWrap = true;
	function parse($text){
		if($this->parent&&$this->parent->Template&&$this->parent->Template->isXhtml)
			$text = STR::cleanXhtml($text);
		$text = self::phpImplode($text,$this->constructor);
		$this->textInject($text);
		//$this->biohazard();
	}
	function biohazard(){
		if(!$this->parent||!$this->parent->antibiotique)
			$this->write(new TML('<loremipsum mini>'));
	}
	function textInject($text){
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
							if($xml){
								$textNode = new TEXT();
								$textNode->setParent($this);
								$textNode->setBuilder($this);
								$textNode->parse($xml);
								$this->childNodes[] = $textNode;
							}
							$xml = '';
							$php = '<?php ';
						break;
						case T_OPEN_TAG_WITH_ECHO:
							$open = 2;
							if($xml){
								$textNode = new TEXT();
								$textNode->setParent($this);
								$textNode->setBuilder($this);
								$textNode->parse($xml);
								$this->childNodes[] = $textNode;
							}
							$xml = '';
							$php = '<?php echo ';
						break;
						case T_CLOSE_TAG:
							$open = 0;
							$phpNode = new PHP();
							$phpNode->setParent($this);
							$phpNode->setBuilder($this->constructor);
							$phpNode->parse($php.($open===2&&substr(trim($php),-1)!=';'?';':'').'?>');
							$this->childNodes[] = $phpNode;
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
			if($php){
				$phpNode = new PHP();
				$phpNode->setParent($this);
				$phpNode->setBuilder($this->constructor);
				$phpNode->parse($php.($open===2?';'&&substr(trim($php),-1)!=';':'').'?>');
				$this->childNodes[] = $phpNode;
			}
			if($xml){
				$textNode = new TEXT();
				$textNode->setParent($this);
				$textNode->setBuilder($this);
				$textNode->parse($xml);
				$this->childNodes[] = $textNode;
			}
		}
	}
}
