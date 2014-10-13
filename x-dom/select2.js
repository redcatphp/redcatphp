$css('select2');
$js(true,[
	'jquery',
	'select2',
	'select2/fr'
],function(){
	$('[is=select2]').each(function(){
		var THIS = $(this);
		THIS.select2({
			dropdownAutoWidth:true,
			width:'100%',
			//placeholder: "",
			//minimumInputLength: 1,
			//ajax: {
				//url: '',
				//dataType: 'jsonp',
				//data: function(term, page){
					//return {
						//q: term, // search term
						//page_limit: 10
					//};
				//},
				//results: function(data, page){
					//return {results: data.movies};
				//}
			//},
			//initSelection: function(element, callback) {
				// the input tag has a value attribute preloaded that points to a preselected movie's id
				// this function resolves that id attribute to an object that select2 can render
				// using its formatResult renderer - that way the movie name is shown preselected
				//var id = $(element).val();
				//if(id!==''){
					//$.ajax("", {
						//data: {
							//
						//},
						//dataType: 'jsonp'
					//}).done(function(data) { callback(data); });
				//}
			//},
			//formatResult: ,
			//formatSelection: , 
			//dropdownCssClass: "bigdrop", // apply css that makes the dropdown taller
			//escapeMarkup: function (m) { return m; },
		});
	});
});