<?php
namespace MyApp\Templix;
use RedCat\Ding\Di;
use MyApp\Route\Route;
class Templix extends \RedCat\Framework\Templix\Templix{
	function __construct(
		Route $route,
		$file=null,$vars=null,$devTemplate=true,$devJs=true,$devCss=true,$devImg=false,Di $di,$httpMtime=false,$httpEtag=false,$httpExpireTime=false
	){
		parent::__construct($file,$vars,$devTemplate,$devJs,$devCss,$devImg,$di,$httpMtime,$httpEtag,$httpExpireTime);
		
		$this['route'] = $route;
		
		$this->onCompile(function($tml)use($route){
			$tml('a')->each(function($a)use($route){
				$a->href = $route->resolveRoute($a->href);
			});
		});
	}
}