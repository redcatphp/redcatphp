var ie = (function(){var undef,v = 3,div = document.createElement('div'),all = div.getElementsByTagName('i');while(div.innerHTML = '<!--[if gt IE ' + (++v) + ']><i></i><![endif]-->',all[0]);return v>4?v:undef;}());
if(ie&&ie<9)
	$js(['html5shiv','local-storage.retro']);

//if(typeof window.matchMedia=="undefined"&&typeof window.msMatchMedia=="undefined")
	//$js('respond');
