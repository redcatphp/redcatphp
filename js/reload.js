$js('jquery',function(){
	$.fn.reload = function(target){
		return this.each(function(){
			var self = $(this);
			self.wrap('<div />').find('[data-reload]').each(function(){
				var load = $(this).attr('data-reload');
				var t = target;
				if(target){
					if(!(t instanceof jQuery)){
						t = $(t);
					}
					t = $('*[data-load="'+load+'"][data-href]',t);
				}
				else{
					t = $('*[data-load="'+load+'"][data-href]',self);
				}
				t.each(function(){
					var href = $(this).attr('data-href');
					$(this).load(href);
				});
			});
		});
	};
	$.reload = function(source,target){
		if(!(source instanceof jQuery)){
			source = $(source);
		}
		return source.reload(target);
	};
	$.reloader = function(source,target){
		return $('<b data-reload="'+source+'" />').reload(target);
	};
});