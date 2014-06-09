$js('jquery',function(){
	var loc = document.location.pathname;
	var i = loc.indexOf(':');
	var i2 = loc.indexOf('|');
	if(i!==-1)
		loc = loc.substr(0,i);
	else if(i!==-1)
		loc = loc.substr(0,i2);
	loc = decodeURIComponent(loc.substr(1));
	var li = $('body>nav>ul[is=dropdown]>li>a[href="'+loc+'"]').parent('li');
	li.addClass('active');
	li.parent('ul').parent('li').addClass('active');
	$(window).on('unload',function(){
		$('main').css('opacity',0.5);
	});
});
<!--#include virtual="/js/retro.js" -->
$css('print.min','print');
