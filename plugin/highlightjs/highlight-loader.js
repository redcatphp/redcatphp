$js(['jquery','/plugin/highlightjs/highlight.js'],function(){
	hljs.configure({useBR: true});
	$('code').each(function(i, block){
		var s = $(block).attr('class').split(' ');
		var lang;
		for(var i in s){
			if(s[i].indexOf('lang-')===0){
				lang = s[i].substr(5);
				break;
			}
		}
		var load = function(){
			hljs.highlightBlock(block);
			
		};
		if(lang){
			if(hljs.listLanguages().indexOf(lang)<0){
				$.get('/plugin/highlightjs/languages/'+lang+'.js',function(func){
					eval('hljs.registerLanguage(lang,'+func+');');
					load();
				},'text');
			}
			else{
				load();
			}
		}
	});
});