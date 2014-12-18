$js('jquery',function(){
	$('[is="vertical-nav"]').each(function(){
		$(this)
			.addClass('master')
			.find('li a').on('click', function(e) {
				var thisA = $(this),
					thisLi = thisA.parent('li'),
					thisParentUl = thisLi.parent('ul');
				if (thisParentUl.hasClass('master')) {
					e.preventDefault();
					e.stopPropagation();
					thisLi
						.toggleClass('active')
						.siblings()
						.removeClass('active');
				}
			})
		;
	});
});