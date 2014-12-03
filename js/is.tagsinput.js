$css('jquery-ui/core');
$css('jquery-ui/menu');
$css('jquery-ui/autocomplete');
$css('tags');
$js(true,[
	'jquery',
	'jquery-ui/core',
	'jquery-ui/widget',
	'jquery-ui/menu',
	'jquery-ui/position',
	'jquery-ui/autocomplete',
	'string',
	'tags'
],function(){
	$('[is=tagsinput]').each(function(){
		var THIS = $(this);
		var splitter = THIS.attr('data-splitter')||' ';
		var suggest = THIS.parent().find('.tags-suggests');
		var suggestion = [];
		if(suggest){
			var suggestion = suggest.text().trim();
			suggest.text('');
			var idf = suggestion.indexOf(':');
			if(idf>-1)
				suggestion = suggestion.substr(idf+1);
			suggestion = suggestion.split(splitter);
			for(var i in suggestion)
				suggestion[i] = suggestion[i].trim();
		}
		THIS.wrap('<div class="tagsinput-wrap" />');
		var data_url = THIS.attr('data-url');
		var data_minchar = THIS.attr('data-minchar');
		var data_maxchar = THIS.attr('data-maxchar');
		var data_max = THIS.attr('data-max');
		data_minchar = parseInt(data_minchar);
		THIS.tagsInput({
			defaultText:THIS.attr('placeholder')?THIS.attr('placeholder'):'',
			defaultTextRemove:'Supprimer ce tag',
			minChars:data_minchar,
			maxChars:data_maxchar,
			max:data_max,
			autocomplete:{
				selectFirst:true,
				autoFill:true,
				minLength: 0,
				source:function(request,response){
					var term = request.term;
					var suggesting = [];
					var termSa = stripAccents(term);
					for(var k in suggestion)
						if(stripAccents(suggestion[k]).indexOf(termSa)===0)
							suggesting.push(suggestion[k]);
					response(suggesting);
					if(term.length>=1&&data_url){
						$.ajax({
							type:'GET',
							dataType:'json',
							url:data_url,
							data:{
								'term':term
							},
							success:function(j){
								for(var k in j)
									if(suggesting.indexOf(j[k])<=-1)
										suggesting.push(j[k]);
								response(suggesting);
							}
						});
					}
				},
				appendTo: THIS.parent(),
				position: {
					my: 'left top-3',
					at: 'left bottom',
					collision: 'none'
				}
			},
			'delimiter':splitter,
			'unique':true,
			after:$(this).attr('data-after')=='true'
		});
	});
});
