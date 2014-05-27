$js([
	'jquery',
	'/plugin/ckeditor/ckeditor.js'
],function(){
	$('[is=ckeditor]').each(function(){
		$(this).wrap('<div/>');
		CKEDITOR.basePath = '/plugin/ckeditor/';
		CKEDITOR.replace(this);
	});
});
