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
		
		$a = [];
		$open = 0;
		$php = '';
		$xml = '';
		$tokens = token_get_all($text);
		foreach($tokens as $token){
			if(is_array($token)){
				switch($token[0]){
					case T_OPEN_TAG:
						$open = 1;
						$a[] = $xml;
						$xml = '';
						$php = '<?php ';
					break;
					case T_OPEN_TAG_WITH_ECHO:
						$open = 2;
						$a[] = $xml;
						$xml = '';
						$php = '<?php echo ';
					break;
					case T_CLOSE_TAG:
						$open = 0;
						$a[] = new PHP($this,'PHP',$php.($open===2&&substr(trim($php),-1)!=';'?';':'').'?>');
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
		if($open)
			$a[] = new PHP($this,'PHP',$php.($open===2?';'&&substr(trim($php),-1)!=';':'').'?>');
		else
			$a[] = $xml;
		$b = false;
		foreach($a as $v){
			if(!$b&&is_string($v)){
				$this->innerHead($v);
				$b = true;
			}
			else{
				if(is_string($v))
					$v = new TEXT($this,$nodeName,$v,$this);
				$this->childNodes[] = $v;
			}
		}
		//var_dump($a);exit;
		
		//$this->innerHead($text);
	}
	function biohazard(){
		if(!$this->parent||!$this->parent->antibiotique)
			$this->contentText = new TML('<loremipsum mini>');
	}
}
