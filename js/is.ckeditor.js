<!--#include virtual="../../plugin/ckeditor/ckeditor.js" -->
$js([
	'jquery',
	//'/plugin/ckeditor/ckeditor.js'
],function(){
	$('[is=ckeditor]').each(function(){
		$(this).wrap('<div/>');
		CKEDITOR.basePath = $('base').attr('href')+'plugin/ckeditor/';
		CKEDITOR.replace(this);
	});
});
