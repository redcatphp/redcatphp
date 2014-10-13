$js(true,['jquery','/x-dom/galleria/galleria.js'],function(){
	Galleria.loadTheme('/x-dom/galleria/themes/classic/galleria.classic.min.js');
	$('[is=galleria]').each(function(){
		Galleria.run(this,{
			transition: 'fade',
			imageCrop: true,
			autoplay: 5000,
			lightbox: true
		});
	});
});
