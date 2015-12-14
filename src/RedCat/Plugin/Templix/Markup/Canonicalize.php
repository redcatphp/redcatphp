<?php
namespace RedCat\Plugin\Templix\Markup;
use RedCat\Wire\Di;
class Canonicalize extends \RedCat\Templix\Markup{
	protected $hiddenWrap = true;
	protected $selfClosed = true;
	function load(){
		$this->remapAttr('domains');
		$this->attr('domains',eval('return '.$this->domains.';'));
		if($this->attr('no-cache')){
			$href = "<?php echo \RedCat\Wire\Di::getInstance()->create('RedCat\Route\Url')->getCanonical(".var_export($this->attr('domains'),true).','.($this->attr('http-substitution')?'true':'false').','.($this->attr('static')?'true':'false').');?>';
		}
		else{
			$url = Di::getInstance()->create('RedCat\Route\Url');
			$canonical = $url->getCanonical($this->attr('domains'),!!$this->attr('http-substitution'),$this->attr('static'));
			$canonical2 = $url->getCanonical($this->attr('domains'),'%s',$this->attr('static'));
			if($this->attr('http-substitution'))
				$href = '<?php echo http_response_code()===200?'.var_export($canonical,true).':sprintf('.var_export($canonical2,true).',http_response_code());?>';
			else
				$href = $canonical;
			
		}
		$this->write('<link rel="canonical" href="'.$href.'">');
	}
}