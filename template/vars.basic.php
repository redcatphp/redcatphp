<?php
use Surikat\Component\DependencyInjection\Container;
return [
	'timeCompiled'	=> time(),
	'BASE_HREF'		=> Container::get()->Http_Url()->getBaseHref(),
];