$js('jquery',function(){
	var splitter = function(loc,splitters){
		for(var i in splitters){
			var idf = loc.indexOf(splitters[i]);
			if(idf>-1){
				loc = loc.substr(0,idf);
			}
		}
		return loc;
	};
	var splitterOnce = function(loc,splitters){		
		for(var i in splitters){
			var idf = loc.indexOf(splitters[i]);
			if(idf>-1){
				var x = loc.split(splitters[i]);
				loc = x[0]+splitters[i]+x[1];
			}
		}
		return loc;
	}
	var location = document.location.pathname;
	location = decodeURIComponent(location.substr(1));
	var loc = splitter(location,['+','/',':','-']);
	var loc2 = splitterOnce(location,['+','-']);
	var selectorMenu = 'body>nav>ul[is=dropdown]>li:has(>a[href^="'+loc+'"])';
	if(loc!=loc2)
		selectorMenu += ',body>nav>ul[is=dropdown]>li>ul>li:has(>a[href^="'+loc2+'"])';
	selectorMenu += ',body>footer>a[href="'+location+'"]';
	$(selectorMenu).addClass('active');
	
	$(window).on('unload',function(){
		$('main').css('opacity',0.5);
	});
});
<!--#include virtual="/js/retro.js" -->
$css('print.min','print');