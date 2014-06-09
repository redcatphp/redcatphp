/*
	jQuery Tags Input Plugin 1.3.3 Copyright (c) 2011 XOXCO, Inc - Documentation for this plugin lives here: http://xoxco.com/clickable/jquery-tags-input
	Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php ben@xoxco.com
	modified by surikat add option defaultTextRemove and lazy-loading off js and css
*/
$css('/x-dom/tagsinput');
$js(true,[
	'jquery',
	'jquery-ui/core',
	'jquery-ui/widget',
	'jquery-ui/menu',
	'jquery-ui/position',
	'jquery-ui/autocomplete',
	'string'
],function(){
	var delimiter = new Array();
	var tags_callbacks = new Array();
	$.fn.addTag = function(value,options) {
		options = $.extend({focus:false,callback:true},options);
		this.each(function() { 
			var id = $(this).attr('id');
			var tagslist = $(this).val().split(delimiter[id]);
			if (tagslist[0]=='')
				tagslist = new Array();
			value = $.trim(value);
			var skipTag = false; 
			if(options.unique)
				skipTag = $(this).tagExist(value);
			if(!skipTag&&options.max&&options.max<=$(this).closest('.tagsinput-wrap').find('.tagsinput>span').length){
				skipTag = true;
				//$('#'+id+'_tag').hide();
			}
			if(skipTag)
				$('#'+id+'_tag').addClass('not_valid');
			if(value!=''&&!skipTag){
				$('<span>').addClass('tag').append(
					$('<span>').text(value).append('&nbsp;&nbsp;'),
					$('<a>', {
						href  : '#',
						title : $(this).data('removeText'),
						text  : 'x'
					}).click(function () {
						return $('#' + id).removeTag(escape(value));
					})
				).insertBefore('#' + id + '_addTag');
				tagslist.push(value);
				$('#'+id+'_tag').val('');
				if(options.focus)
					$('#'+id+'_tag').focus();
				else
					$('#'+id+'_tag').blur();
				$.fn.tagsInput.updateTagsField(this,tagslist);
				if (options.callback && tags_callbacks[id] && tags_callbacks[id]['onAddTag']) {
					var f = tags_callbacks[id]['onAddTag'];
					f.call(this, value);
				}
				if(tags_callbacks[id] && tags_callbacks[id]['onChange']){
					var i = tagslist.length;
					var f = tags_callbacks[id]['onChange'];
					f.call(this, $(this), tagslist[i-1]);
				}					
			}
		});		
		return false;
	};
	$.fn.removeTag = function(value) { 
		value = unescape(value);
		this.each(function() { 
			var id = $(this).attr('id');
			var old = $(this).val().split(delimiter[id]);
			$('#'+id+'_tagsinput .tag').remove();
			str = '';
			for (i=0; i< old.length; i++){ 
				if (old[i]!=value)
					str = str + delimiter[id] +old[i];
			}
			$.fn.tagsInput.importTags(this,str);
			if (tags_callbacks[id] && tags_callbacks[id]['onRemoveTag']) {
				var f = tags_callbacks[id]['onRemoveTag'];
				f.call(this, value);
			}
		});
		return false;
	};
	$.fn.tagExist = function(val) {
		var id = $(this).attr('id');
		var tagslist = $(this).val().split(delimiter[id]);
		return ($.inArray(val, tagslist) >= 0); //true when tag exists, false when not
	};
	$.fn.importTags = function(str){ // clear all existing tags and import new ones from a string
		id = $(this).attr('id');
		$('#'+id+'_tagsinput .tag').remove();
		$.fn.tagsInput.importTags(this,str);
	};
	$.fn.tagsInput = function(options) { 
		var settings = $.extend({
		  defaultText:'add a tag',
		  defaultTextRemove:'Removing tag',
		  minChars:0,
		  max:0,
		  'unique':true,
		  comfortZone: 20,
		  after: false
		},options);
		$(this).data('removeText',settings.defaultTextRemove);
		$(this).data('max',settings.max);
		this.each(function(){ 
			$(this).hide();
			var id = $(this).attr('id');
			if (!id || delimiter[$(this).attr('id')]) {
				id = $(this).attr('id', 'tags' + new Date().getTime()).attr('id');
			}
			var data = $.extend({
				pid:id,
				real_input: '#'+id,
				holder: '#'+id+'_tagsinput',
				input_wrapper: '#'+id+'_addTag',
				fake_input: '#'+id+'_tag'
			},settings);
			delimiter[id] = data.delimiter;
			if (settings.onAddTag || settings.onRemoveTag || settings.onChange) {
				tags_callbacks[id] = new Array();
				tags_callbacks[id]['onAddTag'] = settings.onAddTag;
				tags_callbacks[id]['onRemoveTag'] = settings.onRemoveTag;
				tags_callbacks[id]['onChange'] = settings.onChange;
			}
			var markup = '<div id="'+id+'_tagsinput" class="tagsinput"><div id="'+id+'_addTag">';
			markup += '<input id="'+id+'_tag" value="" placeholder="'+settings.defaultText+'" />';
			markup += '</div><div class="tags_clear"></div></div>';
			if(settings.after)
				$(markup).insertAfter(this);
			else
				$(markup).insertBefore(this);
			if ($(data.real_input).val()!='') 
				$.fn.tagsInput.importTags($(data.real_input),$(data.real_input).val());
			$(data.fake_input).attr('placeholder',$(data.fake_input).attr('placeholder'));
			$(data.holder).on('click',data,function(event) {
				$(event.data.fake_input).focus();
			});
			$(data.fake_input).autocomplete(settings.autocomplete);
			$(data.fake_input).on('autocompleteselect',data,function(event,ui) {
				$(event.data.real_input).addTag(ui.item.value,{focus:true,unique:settings.unique,max:settings.max});
				return false;
			});
			if(settings.autocomplete&&typeof(settings.autocomplete.source)=='function'){				
				$(data.fake_input).on('focus',function(){
					$(this).autocomplete('search','');
				});
			}
			var addingTag = function(event){
				if((event.data.minChars <= $(event.data.fake_input).val().length) && (!event.data.maxChars || (event.data.maxChars >= $(event.data.fake_input).val().length)) ){
					var addValue = $(event.data.fake_input).val();
					addValue = addValue.split(settings.delimiter);
					for (i=0; i<addValue.length; i++) { 
						$(event.data.real_input).addTag(addValue[i],{focus:true,unique:settings.unique,max:settings.max});
					}
				}
			};
			$(data.fake_input).on('keypress',data,function(event) { // if user types a comma, create a new tag
				if (event.which==event.data.delimiter.charCodeAt(0) || event.which==13 ) {
					event.preventDefault();
					addingTag(event);
					return false;
				}
			});
			$(data.fake_input).on('blur',data,function(event){
				addingTag(event);
			});
			$(data.fake_input).on('keydown', function(event){ //Delete last tag on backspace
				if(event.keyCode == 8 && $(this).val() == ''){
					 event.preventDefault();
					 var last_tag = $(this).closest('.tagsinput').find('.tag:last').text();
					 var id = $(this).attr('id').replace(/_tag$/, '');
					 last_tag = last_tag.replace(/[\s]+x$/, '');
					 $('#' + id).removeTag(escape(last_tag));
					 $(this).trigger('focus');
				}
			});
			$(data.fake_input).blur();
			if(data.unique) { //Removes the not_valid class when user changes the value of the fake input
				$(data.fake_input).keydown(function(event){
					if(event.keyCode == 8 || String.fromCharCode(event.which).match(/\w+|[áéíóúÁÉÍÓÚñÑ,/]+/)) {
						$(this).removeClass('not_valid');
					}
				});
			}
		});
		return this;
	};
	$.fn.tagsInput.updateTagsField = function(obj,tagslist) { 
		var id = $(obj).attr('id');
		$(obj).val(tagslist.join(delimiter[id]));
	};
	$.fn.tagsInput.importTags = function(obj,val) {
		$(obj).val('');
		var id = $(obj).attr('id');
		var tags = val.split(delimiter[id]);
		for (i=0; i<tags.length; i++) { 
			$(obj).addTag(tags[i],{focus:false,callback:false,max:$(obj).data('max')});
		}
		if(tags_callbacks[id] && tags_callbacks[id]['onChange']){
			var f = tags_callbacks[id]['onChange'];
			f.call(obj, obj, tags[i]);
		}
	};
	$('[is=tagsinput]').each(function(){
		var THIS = $(this);
		var splitter = THIS.attr('data-splitter')||',';
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
