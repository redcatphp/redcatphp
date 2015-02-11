$js('jquery',function(){
	$.fn.launch = function(target){
		if(!target)
			target = this;
		if(!(target instanceof jQuery)){
			target = $(target);
		}
		return this.each(function(){
			$(this).find('[data-launch]').each(function(){
				target.trigger($(this).attr('data-launch'));
			});
		});
	};
	$.launch = function(source,target){
		if(!(source instanceof jQuery)){
			source = $(source);
		}
		return source.launch(target);
	};
});