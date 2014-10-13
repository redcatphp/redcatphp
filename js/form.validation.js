$js('jquery',function(){
	<!--#include virtual="/js/validate.js" -->
	var onSubmit = function(e){
		e.preventDefault();
		var email = $(document).data('persona.email');
		var THIS = $(this);
		var submit = function(){
			THIS.off('submit',onSubmit).submit();
		};
		if(email){
			submit();
		}
		else{
			$(document).one('persona.login',submit);
			$('[is=persona]:first').click();
		}
		return false;
	};
	var form = $('main form[action][id]');
	form.on('submit',onSubmit);
	$('input[type=url]',form).on('keyup',function(){
		var self = $(this);
		var val = self.val();
		var oval = val;
		try{
			val = decodeURIComponent(val);
		}
		catch(e){}
		if(val.indexOf('://')<0){
			val = 'http://'+val;
		}
		if(oval!=val){
			self.val(val);
		}
	});
});