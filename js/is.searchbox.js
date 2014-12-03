$css('jquery-ui/core');
$css('jquery-ui/menu');
$css('jquery-ui/autocomplete');

$js('jquery',function(){
	$('[is=searchbox]').each(function(){
		var input = $(this).find('input[type=search]');
		var select = $(this).find('select');
		var source = input.attr('data-url');
		$(this).submit(function(e){
			e.preventDefault();
			var val = input.val();
			if(val){
				val = '+phonemic:'+val;
			}
			document.location = document.location.protocol+'//'+document.location.hostname+'/'+select.val()+val;
			return false;
		});
		$js([
			'jquery-ui/core',
			'jquery-ui/widget',
			'jquery-ui/menu',
			'jquery-ui/position',
			'jquery-ui/autocomplete'
		],true,function(){
			input.autocomplete({
				source:source+'?name='+select.val(),
				selectFirst:true,
				autoFill:true,
				minLength: 3
			});
			select.change(function(){
				input.autocomplete('option','source',source+'?name='+select.val());
			});
		});
	});
});