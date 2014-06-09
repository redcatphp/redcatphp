$js(true,[
	'jquery',
	'/x-dom/ckeditor.js',
	'/x-dom/daterange.js',
],function(){
	<!--#include virtual="/js/sisyphus.js" -->
	$('main>form[id][action][role=form]:not(.form-posted)').sisyphus();
});
