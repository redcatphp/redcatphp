<?php
use ObjexLoader\Container;
return [
	'timeCompiled'	=> time(),
	'BASE_HREF'		=> Container::get()->FluxServer_Url()->getBaseHref(),
];